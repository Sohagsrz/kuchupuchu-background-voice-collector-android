package com.kuchupuchu.audiorecorder.recording

import android.annotation.SuppressLint
import android.content.Context
import android.media.AudioFormat
import android.media.AudioRecord
import android.media.MediaCodec
import android.media.MediaCodecInfo
import android.media.MediaFormat
import android.media.MediaMuxer
import android.media.MediaRecorder
import android.os.SystemClock
import android.util.Log
import java.io.File
import java.nio.ByteBuffer
import java.util.concurrent.atomic.AtomicBoolean

interface AudioRecorderEngineListener {
    fun onSegmentCompleted(
        segmentId: String,
        file: File,
        startedAtUtcMs: Long,
        endedAtUtcMs: Long,
        durationMs: Long
    )
    fun onDurationTick(durationMs: Long)
    fun onError(error: Throwable)
}

class AudioRecorderEngine(
    private val context: Context,
    private val segmentManager: SegmentManager
) {
    private val isRecording = AtomicBoolean(false)
    private var recordingThread: Thread? = null

    private var audioRecord: AudioRecord? = null
    private var mediaCodec: MediaCodec? = null

    @Volatile
    private var currentMuxer: MediaMuxer? = null

    @Volatile
    private var currentMuxerTrackIndex = -1

    @Volatile
    private var isMuxerStarted = false

    @Volatile
    private var cachedOutputFormat: MediaFormat? = null

    private var currentSegmentId: String = ""
    private var currentSegmentFile: File? = null
    private var currentSegmentStartUtcMs: Long = 0L
    private var currentSegmentStartPresentationUs: Long = 0L

    private var totalRecordingStartElapsedRealtime: Long = 0L

    @SuppressLint("MissingPermission")
    fun startRecording(
        segmentDurationSeconds: Int,
        bitrateKbps: Int,
        listener: AudioRecorderEngineListener
    ): Boolean {
        if (isRecording.get()) {
            Log.w(TAG, "Recording already in progress")
            return false
        }

        try {
            val sampleRate = 44100
            val channelConfig = AudioFormat.CHANNEL_IN_MONO
            val audioFormat = AudioFormat.ENCODING_PCM_16BIT
            val minBufferSize = AudioRecord.getMinBufferSize(sampleRate, channelConfig, audioFormat)
            val bufferSize = (minBufferSize * 2).coerceAtLeast(4096)

            val record = AudioRecord(
                MediaRecorder.AudioSource.MIC,
                sampleRate,
                channelConfig,
                audioFormat,
                bufferSize
            )

            if (record.state != AudioRecord.STATE_INITIALIZED) {
                record.release()
                throw IllegalStateException("AudioRecord failed to initialize")
            }

            val codecFormat = MediaFormat.createAudioFormat(MediaFormat.MIMETYPE_AUDIO_AAC, sampleRate, 1).apply {
                setInteger(MediaFormat.KEY_AAC_PROFILE, MediaCodecInfo.CodecProfileLevel.AACObjectLC)
                setInteger(MediaFormat.KEY_BIT_RATE, bitrateKbps * 1000)
                setInteger(MediaFormat.KEY_MAX_INPUT_SIZE, bufferSize)
            }

            val codec = MediaCodec.createEncoderByType(MediaFormat.MIMETYPE_AUDIO_AAC).apply {
                configure(codecFormat, null, null, MediaCodec.CONFIGURE_FLAG_ENCODE)
                start()
            }

            audioRecord = record
            mediaCodec = codec

            val segmentId = segmentManager.generateSegmentId()
            val file = segmentManager.createSegmentFile(segmentId)
            currentSegmentId = segmentId
            currentSegmentFile = file
            currentSegmentStartUtcMs = System.currentTimeMillis()
            currentSegmentStartPresentationUs = 0L
            cachedOutputFormat = null
            isMuxerStarted = false
            currentMuxerTrackIndex = -1

            currentMuxer = MediaMuxer(file.absolutePath, MediaMuxer.OutputFormat.MUXER_OUTPUT_MPEG_4)

            isRecording.set(true)
            totalRecordingStartElapsedRealtime = SystemClock.elapsedRealtime()

            record.startRecording()

            recordingThread = Thread({
                runAudioProcessingLoop(
                    sampleRate = sampleRate,
                    bufferSize = bufferSize,
                    segmentDurationMs = segmentDurationSeconds * 1000L,
                    listener = listener
                )
            }, "KuchuPuchu-AudioCapture").apply {
                priority = Thread.MAX_PRIORITY
                start()
            }

            return true
        } catch (e: Exception) {
            Log.e(TAG, "Failed to start audio recording engine", e)
            cleanup()
            listener.onError(e)
            return false
        }
    }

    fun stopRecording(listener: AudioRecorderEngineListener?) {
        if (!isRecording.compareAndSet(true, false)) {
            return
        }

        try {
            audioRecord?.stop()
        } catch (e: Exception) {
            Log.w(TAG, "Error stopping AudioRecord", e)
        }

        try {
            recordingThread?.join(2000)
        } catch (e: Exception) {
            Log.w(TAG, "Error waiting for recording thread termination", e)
        }

        finalizeCurrentSegment(listener)
        cleanup()
    }

    private fun runAudioProcessingLoop(
        sampleRate: Int,
        bufferSize: Int,
        segmentDurationMs: Long,
        listener: AudioRecorderEngineListener
    ) {
        val record = audioRecord ?: return
        val codec = mediaCodec ?: return
        val audioBuffer = ByteArray(bufferSize)
        val bufferInfo = MediaCodec.BufferInfo()

        var totalSamplesCaptured: Long = 0
        var lastDurationTickMs = 0L

        try {
            while (isRecording.get()) {
                val readBytes = record.read(audioBuffer, 0, audioBuffer.size)
                if (readBytes > 0) {
                    val inputBufferIndex = codec.dequeueInputBuffer(10_000)
                    if (inputBufferIndex >= 0) {
                        val inputBuffer: ByteBuffer? = codec.getInputBuffer(inputBufferIndex)
                        inputBuffer?.clear()
                        inputBuffer?.put(audioBuffer, 0, readBytes)

                        val presentationTimeUs = (totalSamplesCaptured * 1_000_000L) / sampleRate
                        codec.queueInputBuffer(
                            inputBufferIndex,
                            0,
                            readBytes,
                            presentationTimeUs,
                            0
                        )
                        totalSamplesCaptured += (readBytes / 2) // 16-bit PCM mono = 2 bytes per sample
                    }
                }

                drainEncoder(
                    codec = codec,
                    bufferInfo = bufferInfo,
                    segmentDurationMs = segmentDurationMs,
                    listener = listener
                )

                val elapsedMs = SystemClock.elapsedRealtime() - totalRecordingStartElapsedRealtime
                if (elapsedMs - lastDurationTickMs >= 500) {
                    lastDurationTickMs = elapsedMs
                    listener.onDurationTick(elapsedMs)
                }
            }

            // Flush remaining data
            val inputBufferIndex = codec.dequeueInputBuffer(10_000)
            if (inputBufferIndex >= 0) {
                codec.queueInputBuffer(
                    inputBufferIndex,
                    0,
                    0,
                    (totalSamplesCaptured * 1_000_000L) / sampleRate,
                    MediaCodec.BUFFER_FLAG_END_OF_STREAM
                )
            }
            drainEncoder(codec, bufferInfo, segmentDurationMs, listener, endOfStream = true)

        } catch (e: Exception) {
            if (isRecording.get()) {
                Log.e(TAG, "Exception during audio capture loop", e)
                listener.onError(e)
            }
        }
    }

    private fun drainEncoder(
        codec: MediaCodec,
        bufferInfo: MediaCodec.BufferInfo,
        segmentDurationMs: Long,
        listener: AudioRecorderEngineListener,
        endOfStream: Boolean = false
    ) {
        val timeoutUs = if (endOfStream) 20_000L else 0L

        while (true) {
            val outputBufferIndex = codec.dequeueOutputBuffer(bufferInfo, timeoutUs)
            if (outputBufferIndex == MediaCodec.INFO_TRY_AGAIN_LATER) {
                if (!endOfStream) break else break
            } else if (outputBufferIndex == MediaCodec.INFO_OUTPUT_FORMAT_CHANGED) {
                val newFormat = codec.outputFormat
                cachedOutputFormat = newFormat
                synchronized(this) {
                    val muxer = currentMuxer
                    if (muxer != null && !isMuxerStarted) {
                        currentMuxerTrackIndex = muxer.addTrack(newFormat)
                        muxer.start()
                        isMuxerStarted = true
                    }
                }
            } else if (outputBufferIndex >= 0) {
                val encodedData = codec.getOutputBuffer(outputBufferIndex)
                if (encodedData != null && (bufferInfo.flags and MediaCodec.BUFFER_FLAG_CODEC_CONFIG) == 0) {
                    if (bufferInfo.size != 0) {
                        val presentationUs = bufferInfo.presentationTimeUs
                        if (currentSegmentStartPresentationUs == 0L) {
                            currentSegmentStartPresentationUs = presentationUs
                        }

                        val segmentRelativeUs = presentationUs - currentSegmentStartPresentationUs
                        val segmentRelativeMs = segmentRelativeUs / 1000L

                        // Check if segment duration reached
                        if (segmentRelativeMs >= segmentDurationMs && isRecording.get()) {
                            rotateSegment(listener, presentationUs)
                        }

                        synchronized(this) {
                            val muxer = currentMuxer
                            if (muxer != null && isMuxerStarted && currentMuxerTrackIndex >= 0) {
                                encodedData.position(bufferInfo.offset)
                                encodedData.limit(bufferInfo.offset + bufferInfo.size)

                                val adjustedBufferInfo = MediaCodec.BufferInfo().apply {
                                    set(
                                        bufferInfo.offset,
                                        bufferInfo.size,
                                        presentationUs - currentSegmentStartPresentationUs,
                                        bufferInfo.flags
                                    )
                                }
                                muxer.writeSampleData(currentMuxerTrackIndex, encodedData, adjustedBufferInfo)
                            }
                        }
                    }
                }
                codec.releaseOutputBuffer(outputBufferIndex, false)
                if ((bufferInfo.flags and MediaCodec.BUFFER_FLAG_END_OF_STREAM) != 0) {
                    break
                }
            }
        }
    }

    private fun rotateSegment(
        listener: AudioRecorderEngineListener,
        currentPresentationUs: Long
    ) {
        synchronized(this) {
            val oldMuxer = currentMuxer
            val oldFile = currentSegmentFile
            val oldSegmentId = currentSegmentId
            val oldStartedAtUtcMs = currentSegmentStartUtcMs
            val nowUtcMs = System.currentTimeMillis()
            val durationMs = nowUtcMs - oldStartedAtUtcMs

            try {
                if (oldMuxer != null && isMuxerStarted) {
                    oldMuxer.stop()
                    oldMuxer.release()
                }
            } catch (e: Exception) {
                Log.w(TAG, "Error stopping rotated muxer", e)
            }

            if (oldFile != null && oldSegmentId.isNotEmpty()) {
                listener.onSegmentCompleted(
                    segmentId = oldSegmentId,
                    file = oldFile,
                    startedAtUtcMs = oldStartedAtUtcMs,
                    endedAtUtcMs = nowUtcMs,
                    durationMs = durationMs
                )
            }

            // Immediately prepare next segment
            val nextSegmentId = segmentManager.generateSegmentId()
            val nextFile = segmentManager.createSegmentFile(nextSegmentId, nowUtcMs)
            currentSegmentId = nextSegmentId
            currentSegmentFile = nextFile
            currentSegmentStartUtcMs = nowUtcMs
            currentSegmentStartPresentationUs = currentPresentationUs

            try {
                val newMuxer = MediaMuxer(nextFile.absolutePath, MediaMuxer.OutputFormat.MUXER_OUTPUT_MPEG_4)
                currentMuxer = newMuxer
                val format = cachedOutputFormat
                if (format != null) {
                    currentMuxerTrackIndex = newMuxer.addTrack(format)
                    newMuxer.start()
                    isMuxerStarted = true
                } else {
                    isMuxerStarted = false
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error initializing new segment muxer", e)
                listener.onError(e)
            }
        }
    }

    private fun finalizeCurrentSegment(listener: AudioRecorderEngineListener?) {
        synchronized(this) {
            val muxer = currentMuxer
            val file = currentSegmentFile
            val segmentId = currentSegmentId
            val startedAtUtcMs = currentSegmentStartUtcMs
            val nowUtcMs = System.currentTimeMillis()
            val durationMs = (nowUtcMs - startedAtUtcMs).coerceAtLeast(0)

            try {
                if (muxer != null && isMuxerStarted) {
                    muxer.stop()
                    muxer.release()
                }
            } catch (e: Exception) {
                Log.w(TAG, "Error stopping final muxer: ${e.message}")
            } finally {
                currentMuxer = null
                isMuxerStarted = false
                currentMuxerTrackIndex = -1
            }

            if (file != null && segmentId.isNotEmpty() && durationMs > 500 && file.length() > 500) {
                listener?.onSegmentCompleted(
                    segmentId = segmentId,
                    file = file,
                    startedAtUtcMs = startedAtUtcMs,
                    endedAtUtcMs = nowUtcMs,
                    durationMs = durationMs
                )
            } else if (file != null && file.exists() && file.length() <= 500) {
                // Audio segment was trivial/empty upon immediate cancellation
                file.delete()
            }
        }
    }

    private fun cleanup() {
        try {
            audioRecord?.release()
        } catch (e: Exception) {
            Log.w(TAG, "Error releasing audioRecord", e)
        }
        audioRecord = null

        try {
            mediaCodec?.stop()
            mediaCodec?.release()
        } catch (e: Exception) {
            Log.w(TAG, "Error releasing mediaCodec", e)
        }
        mediaCodec = null

        recordingThread = null
    }

    companion object {
        private const val TAG = "AudioRecorderEngine"
    }
}

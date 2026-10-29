package com.kuchupuchu.audiorecorder.recording

import android.content.Context
import android.media.MediaExtractor
import android.media.MediaFormat
import android.util.Log
import java.io.File
import java.io.FileInputStream
import java.security.MessageDigest
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone
import java.util.UUID

class SegmentManager(private val context: Context) {

    val recordingsDir: File by lazy {
        File(context.filesDir, "recordings").apply {
            if (!exists()) {
                mkdirs()
            }
        }
    }

    fun generateSegmentId(): String = UUID.randomUUID().toString()

    fun createSegmentFile(segmentId: String, timestamp: Long = System.currentTimeMillis()): File {
        val dateFormat = SimpleDateFormat("yyyyMMdd_HHmmss", Locale.US).apply {
            timeZone = TimeZone.getTimeZone("UTC")
        }
        val dateStr = dateFormat.format(Date(timestamp))
        val filename = "audio_${dateStr}_${segmentId.take(8)}.m4a"
        return File(recordingsDir, filename)
    }

    fun calculateSha256(file: File): String {
        return try {
            val digest = MessageDigest.getInstance("SHA-256")
            FileInputStream(file).use { fis ->
                val buffer = ByteArray(8192)
                var bytesRead: Int
                while (fis.read(buffer).also { bytesRead = it } != -1) {
                    digest.update(buffer, 0, bytesRead)
                }
            }
            digest.digest().joinToString("") { "%02x".format(it) }
        } catch (e: Exception) {
            Log.e(TAG, "Failed to calculate SHA-256 for file ${file.name}", e)
            ""
        }
    }

    /**
     * Validates whether an audio file is a valid, readable M4A / AAC file.
     * Uses MediaExtractor to verify track header and format.
     */
    fun validateAudioFile(file: File): Boolean {
        if (!file.exists() || file.length() < 100) {
            return false
        }
        val extractor = MediaExtractor()
        return try {
            extractor.setDataSource(file.absolutePath)
            val trackCount = extractor.trackCount
            if (trackCount <= 0) return false

            var hasAudioTrack = false
            for (i in 0 until trackCount) {
                val format = extractor.getTrackFormat(i)
                val mime = format.getString(MediaFormat.KEY_MIME) ?: ""
                if (mime.startsWith("audio/")) {
                    hasAudioTrack = true
                    break
                }
            }
            hasAudioTrack
        } catch (e: Exception) {
            Log.w(TAG, "Audio validation failed for ${file.name}: ${e.message}")
            false
        } finally {
            try {
                extractor.release()
            } catch (e: Exception) {
                // ignore
            }
        }
    }

    companion object {
        private const val TAG = "SegmentManager"
    }
}

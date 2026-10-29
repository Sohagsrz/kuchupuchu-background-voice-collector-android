package com.kuchupuchu.audiorecorder.recording

import android.app.Service
import android.content.Context
import android.content.Intent
import android.content.pm.ServiceInfo
import android.os.Build
import android.os.IBinder
import android.os.PowerManager
import android.os.SystemClock
import android.util.Log
import com.kuchupuchu.audiorecorder.KuchuPuchuApp
import com.kuchupuchu.audiorecorder.data.database.RecordingEntity
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.cancel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import java.io.File
import java.util.Locale

class AudioRecordingService : Service(), AudioRecorderEngineListener {

    private val serviceScope = CoroutineScope(Dispatchers.IO + SupervisorJob())

    private lateinit var notificationManager: RecordingNotificationManager
    private lateinit var recorderEngine: AudioRecorderEngine
    private var wakeLock: PowerManager.WakeLock? = null

    private var recordingStartTimeEpochMs: Long = 0L

    override fun onCreate() {
        super.onCreate()
        notificationManager = RecordingNotificationManager(this)

        val app = application as KuchuPuchuApp
        val segmentManager = app.container.segmentManager
        recorderEngine = AudioRecorderEngine(this, segmentManager)

        val powerManager = getSystemService(Context.POWER_SERVICE) as PowerManager
        wakeLock = powerManager.newWakeLock(
            PowerManager.PARTIAL_WAKE_LOCK,
            "KuchuPuchu::RecordingWakeLock"
        ).apply {
            setReferenceCounted(false)
        }
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        val action = intent?.action ?: ACTION_START_RECORDING
        when (action) {
            ACTION_START_RECORDING -> handleStartRecording()
            ACTION_STOP_RECORDING -> handleStopRecording()
        }
        return START_NOT_STICKY
    }

    private fun handleStartRecording() {
        val app = application as KuchuPuchuApp
        val settings = app.container.settingsRepository.settings.value

        // Check storage before starting
        if (app.container.settingsRepository.isStorageCriticallyLow()) {
            _serviceRecordingState.value = RecordingState.Error("Storage space critically low")
            stopSelf()
            return
        }

        // Start Foreground Notification first
        val notification = notificationManager.buildNotification()
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            startForeground(
                RecordingNotificationManager.NOTIFICATION_ID,
                notification,
                ServiceInfo.FOREGROUND_SERVICE_TYPE_MICROPHONE
            )
        } else {
            startForeground(RecordingNotificationManager.NOTIFICATION_ID, notification)
        }

        wakeLock?.acquire(4 * 60 * 60 * 1000L) // Safety timeout 4 hours

        recordingStartTimeEpochMs = System.currentTimeMillis()
        val started = recorderEngine.startRecording(
            segmentDurationSeconds = settings.segmentDurationSeconds,
            bitrateKbps = settings.audioBitrateKbps,
            listener = this
        )

        if (started) {
            _serviceRecordingState.value = RecordingState.Recording(
                startedAtEpochMs = recordingStartTimeEpochMs,
                durationMs = 0L
            )
        } else {
            _serviceRecordingState.value = RecordingState.Error("Failed to initialize microphone")
            stopSelf()
        }
    }

    private fun handleStopRecording() {
        recorderEngine.stopRecording(this)
        _serviceRecordingState.value = RecordingState.Idle
        stopForeground(STOP_FOREGROUND_REMOVE)
        stopSelf()
    }

    override fun onSegmentCompleted(
        segmentId: String,
        file: File,
        startedAtUtcMs: Long,
        endedAtUtcMs: Long,
        durationMs: Long
    ) {
        val app = application as KuchuPuchuApp
        val segmentManager = app.container.segmentManager
        val repository = app.container.recordingRepository
        val uploadScheduler = app.container.uploadScheduler

        val checksum = segmentManager.calculateSha256(file)
        val entity = RecordingEntity(
            id = segmentId,
            filename = file.name,
            localPath = file.absolutePath,
            startedAt = startedAtUtcMs,
            endedAt = endedAtUtcMs,
            durationMs = durationMs,
            sizeBytes = file.length(),
            mimeType = "audio/mp4",
            status = RecordingStatus.QUEUED,
            checksum = checksum
        )

        serviceScope.launch {
            repository.saveRecording(entity)
            uploadScheduler.scheduleUploadWork()
        }
    }

    override fun onDurationTick(durationMs: Long) {
        val hours = (durationMs / (1000 * 60 * 60))
        val minutes = (durationMs / (1000 * 60)) % 60
        val seconds = (durationMs / 1000) % 60
        val durationFormatted = String.format(Locale.US, "%02d:%02d:%02d", hours, minutes, seconds)

        notificationManager.updateDuration(durationFormatted)

        if (_serviceRecordingState.value is RecordingState.Recording) {
            _serviceRecordingState.value = RecordingState.Recording(
                startedAtEpochMs = recordingStartTimeEpochMs,
                durationMs = durationMs
            )
        }
    }

    override fun onError(error: Throwable) {
        Log.e(TAG, "Audio recording engine error", error)
        _serviceRecordingState.value = RecordingState.Error(error.localizedMessage ?: "Recording error")
        stopForeground(STOP_FOREGROUND_REMOVE)
        stopSelf()
    }

    override fun onDestroy() {
        super.onDestroy()
        recorderEngine.stopRecording(null)
        try {
            if (wakeLock?.isHeld == true) {
                wakeLock?.release()
            }
        } catch (e: Exception) {
            Log.w(TAG, "Error releasing wake lock", e)
        }
        serviceScope.cancel()
        _serviceRecordingState.value = RecordingState.Idle
    }

    override fun onBind(intent: Intent?): IBinder? = null

    companion object {
        const val ACTION_START_RECORDING = "com.kuchupuchu.audiorecorder.START_RECORDING"
        const val ACTION_STOP_RECORDING = "com.kuchupuchu.audiorecorder.STOP_RECORDING"
        private const val TAG = "AudioRecordingService"

        private val _serviceRecordingState = MutableStateFlow<RecordingState>(RecordingState.Idle)
        val serviceRecordingState: StateFlow<RecordingState> = _serviceRecordingState.asStateFlow()
    }
}

package com.kuchupuchu.audiorecorder.ui.viewmodel

import android.content.Context
import android.content.Intent
import androidx.core.content.ContextCompat
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.kuchupuchu.audiorecorder.data.database.RecordingEntity
import com.kuchupuchu.audiorecorder.data.repository.QueueSummary
import com.kuchupuchu.audiorecorder.data.repository.RecordingRepository
import com.kuchupuchu.audiorecorder.recording.AudioRecordingService
import com.kuchupuchu.audiorecorder.recording.RecordingState
import com.kuchupuchu.audiorecorder.settings.SettingsRepository
import com.kuchupuchu.audiorecorder.upload.UploadScheduler
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import java.util.Locale

class MainViewModel(
    private val recordingRepository: RecordingRepository,
    private val settingsRepository: SettingsRepository,
    private val uploadScheduler: UploadScheduler
) : ViewModel() {

    val recordingState: StateFlow<RecordingState> = AudioRecordingService.serviceRecordingState

    val latestRecordings: StateFlow<List<RecordingEntity>> =
        recordingRepository.latestRecordings
            .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val queueSummary: StateFlow<QueueSummary> =
        recordingRepository.queueSummary
            .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), QueueSummary())

    private val _storageWarning = MutableStateFlow<String?>(null)
    val storageWarning: StateFlow<String?> = _storageWarning.asStateFlow()

    init {
        checkStorage()
    }

    fun checkStorage() {
        val availableBytes = settingsRepository.getAvailableStorageBytes()
        if (availableBytes in 0..(100 * 1024 * 1024L)) {
            val mb = availableBytes / (1024 * 1024)
            _storageWarning.value = "Low device storage: only $mb MB remaining!"
        } else {
            _storageWarning.value = null
        }
    }

    fun startRecording(context: Context) {
        checkStorage()
        val intent = Intent(context, AudioRecordingService::class.java).apply {
            action = AudioRecordingService.ACTION_START_RECORDING
        }
        ContextCompat.startForegroundService(context, intent)
    }

    fun stopRecording(context: Context) {
        val intent = Intent(context, AudioRecordingService::class.java).apply {
            action = AudioRecordingService.ACTION_STOP_RECORDING
        }
        context.startService(intent)
    }

    fun retryUpload(id: String) {
        viewModelScope.launch {
            recordingRepository.retryUpload(id)
            uploadScheduler.scheduleUploadWork(forceReplace = true)
        }
    }

    fun retryAllFailed() {
        viewModelScope.launch {
            recordingRepository.retryAllFailed()
            uploadScheduler.scheduleUploadWork(forceReplace = true)
        }
    }

    fun formatDuration(durationMs: Long): String {
        val hours = durationMs / (1000 * 60 * 60)
        val minutes = (durationMs / (1000 * 60)) % 60
        val seconds = (durationMs / 1000) % 60
        return String.format(Locale.US, "%02d:%02d:%02d", hours, minutes, seconds)
    }

    fun formatFileSize(bytes: Long): String {
        return when {
            bytes < 1024 -> "$bytes B"
            bytes < 1024 * 1024 -> String.format(Locale.US, "%.1f KB", bytes / 1024.0)
            else -> String.format(Locale.US, "%.1f MB", bytes / (1024.0 * 1024.0))
        }
    }

    class Factory(
        private val recordingRepository: RecordingRepository,
        private val settingsRepository: SettingsRepository,
        private val uploadScheduler: UploadScheduler
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            return MainViewModel(recordingRepository, settingsRepository, uploadScheduler) as T
        }
    }
}

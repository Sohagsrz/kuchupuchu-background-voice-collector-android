package com.kuchupuchu.audiorecorder.ui.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.kuchupuchu.audiorecorder.data.repository.QueueSummary
import com.kuchupuchu.audiorecorder.data.repository.RecordingRepository
import com.kuchupuchu.audiorecorder.settings.AppSettings
import com.kuchupuchu.audiorecorder.settings.SettingsRepository
import com.kuchupuchu.audiorecorder.upload.UploadRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import java.util.Locale

sealed interface ConnectionTestState {
    data object Idle : ConnectionTestState
    data object Testing : ConnectionTestState
    data class Success(val message: String) : ConnectionTestState
    data class Error(val message: String) : ConnectionTestState
}

class SettingsViewModel(
    private val settingsRepository: SettingsRepository,
    private val uploadRepository: UploadRepository,
    private val recordingRepository: RecordingRepository
) : ViewModel() {

    val settings: StateFlow<AppSettings> = settingsRepository.settings

    val queueSummary: StateFlow<QueueSummary> =
        recordingRepository.queueSummary
            .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), QueueSummary())

    private val _connectionTestState = MutableStateFlow<ConnectionTestState>(ConnectionTestState.Idle)
    val connectionTestState: StateFlow<ConnectionTestState> = _connectionTestState.asStateFlow()

    fun updateServerBaseUrl(url: String) = settingsRepository.updateServerBaseUrl(url)
    fun updateApiToken(token: String) = settingsRepository.updateApiToken(token)
    fun updateSegmentDuration(seconds: Int) = settingsRepository.updateSegmentDuration(seconds)
    fun updateAudioBitrate(bitrateKbps: Int) = settingsRepository.updateAudioBitrate(bitrateKbps)
    fun updateDeleteAfterUpload(delete: Boolean) = settingsRepository.updateDeleteAfterUpload(delete)
    fun updateMaxQueueSize(sizeMb: Int) = settingsRepository.updateMaxQueueSize(sizeMb)

    fun testConnection() {
        viewModelScope.launch {
            _connectionTestState.value = ConnectionTestState.Testing
            val result = uploadRepository.testConnection()
            result.fold(
                onSuccess = { _connectionTestState.value = ConnectionTestState.Success(it) },
                onFailure = { _connectionTestState.value = ConnectionTestState.Error(it.message ?: "Failed") }
            )
        }
    }

    fun clearLocalHistory() {
        viewModelScope.launch {
            recordingRepository.clearAllRecordings(deleteAudioFiles = true)
        }
    }

    fun getAvailableStorageFormatted(): String {
        val bytes = settingsRepository.getAvailableStorageBytes()
        if (bytes < 0) return "Unknown"
        return String.format(Locale.US, "%.2f GB", bytes / (1024.0 * 1024.0 * 1024.0))
    }

    class Factory(
        private val settingsRepository: SettingsRepository,
        private val uploadRepository: UploadRepository,
        private val recordingRepository: RecordingRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            return SettingsViewModel(settingsRepository, uploadRepository, recordingRepository) as T
        }
    }
}

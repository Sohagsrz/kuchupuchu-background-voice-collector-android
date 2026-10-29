package com.kuchupuchu.audiorecorder.settings

import android.content.Context
import android.os.StatFs
import com.kuchupuchu.audiorecorder.security.TokenStorage
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import java.io.File

open class SettingsRepository(
    private val context: Context,
    private val tokenStorage: TokenStorage
) {
    private val prefs by lazy { context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE) }

    private val _settings by lazy { MutableStateFlow(loadSettings()) }
    open val settings: StateFlow<AppSettings> get() = _settings.asStateFlow()

    private fun loadSettings(): AppSettings {
        return AppSettings(
            serverBaseUrl = prefs.getString(KEY_BASE_URL, "http://10.0.2.2:8080/") ?: "http://10.0.2.2:8080/",
            apiToken = tokenStorage.apiToken,
            segmentDurationSeconds = prefs.getInt(KEY_SEGMENT_DURATION, 60).coerceIn(10, 600),
            audioBitrateKbps = prefs.getInt(KEY_BITRATE, 24).coerceIn(16, 128),
            deleteAfterUpload = prefs.getBoolean(KEY_DELETE_AFTER_UPLOAD, false),
            maxQueueSizeMb = prefs.getInt(KEY_MAX_QUEUE_SIZE, 500).coerceIn(50, 5000)
        )
    }

    fun updateServerBaseUrl(url: String) {
        val sanitized = if (!url.endsWith("/")) "$url/" else url
        prefs.edit().putString(KEY_BASE_URL, sanitized).apply()
        _settings.value = _settings.value.copy(serverBaseUrl = sanitized)
    }

    fun updateApiToken(token: String) {
        tokenStorage.apiToken = token
        _settings.value = _settings.value.copy(apiToken = token)
    }

    fun updateSegmentDuration(seconds: Int) {
        val valid = seconds.coerceIn(10, 600)
        prefs.edit().putInt(KEY_SEGMENT_DURATION, valid).apply()
        _settings.value = _settings.value.copy(segmentDurationSeconds = valid)
    }

    fun updateAudioBitrate(bitrateKbps: Int) {
        val valid = bitrateKbps.coerceIn(16, 128)
        prefs.edit().putInt(KEY_BITRATE, valid).apply()
        _settings.value = _settings.value.copy(audioBitrateKbps = valid)
    }

    fun updateDeleteAfterUpload(delete: Boolean) {
        prefs.edit().putBoolean(KEY_DELETE_AFTER_UPLOAD, delete).apply()
        _settings.value = _settings.value.copy(deleteAfterUpload = delete)
    }

    fun updateMaxQueueSize(sizeMb: Int) {
        val valid = sizeMb.coerceIn(50, 5000)
        prefs.edit().putInt(KEY_MAX_QUEUE_SIZE, valid).apply()
        _settings.value = _settings.value.copy(maxQueueSizeMb = valid)
    }

    fun getAvailableStorageBytes(): Long {
        return try {
            val stat = StatFs(context.filesDir.path)
            stat.availableBlocksLong * stat.blockSizeLong
        } catch (e: Exception) {
            -1L
        }
    }

    fun isStorageCriticallyLow(thresholdBytes: Long = 50 * 1024 * 1024L): Boolean {
        val available = getAvailableStorageBytes()
        return available in 0..thresholdBytes
    }

    companion object {
        private const val PREFS_NAME = "kuchupuchu_app_settings"
        private const val KEY_BASE_URL = "key_base_url"
        private const val KEY_SEGMENT_DURATION = "key_segment_duration"
        private const val KEY_BITRATE = "key_bitrate"
        private const val KEY_DELETE_AFTER_UPLOAD = "key_delete_after_upload"
        private const val KEY_MAX_QUEUE_SIZE = "key_max_queue_size"
    }
}

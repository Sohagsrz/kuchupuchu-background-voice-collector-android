package com.kuchupuchu.audiorecorder.upload

import android.util.Log
import com.kuchupuchu.audiorecorder.data.database.RecordingEntity
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus
import com.kuchupuchu.audiorecorder.data.repository.RecordingRepository
import com.kuchupuchu.audiorecorder.settings.SettingsRepository
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone
import java.util.concurrent.TimeUnit

sealed interface UploadResult {
    data class Success(val segmentId: String, val remoteId: String) : UploadResult
    data class RetryableError(val message: String, val retryAfterSeconds: Long? = null) : UploadResult
    data class PermanentError(val message: String) : UploadResult
}

class UploadRepository(
    private val recordingRepository: RecordingRepository,
    private val settingsRepository: SettingsRepository
) {
    private val moshi = Moshi.Builder()
        .addLast(KotlinJsonAdapterFactory())
        .build()

    private val okHttpClient = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(60, TimeUnit.SECONDS)
        .writeTimeout(60, TimeUnit.SECONDS)
        .addInterceptor(HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BASIC
            redactHeader("Authorization")
        })
        .build()

    private fun getApi(baseUrl: String): RecordingApi {
        val sanitizedUrl = if (baseUrl.endsWith("/")) baseUrl else "$baseUrl/"
        return Retrofit.Builder()
            .baseUrl(sanitizedUrl)
            .client(okHttpClient)
            .addConverterFactory(MoshiConverterFactory.create(moshi))
            .build()
            .create(RecordingApi::class.java)
    }

    suspend fun testConnection(): Result<String> = withContext(Dispatchers.IO) {
        try {
            val settings = settingsRepository.settings.value
            val api = getApi(settings.serverBaseUrl)
            val authHeader = if (settings.apiToken.isNotBlank()) "Bearer ${settings.apiToken}" else null

            val response = api.checkHealth(authHeader)
            if (response.isSuccessful) {
                val body = response.body()
                Result.success("Server connected! Status: ${body?.status ?: "OK"}")
            } else {
                Result.failure(Exception("Server returned HTTP ${response.code()}: ${response.message()}"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "Connection test failed", e)
            Result.failure(e)
        }
    }

    suspend fun uploadRecording(recording: RecordingEntity): UploadResult = withContext(Dispatchers.IO) {
        val file = File(recording.localPath)
        if (!file.exists() || file.length() == 0L) {
            recordingRepository.updateStatus(recording.id, RecordingStatus.CORRUPT, "Local file missing or empty")
            return@withContext UploadResult.PermanentError("Local file missing or empty")
        }

        val settings = settingsRepository.settings.value
        val api = getApi(settings.serverBaseUrl)
        val authHeader = if (settings.apiToken.isNotBlank()) "Bearer ${settings.apiToken}" else null

        val isoFormat = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'", Locale.US).apply {
            timeZone = TimeZone.getTimeZone("UTC")
        }
        val startedAtStr = isoFormat.format(Date(recording.startedAt))
        val endedAtStr = isoFormat.format(Date(recording.endedAt))

        val fileRequestBody = file.asRequestBody("audio/mp4".toMediaTypeOrNull())
        val filePart = MultipartBody.Part.createFormData("file", file.name, fileRequestBody)

        val textMediaType = "text/plain".toMediaTypeOrNull()
        val segmentIdBody = recording.id.toRequestBody(textMediaType)

        val model = android.os.Build.MODEL ?: "Android Device"
        val manufacturer = android.os.Build.MANUFACTURER ?: ""
        val deviceName = if (manufacturer.isNotBlank() && !model.startsWith(manufacturer, ignoreCase = true)) {
            "$manufacturer $model"
        } else {
            model
        }
        val deviceNameBody = deviceName.toRequestBody(textMediaType)

        val startedAtBody = startedAtStr.toRequestBody(textMediaType)
        val endedAtBody = endedAtStr.toRequestBody(textMediaType)
        val durationBody = recording.durationMs.toString().toRequestBody(textMediaType)
        val checksumBody = (recording.checksum ?: "").toRequestBody(textMediaType)

        try {
            recordingRepository.updateStatus(recording.id, RecordingStatus.UPLOADING)

            val response = api.uploadRecording(
                authorization = authHeader,
                file = filePart,
                segmentId = segmentIdBody,
                deviceName = deviceNameBody,
                startedAt = startedAtBody,
                endedAt = endedAtBody,
                durationMs = durationBody,
                checksum = checksumBody
            )

            if (response.isSuccessful) {
                val body = response.body()
                if (body != null && body.success) {
                    val remoteId = body.recordingId ?: recording.id
                    val uploadedAt = System.currentTimeMillis()
                    recordingRepository.markUploaded(recording.id, remoteId, uploadedAt)

                    // Check retention cleanup policy
                    if (settings.deleteAfterUpload) {
                        recordingRepository.cleanupUploadedFileIfPolicyAllows(recording.id, true)
                    }

                    UploadResult.Success(recording.id, remoteId)
                } else {
                    val errorMsg = body?.error ?: "Server returned success=false without error details"
                    recordingRepository.markFailed(recording.id, errorMsg, recording.retryCount + 1, null)
                    UploadResult.PermanentError(errorMsg)
                }
            } else {
                val code = response.code()
                val retryAfter = response.headers()["Retry-After"]?.toLongOrNull()

                when (code) {
                    401, 403 -> {
                        val msg = "Authentication failed (HTTP $code). Verify token."
                        recordingRepository.markFailed(recording.id, msg, recording.retryCount + 1, null)
                        UploadResult.PermanentError(msg)
                    }
                    413 -> {
                        val msg = "File too large for server (HTTP 413)"
                        recordingRepository.markFailed(recording.id, msg, recording.retryCount + 1, null)
                        UploadResult.PermanentError(msg)
                    }
                    408, 429, in 500..599 -> {
                        val msg = "Transient server error (HTTP $code)"
                        val nextRetry = System.currentTimeMillis() + ((retryAfter ?: calculateBackoffSeconds(recording.retryCount)) * 1000L)
                        recordingRepository.markFailed(recording.id, msg, recording.retryCount + 1, nextRetry)
                        UploadResult.RetryableError(msg, retryAfter)
                    }
                    else -> {
                        val msg = "Upload rejected by server (HTTP $code: ${response.message()})"
                        recordingRepository.markFailed(recording.id, msg, recording.retryCount + 1, null)
                        UploadResult.PermanentError(msg)
                    }
                }
            }
        } catch (e: com.squareup.moshi.JsonDataException) {
            Log.e(TAG, "Malformed server response JSON for segment ${recording.id}", e)
            val msg = "Malformed server response JSON: ${e.message}"
            recordingRepository.markFailed(recording.id, msg, recording.retryCount + 1, null)
            UploadResult.PermanentError(msg)
        } catch (e: com.squareup.moshi.JsonEncodingException) {
            Log.e(TAG, "Corrupt server response JSON for segment ${recording.id}", e)
            val msg = "Corrupt server response JSON: ${e.message}"
            recordingRepository.markFailed(recording.id, msg, recording.retryCount + 1, null)
            UploadResult.PermanentError(msg)
        } catch (e: Exception) {
            Log.e(TAG, "Network exception during upload for segment ${recording.id}", e)
            val msg = "Network failure: ${e.localizedMessage ?: "Unknown error"}"
            val nextRetry = System.currentTimeMillis() + (calculateBackoffSeconds(recording.retryCount) * 1000L)
            recordingRepository.markFailed(recording.id, msg, recording.retryCount + 1, nextRetry)
            UploadResult.RetryableError(msg)
        }
    }

    private fun calculateBackoffSeconds(retryCount: Int): Long {
        // Exponential backoff: 5s, 10s, 20s, 40s, max 300s + jitter
        val base = 5L * (1L shl retryCount.coerceIn(0, 6))
        val jitter = (0..5).random()
        return (base + jitter).coerceAtMost(300L)
    }

    companion object {
        private const val TAG = "UploadRepository"
    }
}

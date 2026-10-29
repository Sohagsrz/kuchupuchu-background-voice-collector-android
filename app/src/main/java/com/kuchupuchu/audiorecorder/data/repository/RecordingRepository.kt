package com.kuchupuchu.audiorecorder.data.repository

import com.kuchupuchu.audiorecorder.data.database.RecordingEntity
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus
import kotlinx.coroutines.flow.Flow

data class QueueSummary(
    val pendingCount: Int = 0,
    val uploadedCount: Int = 0,
    val failedCount: Int = 0,
    val pendingSizeBytes: Long = 0L
)

interface RecordingRepository {
    val latestRecordings: Flow<List<RecordingEntity>>
    val allRecordings: Flow<List<RecordingEntity>>
    val queueSummary: Flow<QueueSummary>

    suspend fun saveRecording(recording: RecordingEntity)
    suspend fun getRecording(id: String): RecordingEntity?
    suspend fun getPendingUploads(): List<RecordingEntity>
    suspend fun updateStatus(id: String, status: RecordingStatus, error: String? = null)
    suspend fun markUploaded(id: String, remoteId: String, uploadedAt: Long)
    suspend fun markFailed(id: String, error: String, retryCount: Int, nextRetryAt: Long?)
    suspend fun recoverInterruptedUploads(): Int
    suspend fun retryUpload(id: String)
    suspend fun retryAllFailed()
    suspend fun deleteRecording(id: String, deleteAudioFile: Boolean = true)
    suspend fun clearAllRecordings(deleteAudioFiles: Boolean = true)
    suspend fun cleanupUploadedFileIfPolicyAllows(id: String, deleteAfterUpload: Boolean)
}

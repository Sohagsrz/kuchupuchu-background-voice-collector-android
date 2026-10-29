package com.kuchupuchu.audiorecorder.data.repository

import com.kuchupuchu.audiorecorder.data.database.RecordingDao
import com.kuchupuchu.audiorecorder.data.database.RecordingEntity
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.combine
import java.io.File

class RecordingRepositoryImpl(
    private val recordingDao: RecordingDao
) : RecordingRepository {

    override val latestRecordings: Flow<List<RecordingEntity>> =
        recordingDao.getLatestRecordings(10)

    override val allRecordings: Flow<List<RecordingEntity>> =
        recordingDao.getAllRecordings()

    override val queueSummary: Flow<QueueSummary> = combine(
        recordingDao.getPendingCount(),
        recordingDao.getUploadedCount(),
        recordingDao.getFailedCount(),
        recordingDao.getPendingQueueSizeBytes()
    ) { pending, uploaded, failed, sizeBytes ->
        QueueSummary(
            pendingCount = pending,
            uploadedCount = uploaded,
            failedCount = failed,
            pendingSizeBytes = sizeBytes
        )
    }

    override suspend fun saveRecording(recording: RecordingEntity) {
        recordingDao.insert(recording)
    }

    override suspend fun getRecording(id: String): RecordingEntity? {
        return recordingDao.getById(id)
    }

    override suspend fun getPendingUploads(): List<RecordingEntity> {
        return recordingDao.getPendingUploads()
    }

    override suspend fun updateStatus(id: String, status: RecordingStatus, error: String?) {
        recordingDao.updateStatus(id, status, error)
    }

    override suspend fun markUploaded(id: String, remoteId: String, uploadedAt: Long) {
        recordingDao.markUploaded(id, remoteId, uploadedAt)
    }

    override suspend fun markFailed(id: String, error: String, retryCount: Int, nextRetryAt: Long?) {
        recordingDao.markFailed(id, error, retryCount, nextRetryAt)
    }

    override suspend fun recoverInterruptedUploads(): Int {
        return recordingDao.recoverInterruptedUploads()
    }

    override suspend fun retryUpload(id: String) {
        recordingDao.retryUpload(id)
    }

    override suspend fun retryAllFailed() {
        recordingDao.retryAllFailed()
    }

    override suspend fun deleteRecording(id: String, deleteAudioFile: Boolean) {
        val record = recordingDao.getById(id)
        if (record != null && deleteAudioFile) {
            runCatching {
                val file = File(record.localPath)
                if (file.exists()) {
                    file.delete()
                }
            }
        }
        recordingDao.deleteById(id)
    }

    override suspend fun clearAllRecordings(deleteAudioFiles: Boolean) {
        if (deleteAudioFiles) {
            val records = recordingDao.getAllList()
            for (record in records) {
                runCatching {
                    val file = File(record.localPath)
                    if (file.exists()) {
                        file.delete()
                    }
                }
            }
        }
        recordingDao.deleteAll()
    }

    override suspend fun cleanupUploadedFileIfPolicyAllows(id: String, deleteAfterUpload: Boolean) {
        if (!deleteAfterUpload) return
        val record = recordingDao.getById(id) ?: return
        if (record.status == RecordingStatus.UPLOADED) {
            runCatching {
                val file = File(record.localPath)
                if (file.exists()) {
                    file.delete()
                }
            }
        }
    }
}

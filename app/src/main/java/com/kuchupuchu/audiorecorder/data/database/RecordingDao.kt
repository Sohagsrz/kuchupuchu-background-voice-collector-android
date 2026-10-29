package com.kuchupuchu.audiorecorder.data.database

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Update
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus
import kotlinx.coroutines.flow.Flow

@Dao
interface RecordingDao {

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(recording: RecordingEntity)

    @Update
    suspend fun update(recording: RecordingEntity)

    @Query("SELECT * FROM recordings WHERE id = :id")
    suspend fun getById(id: String): RecordingEntity?

    @Query("SELECT * FROM recordings ORDER BY startedAt DESC LIMIT :limit")
    fun getLatestRecordings(limit: Int = 10): Flow<List<RecordingEntity>>

    @Query("SELECT * FROM recordings ORDER BY startedAt DESC")
    fun getAllRecordings(): Flow<List<RecordingEntity>>

    @Query("SELECT * FROM recordings WHERE status IN ('READY', 'QUEUED') ORDER BY startedAt ASC")
    suspend fun getPendingUploads(): List<RecordingEntity>

    @Query("SELECT * FROM recordings WHERE status = 'FAILED' ORDER BY startedAt ASC")
    suspend fun getFailedUploads(): List<RecordingEntity>

    @Query("UPDATE recordings SET status = :status, lastError = :error WHERE id = :id")
    suspend fun updateStatus(id: String, status: RecordingStatus, error: String? = null)

    @Query("UPDATE recordings SET status = 'UPLOADED', remoteId = :remoteId, uploadedAt = :uploadedAt, lastError = null WHERE id = :id")
    suspend fun markUploaded(id: String, remoteId: String, uploadedAt: Long)

    @Query("UPDATE recordings SET status = 'FAILED', lastError = :error, retryCount = :retryCount, nextRetryAt = :nextRetryAt WHERE id = :id")
    suspend fun markFailed(id: String, error: String, retryCount: Int, nextRetryAt: Long?)

    @Query("UPDATE recordings SET status = 'QUEUED' WHERE status = 'UPLOADING'")
    suspend fun recoverInterruptedUploads(): Int

    @Query("UPDATE recordings SET status = 'QUEUED', retryCount = 0, nextRetryAt = null, lastError = null WHERE id = :id")
    suspend fun retryUpload(id: String)

    @Query("UPDATE recordings SET status = 'QUEUED', retryCount = 0, nextRetryAt = null, lastError = null WHERE status = 'FAILED'")
    suspend fun retryAllFailed(): Int

    @Query("SELECT COUNT(*) FROM recordings WHERE status IN ('READY', 'QUEUED', 'UPLOADING')")
    fun getPendingCount(): Flow<Int>

    @Query("SELECT COUNT(*) FROM recordings WHERE status = 'UPLOADED'")
    fun getUploadedCount(): Flow<Int>

    @Query("SELECT COUNT(*) FROM recordings WHERE status = 'FAILED'")
    fun getFailedCount(): Flow<Int>

    @Query("SELECT COALESCE(SUM(sizeBytes), 0) FROM recordings WHERE status IN ('READY', 'QUEUED', 'UPLOADING')")
    fun getPendingQueueSizeBytes(): Flow<Long>

    @Query("DELETE FROM recordings WHERE id = :id")
    suspend fun deleteById(id: String)

    @Query("DELETE FROM recordings")
    suspend fun deleteAll()

    @Query("SELECT * FROM recordings")
    suspend fun getAllList(): List<RecordingEntity>
}

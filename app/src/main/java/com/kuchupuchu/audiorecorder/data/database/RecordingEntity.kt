package com.kuchupuchu.audiorecorder.data.database

import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus

@Entity(
    tableName = "recordings",
    indices = [
        Index(value = ["startedAt"]),
        Index(value = ["status"])
    ]
)
data class RecordingEntity(
    @PrimaryKey
    val id: String,
    val filename: String,
    val localPath: String,
    val startedAt: Long,
    val endedAt: Long,
    val durationMs: Long,
    val sizeBytes: Long,
    val mimeType: String = "audio/mp4",
    val status: RecordingStatus = RecordingStatus.QUEUED,
    val retryCount: Int = 0,
    val nextRetryAt: Long? = null,
    val remoteId: String? = null,
    val uploadedAt: Long? = null,
    val checksum: String? = null,
    val lastError: String? = null
)

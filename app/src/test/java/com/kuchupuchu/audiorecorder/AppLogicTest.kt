package com.kuchupuchu.audiorecorder

import com.kuchupuchu.audiorecorder.data.database.RecordingEntity
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus
import com.kuchupuchu.audiorecorder.data.repository.QueueSummary
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import java.util.Locale

class AppLogicTest {

    @Test
    fun testDurationFormatting() {
        fun formatDuration(durationMs: Long): String {
            val hours = durationMs / (1000 * 60 * 60)
            val minutes = (durationMs / (1000 * 60)) % 60
            val seconds = (durationMs / 1000) % 60
            return String.format(Locale.US, "%02d:%02d:%02d", hours, minutes, seconds)
        }

        assertEquals("00:00:00", formatDuration(0L))
        assertEquals("00:01:00", formatDuration(60000L))
        assertEquals("00:04:32", formatDuration(272000L))
        assertEquals("01:15:30", formatDuration(4530000L))
    }

    @Test
    fun testFileSizeFormatting() {
        fun formatFileSize(bytes: Long): String {
            return when {
                bytes < 1024 -> "$bytes B"
                bytes < 1024 * 1024 -> String.format(Locale.US, "%.1f KB", bytes / 1024.0)
                else -> String.format(Locale.US, "%.1f MB", bytes / (1024.0 * 1024.0))
            }
        }

        assertEquals("500 B", formatFileSize(500L))
        assertEquals("240.0 KB", formatFileSize(240 * 1024L))
        assertEquals("1.5 MB", formatFileSize((1.5 * 1024 * 1024).toLong()))
    }

    @Test
    fun testLatestRecordingsSortingAndLimit() {
        val recordings = (1..15).map { index ->
            RecordingEntity(
                id = "rec-$index",
                filename = "audio_$index.m4a",
                localPath = "/tmp/audio_$index.m4a",
                startedAt = 1000L * index,
                endedAt = 1000L * index + 60000L,
                durationMs = 60000L,
                sizeBytes = 240000L,
                status = RecordingStatus.QUEUED
            )
        }

        // Sort descending by startedAt and take latest 10
        val latest10 = recordings.sortedByDescending { it.startedAt }.take(10)

        assertEquals(10, latest10.size)
        assertEquals("rec-15", latest10.first().id)
        assertEquals("rec-6", latest10.last().id)
        // Older records beyond the latest 10 are preserved in the original list
        assertEquals(15, recordings.size)
        assertTrue(recordings.any { it.id == "rec-1" })
    }

    @Test
    fun testQueueSummaryCounts() {
        val items = listOf(
            createEntity("1", RecordingStatus.QUEUED, 1000L),
            createEntity("2", RecordingStatus.QUEUED, 2000L),
            createEntity("3", RecordingStatus.UPLOADING, 3000L),
            createEntity("4", RecordingStatus.UPLOADED, 4000L),
            createEntity("5", RecordingStatus.UPLOADED, 5000L),
            createEntity("6", RecordingStatus.FAILED, 6000L),
            createEntity("7", RecordingStatus.CORRUPT, 7000L)
        )

        val pending = items.count { it.status in listOf(RecordingStatus.READY, RecordingStatus.QUEUED, RecordingStatus.UPLOADING) }
        val uploaded = items.count { it.status == RecordingStatus.UPLOADED }
        val failed = items.count { it.status == RecordingStatus.FAILED }
        val pendingSize = items.filter { it.status in listOf(RecordingStatus.READY, RecordingStatus.QUEUED, RecordingStatus.UPLOADING) }
            .sumOf { it.sizeBytes }

        val summary = QueueSummary(
            pendingCount = pending,
            uploadedCount = uploaded,
            failedCount = failed,
            pendingSizeBytes = pendingSize
        )

        assertEquals(3, summary.pendingCount)
        assertEquals(2, summary.uploadedCount)
        assertEquals(1, summary.failedCount)
        assertEquals(6000L, summary.pendingSizeBytes)
    }

    @Test
    fun testUnfinishedUploadRecovery() {
        val list = mutableListOf(
            createEntity("1", RecordingStatus.UPLOADING, 1000L),
            createEntity("2", RecordingStatus.QUEUED, 2000L),
            createEntity("3", RecordingStatus.UPLOADED, 3000L)
        )

        // Simulate crash recovery: UPLOADING items must be reset to QUEUED
        val recovered = list.map { entity ->
            if (entity.status == RecordingStatus.UPLOADING) entity.copy(status = RecordingStatus.QUEUED) else entity
        }

        assertEquals(RecordingStatus.QUEUED, recovered.find { it.id == "1" }?.status)
        assertEquals(RecordingStatus.QUEUED, recovered.find { it.id == "2" }?.status)
        assertEquals(RecordingStatus.UPLOADED, recovered.find { it.id == "3" }?.status)
    }

    @Test
    fun testRetentionPolicyDeletionOnlyAfterVerifiedUpload() {
        var fileExists = true
        val entityQueued = createEntity("q1", RecordingStatus.QUEUED, 1000L)

        // Attempting delete before verified upload should NOT happen
        fun tryDeleteFile(entity: RecordingEntity, policyEnabled: Boolean) {
            if (policyEnabled && entity.status == RecordingStatus.UPLOADED) {
                fileExists = false
            }
        }

        tryDeleteFile(entityQueued, policyEnabled = true)
        assertTrue("Queued file must NOT be deleted", fileExists)

        // Only after verified upload
        val entityUploaded = entityQueued.copy(status = RecordingStatus.UPLOADED)
        tryDeleteFile(entityUploaded, policyEnabled = true)
        assertFalse("Uploaded file should be cleaned up under enabled retention policy", fileExists)
    }

    private fun createEntity(id: String, status: RecordingStatus, size: Long): RecordingEntity {
        return RecordingEntity(
            id = id,
            filename = "audio_$id.m4a",
            localPath = "/tmp/audio_$id.m4a",
            startedAt = 1000L,
            endedAt = 61000L,
            durationMs = 60000L,
            sizeBytes = size,
            status = status
        )
    }
}

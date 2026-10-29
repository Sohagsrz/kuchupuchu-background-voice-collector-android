package com.kuchupuchu.audiorecorder

import android.app.Application
import android.util.Log
import com.kuchupuchu.audiorecorder.data.database.RecordingEntity
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus
import com.kuchupuchu.audiorecorder.di.AppContainer
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import java.io.File

class KuchuPuchuApp : Application() {

    lateinit var container: AppContainer
        private set

    private val applicationScope = CoroutineScope(Dispatchers.IO + SupervisorJob())

    override fun onCreate() {
        super.onCreate()
        container = AppContainer(this)

        applicationScope.launch {
            recoverUnfinishedRecordings()
        }
    }

    private suspend fun recoverUnfinishedRecordings() {
        try {
            val recoveredCount = container.recordingRepository.recoverInterruptedUploads()
            if (recoveredCount > 0) {
                Log.i(TAG, "Recovered $recoveredCount interrupted uploads back to QUEUED")
            }

            // Inspect recording files in folder for orphan recovery or corrupt detection
            val dir = container.segmentManager.recordingsDir
            val files = dir.listFiles { file -> file.extension == "m4a" } ?: emptyArray()

            for (file in files) {
                val filename = file.name
                val existing = container.recordingRepository.latestRecordings
                // If not in database, check if valid and register
                val idFromFilename = filename.substringAfterLast("_", "").substringBefore(".m4a")
                if (idFromFilename.isNotEmpty()) {
                    val entity = container.recordingRepository.getRecording(idFromFilename)
                    if (entity == null) {
                        val isValid = container.segmentManager.validateAudioFile(file)
                        val now = System.currentTimeMillis()
                        val newEntity = RecordingEntity(
                            id = idFromFilename,
                            filename = filename,
                            localPath = file.absolutePath,
                            startedAt = file.lastModified(),
                            endedAt = now,
                            durationMs = 60000L,
                            sizeBytes = file.length(),
                            status = if (isValid) RecordingStatus.QUEUED else RecordingStatus.CORRUPT,
                            checksum = if (isValid) container.segmentManager.calculateSha256(file) else null,
                            lastError = if (isValid) null else "Corrupt audio header on recovery"
                        )
                        container.recordingRepository.saveRecording(newEntity)
                        Log.i(TAG, "Registered recovered orphaned file: $filename as ${newEntity.status}")
                    }
                }
            }

            // Trigger upload work for any pending items
            container.uploadScheduler.scheduleUploadWork()
        } catch (e: Exception) {
            Log.e(TAG, "Error during unfinished recording recovery", e)
        }
    }

    companion object {
        private const val TAG = "KuchuPuchuApp"
    }
}

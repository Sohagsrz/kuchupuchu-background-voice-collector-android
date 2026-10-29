package com.kuchupuchu.audiorecorder.upload

import android.content.Context
import android.util.Log
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.kuchupuchu.audiorecorder.KuchuPuchuApp

class UploadWorker(
    appContext: Context,
    workerParams: WorkerParameters
) : CoroutineWorker(appContext, workerParams) {

    override suspend fun doWork(): Result {
        val app = applicationContext as KuchuPuchuApp
        val recordingRepository = app.container.recordingRepository
        val uploadRepository = app.container.uploadRepository

        Log.d(TAG, "Starting UploadWorker execution")

        // First recover any interrupted uploads left from an abrupt process termination
        recordingRepository.recoverInterruptedUploads()

        val pending = recordingRepository.getPendingUploads()
        if (pending.isEmpty()) {
            Log.d(TAG, "No pending uploads found")
            return Result.success()
        }

        var shouldRetry = false

        for (recording in pending) {
            if (isStopped) {
                Log.d(TAG, "UploadWorker was stopped/cancelled")
                return Result.retry()
            }

            // Skip items with a future retry timestamp
            val now = System.currentTimeMillis()
            if (recording.nextRetryAt != null && recording.nextRetryAt > now) {
                Log.d(TAG, "Skipping ${recording.id} until ${recording.nextRetryAt}")
                continue
            }

            Log.d(TAG, "Uploading segment: ${recording.id}")
            when (val result = uploadRepository.uploadRecording(recording)) {
                is UploadResult.Success -> {
                    Log.d(TAG, "Uploaded successfully: ${recording.id}")
                }
                is UploadResult.PermanentError -> {
                    Log.w(TAG, "Permanent failure for segment ${recording.id}: ${result.message}")
                    // Continue with other items
                }
                is UploadResult.RetryableError -> {
                    Log.w(TAG, "Retryable failure for segment ${recording.id}: ${result.message}")
                    shouldRetry = true
                    break
                }
            }
        }

        return if (shouldRetry) Result.retry() else Result.success()
    }

    companion object {
        private const val TAG = "UploadWorker"
    }
}

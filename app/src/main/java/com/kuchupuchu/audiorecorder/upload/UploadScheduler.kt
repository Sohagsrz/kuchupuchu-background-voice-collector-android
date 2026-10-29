package com.kuchupuchu.audiorecorder.upload

import android.content.Context
import androidx.work.BackoffPolicy
import androidx.work.Constraints
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkManager
import java.util.concurrent.TimeUnit

class UploadScheduler(private val context: Context) {

    private val workManager by lazy { WorkManager.getInstance(context) }

    fun scheduleUploadWork(forceReplace: Boolean = false) {
        val constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()

        val uploadRequest = OneTimeWorkRequestBuilder<UploadWorker>()
            .setConstraints(constraints)
            .setBackoffCriteria(
                BackoffPolicy.EXPONENTIAL,
                10,
                TimeUnit.SECONDS
            )
            .addTag(TAG_UPLOAD_WORK)
            .build()

        val policy = if (forceReplace) {
            ExistingWorkPolicy.REPLACE
        } else {
            ExistingWorkPolicy.KEEP
        }

        workManager.enqueueUniqueWork(
            UNIQUE_WORK_NAME,
            policy,
            uploadRequest
        )
    }

    fun cancelAllUploads() {
        workManager.cancelUniqueWork(UNIQUE_WORK_NAME)
    }

    companion object {
        const val UNIQUE_WORK_NAME = "kuchupuchu_upload_work"
        const val TAG_UPLOAD_WORK = "tag_kuchupuchu_upload"
    }
}

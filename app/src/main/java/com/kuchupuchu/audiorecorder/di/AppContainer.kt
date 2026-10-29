package com.kuchupuchu.audiorecorder.di

import android.content.Context
import com.kuchupuchu.audiorecorder.data.database.AppDatabase
import com.kuchupuchu.audiorecorder.data.repository.RecordingRepository
import com.kuchupuchu.audiorecorder.data.repository.RecordingRepositoryImpl
import com.kuchupuchu.audiorecorder.recording.SegmentManager
import com.kuchupuchu.audiorecorder.security.TokenStorage
import com.kuchupuchu.audiorecorder.settings.SettingsRepository
import com.kuchupuchu.audiorecorder.upload.UploadRepository
import com.kuchupuchu.audiorecorder.upload.UploadScheduler

class AppContainer(private val context: Context) {

    val database: AppDatabase by lazy {
        AppDatabase.getInstance(context)
    }

    val recordingRepository: RecordingRepository by lazy {
        RecordingRepositoryImpl(database.recordingDao())
    }

    val tokenStorage: TokenStorage by lazy {
        TokenStorage(context)
    }

    val settingsRepository: SettingsRepository by lazy {
        SettingsRepository(context, tokenStorage)
    }

    val segmentManager: SegmentManager by lazy {
        SegmentManager(context)
    }

    val uploadRepository: UploadRepository by lazy {
        UploadRepository(recordingRepository, settingsRepository)
    }

    val uploadScheduler: UploadScheduler by lazy {
        UploadScheduler(context)
    }
}

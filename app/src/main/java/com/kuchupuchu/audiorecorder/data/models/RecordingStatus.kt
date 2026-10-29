package com.kuchupuchu.audiorecorder.data.models

enum class RecordingStatus {
    READY,
    QUEUED,
    UPLOADING,
    UPLOADED,
    FAILED,
    CORRUPT;

    val displayLabel: String
        get() = when (this) {
            READY -> "Ready"
            QUEUED -> "Queued"
            UPLOADING -> "Uploading"
            UPLOADED -> "Uploaded"
            FAILED -> "Failed"
            CORRUPT -> "Corrupt"
        }
}

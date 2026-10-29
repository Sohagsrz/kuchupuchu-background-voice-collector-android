package com.kuchupuchu.audiorecorder.settings

data class AppSettings(
    val serverBaseUrl: String = "http://10.0.2.2:8080/",
    val apiToken: String = "",
    val segmentDurationSeconds: Int = 60,
    val audioBitrateKbps: Int = 24,
    val deleteAfterUpload: Boolean = false,
    val maxQueueSizeMb: Int = 500
)

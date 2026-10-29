package com.kuchupuchu.audiorecorder.settings

data class AppSettings(
    val serverBaseUrl: String = "https://kuchupuchu.pricehatbd.com/",
    val apiToken: String = "",
    val segmentDurationSeconds: Int = 60,
    val audioBitrateKbps: Int = 24,
    val deleteAfterUpload: Boolean = false,
    val maxQueueSizeMb: Int = 500
)

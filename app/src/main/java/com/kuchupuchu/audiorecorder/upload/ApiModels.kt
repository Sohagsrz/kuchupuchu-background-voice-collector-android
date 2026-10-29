package com.kuchupuchu.audiorecorder.upload

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class UploadResponse(
    @Json(name = "success") val success: Boolean = false,
    @Json(name = "segment_id") val segmentId: String? = null,
    @Json(name = "recording_id") val recordingId: String? = null,
    @Json(name = "error") val error: String? = null
)

@JsonClass(generateAdapter = true)
data class HealthResponse(
    @Json(name = "status") val status: String? = null,
    @Json(name = "version") val version: String? = null
)

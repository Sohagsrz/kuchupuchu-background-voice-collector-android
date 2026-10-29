package com.kuchupuchu.audiorecorder.upload

import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.Response
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part

interface RecordingApi {

    @Multipart
    @POST("api/v1/recordings")
    suspend fun uploadRecording(
        @Header("Authorization") authorization: String?,
        @Part file: MultipartBody.Part,
        @Part("segment_id") segmentId: RequestBody,
        @Part("started_at") startedAt: RequestBody,
        @Part("ended_at") endedAt: RequestBody,
        @Part("duration_ms") durationMs: RequestBody,
        @Part("checksum") checksum: RequestBody
    ): Response<UploadResponse>

    @GET("api/v1/health")
    suspend fun checkHealth(
        @Header("Authorization") authorization: String?
    ): Response<HealthResponse>
}

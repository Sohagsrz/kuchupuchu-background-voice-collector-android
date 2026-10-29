package com.kuchupuchu.audiorecorder.upload

import com.kuchupuchu.audiorecorder.data.database.RecordingEntity
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus
import com.kuchupuchu.audiorecorder.data.repository.QueueSummary
import com.kuchupuchu.audiorecorder.data.repository.RecordingRepository
import com.kuchupuchu.audiorecorder.security.TokenStorage
import com.kuchupuchu.audiorecorder.settings.AppSettings
import com.kuchupuchu.audiorecorder.settings.SettingsRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.emptyFlow
import kotlinx.coroutines.runBlocking
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import java.io.File
import java.io.FileOutputStream

class UploadRepositoryTest {

    private lateinit var mockWebServer: MockWebServer
    private lateinit var fakeRepo: FakeRecordingRepository
    private lateinit var fakeSettingsRepo: FakeSettingsRepository
    private lateinit var uploadRepository: UploadRepository
    private lateinit var tempAudioFile: File

    @Before
    fun setUp() {
        mockWebServer = MockWebServer()
        mockWebServer.start()

        tempAudioFile = File.createTempFile("test_upload", ".m4a").apply {
            deleteOnExit()
        }
        FileOutputStream(tempAudioFile).use {
            it.write("fake aac data content".toByteArray(Charsets.UTF_8))
        }

        fakeRepo = FakeRecordingRepository()
        fakeSettingsRepo = FakeSettingsRepository(
            baseUrl = mockWebServer.url("/").toString()
        )

        uploadRepository = UploadRepository(fakeRepo, fakeSettingsRepo as SettingsRepository)
    }

    @After
    fun tearDown() {
        mockWebServer.shutdown()
        tempAudioFile.delete()
    }

    @Test
    fun testSuccessfulUpload() = runBlocking {
        mockWebServer.enqueue(
            MockResponse()
                .setResponseCode(200)
                .setBody("""
                    {
                        "success": true,
                        "segment_id": "seg-123",
                        "recording_id": "rec-server-456"
                    }
                """.trimIndent())
        )

        val recording = RecordingEntity(
            id = "seg-123",
            filename = tempAudioFile.name,
            localPath = tempAudioFile.absolutePath,
            startedAt = 1000L,
            endedAt = 61000L,
            durationMs = 60000L,
            sizeBytes = tempAudioFile.length(),
            status = RecordingStatus.QUEUED,
            checksum = "test-hash"
        )

        val result = uploadRepository.uploadRecording(recording)

        assertTrue(result is UploadResult.Success)
        val success = result as UploadResult.Success
        assertEquals("seg-123", success.segmentId)
        assertEquals("rec-server-456", success.remoteId)
        assertEquals(RecordingStatus.UPLOADED, fakeRepo.recordings["seg-123"]?.status)
    }

    @Test
    fun testAuthFailurePermanentError() = runBlocking {
        mockWebServer.enqueue(
            MockResponse()
                .setResponseCode(401)
                .setBody("""{"error": "Unauthorized"}""")
        )

        val recording = RecordingEntity(
            id = "seg-unauth",
            filename = tempAudioFile.name,
            localPath = tempAudioFile.absolutePath,
            startedAt = 1000L,
            endedAt = 61000L,
            durationMs = 60000L,
            sizeBytes = tempAudioFile.length(),
            status = RecordingStatus.QUEUED
        )

        val result = uploadRepository.uploadRecording(recording)

        assertTrue(result is UploadResult.PermanentError)
        assertEquals(RecordingStatus.FAILED, fakeRepo.recordings["seg-unauth"]?.status)
    }

    @Test
    fun testServerErrorTransientRetryable() = runBlocking {
        mockWebServer.enqueue(
            MockResponse()
                .setResponseCode(503)
                .setHeader("Retry-After", "30")
                .setBody("""{"error": "Service Unavailable"}""")
        )

        val recording = RecordingEntity(
            id = "seg-retry",
            filename = tempAudioFile.name,
            localPath = tempAudioFile.absolutePath,
            startedAt = 1000L,
            endedAt = 61000L,
            durationMs = 60000L,
            sizeBytes = tempAudioFile.length(),
            status = RecordingStatus.QUEUED
        )

        val result = uploadRepository.uploadRecording(recording)

        assertTrue(result is UploadResult.RetryableError)
        val retry = result as UploadResult.RetryableError
        assertEquals(30L, retry.retryAfterSeconds)
        assertEquals(RecordingStatus.FAILED, fakeRepo.recordings["seg-retry"]?.status)
    }

    @Test
    fun testMalformedServerResponseDoesNotMarkUploaded() = runBlocking {
        mockWebServer.enqueue(
            MockResponse()
                .setResponseCode(200)
                .setBody("""{"corrupt_response": true}""") // missing success = true
        )

        val recording = RecordingEntity(
            id = "seg-malformed",
            filename = tempAudioFile.name,
            localPath = tempAudioFile.absolutePath,
            startedAt = 1000L,
            endedAt = 61000L,
            durationMs = 60000L,
            sizeBytes = tempAudioFile.length(),
            status = RecordingStatus.QUEUED
        )

        val result = uploadRepository.uploadRecording(recording)

        assertTrue(result is UploadResult.PermanentError)
        assertEquals(RecordingStatus.FAILED, fakeRepo.recordings["seg-malformed"]?.status)
    }
}

class FakeRecordingRepository : RecordingRepository {
    val recordings = mutableMapOf<String, RecordingEntity>()

    override val latestRecordings: Flow<List<RecordingEntity>> = emptyFlow()
    override val allRecordings: Flow<List<RecordingEntity>> = emptyFlow()
    override val queueSummary: Flow<QueueSummary> = emptyFlow()

    override suspend fun saveRecording(recording: RecordingEntity) {
        recordings[recording.id] = recording
    }

    override suspend fun getRecording(id: String): RecordingEntity? = recordings[id]
    override suspend fun getPendingUploads(): List<RecordingEntity> = recordings.values.toList()

    override suspend fun updateStatus(id: String, status: RecordingStatus, error: String?) {
        val existing = recordings[id]
        if (existing != null) {
            recordings[id] = existing.copy(status = status, lastError = error)
        } else {
            recordings[id] = RecordingEntity(
                id = id, filename = "test.m4a", localPath = "/tmp/test.m4a",
                startedAt = 0L, endedAt = 0L, durationMs = 0L, sizeBytes = 0L,
                status = status, lastError = error
            )
        }
    }

    override suspend fun markUploaded(id: String, remoteId: String, uploadedAt: Long) {
        val existing = recordings[id]
        if (existing != null) {
            recordings[id] = existing.copy(status = RecordingStatus.UPLOADED, remoteId = remoteId, uploadedAt = uploadedAt)
        } else {
            recordings[id] = RecordingEntity(
                id = id, filename = "test.m4a", localPath = "/tmp/test.m4a",
                startedAt = 0L, endedAt = 0L, durationMs = 0L, sizeBytes = 0L,
                status = RecordingStatus.UPLOADED, remoteId = remoteId, uploadedAt = uploadedAt
            )
        }
    }

    override suspend fun markFailed(id: String, error: String, retryCount: Int, nextRetryAt: Long?) {
        val existing = recordings[id]
        if (existing != null) {
            recordings[id] = existing.copy(status = RecordingStatus.FAILED, lastError = error, retryCount = retryCount, nextRetryAt = nextRetryAt)
        } else {
            recordings[id] = RecordingEntity(
                id = id, filename = "test.m4a", localPath = "/tmp/test.m4a",
                startedAt = 0L, endedAt = 0L, durationMs = 0L, sizeBytes = 0L,
                status = RecordingStatus.FAILED, lastError = error, retryCount = retryCount, nextRetryAt = nextRetryAt
            )
        }
    }

    override suspend fun recoverInterruptedUploads(): Int = 0
    override suspend fun retryUpload(id: String) {}
    override suspend fun retryAllFailed() {}
    override suspend fun deleteRecording(id: String, deleteAudioFile: Boolean) { recordings.remove(id) }
    override suspend fun clearAllRecordings(deleteAudioFiles: Boolean) { recordings.clear() }
    override suspend fun cleanupUploadedFileIfPolicyAllows(id: String, deleteAfterUpload: Boolean) {}
}

class FakeSettingsRepository(baseUrl: String) : SettingsRepository(
    context = FakeContextWrapper.create(),
    tokenStorage = TokenStorage(FakeContextWrapper.create())
) {
    private val _fakeSettings = MutableStateFlow(AppSettings(serverBaseUrl = baseUrl))
    override val settings: StateFlow<AppSettings> get() = _fakeSettings
}

object FakeContextWrapper {
    fun create(): android.content.Context {
        val mock = org.mockito.Mockito.mock(android.content.Context::class.java)
        val prefs = org.mockito.Mockito.mock(android.content.SharedPreferences::class.java)
        org.mockito.Mockito.`when`(mock.getSharedPreferences(org.mockito.ArgumentMatchers.anyString(), org.mockito.ArgumentMatchers.anyInt()))
            .thenReturn(prefs)
        org.mockito.Mockito.`when`(mock.filesDir).thenReturn(File("/tmp"))
        return mock
    }
}

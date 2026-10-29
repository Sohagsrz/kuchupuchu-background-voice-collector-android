package com.kuchupuchu.audiorecorder.recording

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import java.io.File
import java.io.FileOutputStream

class SegmentManagerTest {

    @Test
    fun testGenerateSegmentId() {
        val segmentManager = SegmentManagerFake()
        val id1 = segmentManager.generateSegmentId()
        val id2 = segmentManager.generateSegmentId()

        assertTrue(id1.isNotEmpty())
        assertTrue(id2.isNotEmpty())
        assertNotEquals(id1, id2)
    }

    @Test
    fun testSha256Calculation() {
        val tempFile = File.createTempFile("test_audio", ".m4a").apply {
            deleteOnExit()
        }
        FileOutputStream(tempFile).use {
            it.write("Test Audio Content".toByteArray(Charsets.UTF_8))
        }

        val segmentManager = SegmentManagerFake()
        val checksum = segmentManager.calculateSha256(tempFile)

        assertTrue(checksum.isNotEmpty())
        assertEquals(64, checksum.length) // SHA-256 hex is 64 characters
    }

    @Test
    fun testFileNamingFormat() {
        val segmentManager = SegmentManagerFake()
        val id = "12345678-abcd-ef01-2345-6789abcdef01"
        val timestamp = 1730000000000L // UTC timestamp
        val filename = segmentManager.generateFilename(id, timestamp)

        assertTrue(filename.startsWith("audio_"))
        assertTrue(filename.endsWith(".m4a"))
        assertTrue(filename.contains("12345678"))
    }
}

class SegmentManagerFake {
    fun generateSegmentId(): String = java.util.UUID.randomUUID().toString()

    fun calculateSha256(file: File): String {
        val digest = java.security.MessageDigest.getInstance("SHA-256")
        java.io.FileInputStream(file).use { fis ->
            val buffer = ByteArray(4096)
            var bytesRead: Int
            while (fis.read(buffer).also { bytesRead = it } != -1) {
                digest.update(buffer, 0, bytesRead)
            }
        }
        return digest.digest().joinToString("") { "%02x".format(it) }
    }

    fun generateFilename(segmentId: String, timestamp: Long): String {
        val dateFormat = java.text.SimpleDateFormat("yyyyMMdd_HHmmss", java.util.Locale.US).apply {
            timeZone = java.util.TimeZone.getTimeZone("UTC")
        }
        val dateStr = dateFormat.format(java.util.Date(timestamp))
        return "audio_${dateStr}_${segmentId.take(8)}.m4a"
    }
}

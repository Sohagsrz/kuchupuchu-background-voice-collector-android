package com.kuchupuchu.audiorecorder.recording

sealed interface RecordingState {
    data object Idle : RecordingState
    data class Recording(
        val startedAtEpochMs: Long,
        val durationMs: Long
    ) : RecordingState
    data class Error(val message: String) : RecordingState
}

# KuchuPuchu - Background Audio Recorder with Offline Upload Queue

Production-quality native Android audio recording application with continuous background segmenting, durable offline queuing, and a companion **Laravel + Filament Admin Panel** for listening to, inspecting, and managing recorded audio files.

---

## Architecture Overview

```
                      +------------------------------------------+
                      |         KuchuPuchu Android App           |
                      |  - Jetpack Compose (Material 3)          |
                      |  - AudioRecorderEngine (AudioRecord/AAC) |
                      |  - AudioRecordingService (Foreground)    |
                      |  - Room DB (RecordingEntity metadata)    |
                      |  - App-Private Storage (/recordings)     |
                      |  - WorkManager (UploadWorker)            |
                      +---------------------+--------------------+
                                            |
                         HTTPS / Multipart  | POST /api/v1/recordings
                         Bearer Auth Token  | GET  /api/v1/health
                                            v
                      +---------------------+--------------------+
                      |       Laravel Backend API                |
                      |  - Audio file storage (storage/app)      |
                      |  - Idempotency & SHA-256 validation      |
                      |  - SQLite / MySQL Database               |
                      +---------------------+--------------------+
                                            |
                                            v
                      +------------------------------------------+
                      |        Filament Admin Panel              |
                      |  - Web UI at http://localhost:8000/admin |
                      |  - Embedded HTML5 Audio Player           |
                      |  - File Download & Checksum Verification |
                      |  - Statuses, Duration & Date Filtering   |
                      +------------------------------------------+
```

---

## 1. Android Application Specifications

- **Language**: Kotlin 2.0.21
- **UI Framework**: Jetpack Compose (Material 3), edge-to-edge
- **Minimum Android Version**: Android 8.0 (API 26)
- **Target Android Version**: Android 15 (API 35)
- **Audio Capture**: Continuous `AudioRecord` (16-bit PCM Mono @ 44.1 kHz)
- **Audio Encoding**: Hardware `MediaCodec` AAC-LC encoder
- **Audio Container**: MPEG-4 Container (`.m4a`) via `MediaMuxer`
- **Audio Quality**: Mono, configurable 16–128 kbps (default 24 kbps)
- **Segment Rotation**: Seamless continuous rotation every 60 seconds (zero sample gap between files)
- **Background Persistence**: `android:foregroundServiceType="microphone"` + `PARTIAL_WAKE_LOCK`
- **Notification**: Persistent ongoing notification with live duration counter and **Stop** action button
- **Local Metadata**: AndroidX Room Database with StateFlow reactive queries
- **Local Storage**: App-private storage (`context.filesDir/recordings`)
- **Offline Queue**: AndroidX WorkManager (`UploadWorker`) with exponential backoff & network constraints
- **Security**: AndroidX `EncryptedSharedPreferences` for secure Bearer token storage

---

## 2. Laravel Backend & Filament Admin Panel

The backend is built with **Laravel** and features a **Filament v3 Admin Panel** with an embedded HTML5 audio player.

### Features
1. **REST API**:
   - `GET /api/v1/health`: Server health check endpoint used by the Android app's "Test Connection" button.
   - `POST /api/v1/recordings`: Accepts `multipart/form-data` uploads:
     - `file`: The `.m4a` AAC audio file
     - `segment_id`: Unique segment UUID (used as idempotency key)
     - `started_at`: UTC ISO-8601 recording start time
     - `ended_at`: UTC ISO-8601 recording end time
     - `duration_ms`: Duration in milliseconds
     - `checksum`: SHA-256 hash for integrity validation
2. **Filament Admin Panel & Voice Player Studio**:
   - **URL**: `http://localhost:8000/admin`
   - **Default Admin Account**:
     - **Email**: `admin@kuchupuchu.local`
     - **Password**: `admin123`
   - **Dedicated Voice Player Studio (`/admin/voice-studio`)**:
     - High-end master audio player bar with scrub seekbar, skip +/- 10s, volume, and 0.75x–2.0x playback speed.
     - Live soundwave equalizer animations for the actively playing recording.
     - Key statistics: Total recordings, total recorded duration, disk space consumed, and latest activity.
     - Interactive recording cards with instant one-click playback, metadata tags, and direct file downloads.
     - Real-time client-side search across filenames and segment UUIDs.
   - **Voice Recordings Resource (`/admin/recordings`)**:
     - Embedded audio player column in the data table.
     - Infolist modal with audio playback and full technical segment metadata.
     - Status filtering, bulk deletion, and single-click `.m4a` file export.

---

## 3. How to Run the Project

### Running the Laravel Backend
```bash
cd backend

# 1. Install dependencies (if not already installed)
composer install

# 2. Run database migrations
php artisan migrate

# 3. Create Filament admin user
php artisan make:filament-user

# 4. Start the server (accessible to Android emulator via 10.0.2.2:8000)
php artisan serve --host=0.0.0.0 --port=8000
```
Then visit `http://localhost:8000/admin` in your browser.

### Running the Android Application
```bash
# 1. Compile and build the debug APK
./gradlew assembleDebug

# 2. Run unit tests
./gradlew test

# 3. Start emulator and install APK
adb install -r app/build/outputs/apk/debug/app-debug.apk
adb shell am start -n com.kuchupuchu.audiorecorder/.ui.MainActivity
```

---

## 4. Android Configuration & Settings

Inside the app, tap the **Settings** icon in the top right to configure:
- **Server Base URL**:
  - Laravel backend (port 8000): `http://10.0.2.2:8000/`
  - Python mock backend (port 8080): `http://10.0.2.2:8080/`
  *(Both share the identical REST API endpoints `/api/v1/health` and `/api/v1/recordings`)*
- **Device Auth Token**: Optional Bearer token for protected backend endpoints.
- **Test Connection**: Pings `/api/v1/health` and displays immediate status feedback.
- **Segment Duration**: Select 30s, 60s, 120s, or 300s.
- **Bitrate**: Select 16, 24, 32, or 64 kbps.
- **Delete After Upload**: Automatically purge local audio files after verified server acknowledgment.
- **Clear Local History**: Confirmation dialog to erase local audio and metadata.

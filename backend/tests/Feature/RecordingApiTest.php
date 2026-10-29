<?php

namespace Tests\Feature;

use App\Models\Recording;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecordingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'version' => '1.0.0',
        ]);
    }

    public function test_upload_recording_success_and_idempotent(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('test_audio.m4a', 128, 'audio/mp4');

        $payload = [
            'file' => $file,
            'segment_id' => 'test-segment-uuid-999',
            'started_at' => '2026-10-29T14:00:00Z',
            'ended_at' => '2026-10-29T14:01:00Z',
            'duration_ms' => 60000,
            'checksum' => 'fake_sha256_checksum',
        ];

        // First upload
        $response1 = $this->postJson('/api/v1/recordings', $payload);
        $response1->assertStatus(200);
        $response1->assertJson([
            'success' => true,
            'segment_id' => 'test-segment-uuid-999',
        ]);

        $recordingId = $response1->json('recording_id');
        $this->assertNotEmpty($recordingId);
        $this->assertDatabaseHas('recordings', [
            'segment_id' => 'test-segment-uuid-999',
        ]);

        // Duplicate upload (idempotency check)
        $response2 = $this->postJson('/api/v1/recordings', $payload);
        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'segment_id' => 'test-segment-uuid-999',
            'recording_id' => $recordingId,
        ]);

        $this->assertEquals(1, Recording::where('segment_id', 'test-segment-uuid-999')->count());
    }

    public function test_upload_recording_creates_phone_name_directory(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('custom_device_audio.m4a', 64, 'audio/mp4');

        $payload = [
            'file' => $file,
            'segment_id' => 'seg-phone-test-101',
            'device_name' => 'Samsung Galaxy S24 Ultra',
            'started_at' => '2026-10-29T14:10:00Z',
            'ended_at' => '2026-10-29T14:11:00Z',
            'duration_ms' => 60000,
        ];

        $response = $this->postJson('/api/v1/recordings', $payload);
        $response->assertStatus(200);

        $recording = Recording::where('segment_id', 'seg-phone-test-101')->first();
        $this->assertNotNull($recording);
        $this->assertEquals('Samsung Galaxy S24 Ultra', $recording->device_name);
        $this->assertStringStartsWith('recordings/Samsung_Galaxy_S24_Ultra/', $recording->file_path);

        Storage::disk('public')->assertExists($recording->file_path);
    }
}

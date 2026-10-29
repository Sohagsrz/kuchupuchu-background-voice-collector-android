<?php

namespace Tests\Feature;

use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceStudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/studio');
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_can_access_standalone_studio(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@kuchupuchu.local'],
            ['name' => 'Admin', 'password' => bcrypt('admin123')]
        );

        $response = $this->actingAs($user)->get('/studio');

        $response->assertStatus(200);
        $response->assertSee('Voice Studio');
        $response->assertSee('Audio Recordings');
    }

    public function test_admin_can_access_filament_voice_studio_page(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@kuchupuchu.local'],
            ['name' => 'Admin', 'password' => bcrypt('admin123')]
        );

        $response = $this->actingAs($user)->get('/admin/voice-studio');

        $response->assertStatus(200);
        $response->assertSee('Voice Player Studio');
        $response->assertSee('Total Recordings');
    }

    public function test_studio_returns_json_pagination_for_infinity_scroll(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@kuchupuchu.local'],
            ['name' => 'Admin', 'password' => bcrypt('admin123')]
        );

        for ($i = 1; $i <= 25; $i++) {
            Recording::create([
                'segment_id' => "segment-uuid-{$i}",
                'filename' => "recording_{$i}.m4a",
                'file_path' => "recordings/recording_{$i}.m4a",
                'started_at' => now()->subMinutes(26 - $i),
                'duration_ms' => 60000,
                'size_bytes' => 180000,
                'sha256' => str_repeat((string) ($i % 10), 64),
                'status' => 'COMPLETED',
            ]);
        }

        // Page 1 (per_page = 12)
        $responsePage1 = $this->actingAs($user)->getJson('/studio?page=1&per_page=12&format=json');
        $responsePage1->assertStatus(200);
        $responsePage1->assertJsonPath('current_page', 1);
        $responsePage1->assertJsonPath('last_page', 3);
        $responsePage1->assertJsonPath('has_more', true);
        $responsePage1->assertJsonPath('total', 25);
        $this->assertCount(12, $responsePage1->json('data'));

        // Page 3 (final page, 1 remaining item)
        $responsePage3 = $this->actingAs($user)->getJson('/studio?page=3&per_page=12&format=json');
        $responsePage3->assertStatus(200);
        $responsePage3->assertJsonPath('current_page', 3);
        $responsePage3->assertJsonPath('has_more', false);
        $this->assertCount(1, $responsePage3->json('data'));
    }

    public function test_studio_json_pagination_filters_by_search(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@kuchupuchu.local'],
            ['name' => 'Admin', 'password' => bcrypt('admin123')]
        );

        Recording::create([
            'segment_id' => 'special-target-uuid',
            'filename' => 'special_secret_recording.m4a',
            'file_path' => 'recordings/special.m4a',
            'started_at' => now(),
            'duration_ms' => 60000,
            'size_bytes' => 180000,
            'sha256' => str_repeat('a', 64),
            'status' => 'COMPLETED',
        ]);

        Recording::create([
            'segment_id' => 'normal-uuid',
            'filename' => 'normal_recording.m4a',
            'file_path' => 'recordings/normal.m4a',
            'started_at' => now(),
            'duration_ms' => 60000,
            'size_bytes' => 180000,
            'sha256' => str_repeat('b', 64),
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($user)->getJson('/studio?format=json&search=special_secret');
        $response->assertStatus(200);
        $response->assertJsonPath('total', 1);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('special_secret_recording.m4a', $response->json('data.0.filename'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceStudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_voice_studio_page(): void
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
}

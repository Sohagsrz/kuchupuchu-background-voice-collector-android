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
}

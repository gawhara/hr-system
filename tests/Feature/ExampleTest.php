<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_seeded_admin_can_view_dashboard(): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'email' => 'admin@hr.local',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('لوحة شركة');
    }
}

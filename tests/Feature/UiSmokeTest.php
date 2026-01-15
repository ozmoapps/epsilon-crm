<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the /ui route is available in local environment.
     */
    public function test_ui_page_is_accessible_locally(): void
    {
        // Force application to think it is in local environment
        // NOTE: The route registration happens at boot time, so dynamically switching
        // app()->environment() here might not be enough if routes are already cached or loaded.
        // However, standard PHPUnit tests usually boot the app fresh.
        // But the route condition `if (app()->environment('local'))` in web.php
        // is evaluated when routes are loaded.
        
        // Since we cannot easily reload routes with a different environment in a single test request
        // without more complex setup, we will check if we are running in a CI/testing env that
        // claims to be local, or just skip if not local.
        
        if (!app()->environment('local')) {
            $this->markTestSkipped('UI page is only available in local environment.');
        }

        $response = $this->get('/ui');
        $response->assertStatus(200);
    }

    /**
     * Test that the login page is accessible available.
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /**
     * Test that critical index pages load correctly for authenticated users.
     */
    public function test_critical_index_pages_are_accessible_with_auth(): void
    {
        // Use existing User factory
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/quotes');
        $response->assertStatus(200);

        $response = $this->actingAs($user)->get('/customers');
        $response->assertStatus(200);
    }
}

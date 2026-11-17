<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardResponsiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_responsive_navigation_and_whole_screen_layout(): void
    {
        $user = User::factory()->create([
            'unit' => 'Forensic Directorate',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/Forensic_Head');
        $response->assertOk();

        // Mobile navigation button and drawer panel
        $response->assertSee('mobileMenuBtn', false);
        $response->assertSee('mobileNavPanel', false);
        $response->assertSee('toggleMobileNav', false);

        // Whole screen and responsive css rules
        $response->assertSee('max-height: 100vh', false);
        $response->assertSee('overflow: hidden', false);
        $response->assertSee('min-height: 100dvh', false);

        // Responsive map and radial layout rules
        $response->assertSee('stats-split-grid', false);
        $response->assertSee('radial-layout', false);
    }
}


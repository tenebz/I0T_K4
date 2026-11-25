<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLiquidMotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_intro_screen_over_fluid_background(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="fluid-canvas"', false);
        $response->assertSee('js/fluid.js', false);
        $response->assertSee('Masuk Dashboard');
        $response->assertSee('enterDashboard(\'riwayat\', \'riwayat\')', false);
    }

    public function test_dashboard_keeps_existing_features_behind_the_intro(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Kualitas Air');
        $response->assertSee('id="cari-percobaan"', false);
        $response->assertSee('Sinkronkan');
        $response->assertSee('End This Trial');
        $response->assertSee('Histori Percobaan Air Minum');
        $response->assertSee('x-countup', false);
        $response->assertSee('showInputDetected && entered', false);
    }
}

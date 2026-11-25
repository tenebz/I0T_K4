<?php

namespace Tests\Feature;

use App\Models\Perangkat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLiquidMotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_the_aquasense_opening_screen_over_the_fluid_background(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('<html lang="en">', false);
        $response->assertSee('id="fluid-canvas"', false);
        $response->assertSee('js/fluid.js', false);
        $response->assertSee('AquaSense');
        $response->assertSee('Integrated Real Time Water Quality System');
        $response->assertDontSee('Sytem');
        $response->assertDontSee('sensors normal');
        $response->assertDontSee('intro-badge', false);
        $response->assertSee('Go to Dashboard');
        $response->assertSee('© 2026 AquaSense - Made with ❤️ by Erkham, faishal, Najwa, Ratri, Pebri, Sultan, Iim, Galih, Via');
        $response->assertSee('enterDashboard(\'riwayat\', \'riwayat\')', false);
    }

    public function test_dashboard_renders_empty_states_when_no_trials_exist(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('awal', fn (array $awal): bool => $awal['percobaan']->isEmpty()
            && $awal['active_trial'] === null
            && $awal['completed_trials']->isEmpty());
        $response->assertSee('Start a trial');
        $response->assertSee('No reading yet');
        $response->assertSee('No data to chart yet');
        $response->assertSee('No trials recorded yet');
        $response->assertSee('No completed trials yet');
        $response->assertSee('Not now');
    }

    public function test_device_panel_follows_the_running_trial_not_the_device_sample(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('x-text="deviceStatus"', false);
        $response->assertSee('x-text="activeTrial ? activeTrial.name : \'—\'"', false);
        $response->assertDontSee('device.sampel', false);
        $response->assertSee('askDeleteTrial(t)', false);
    }

    public function test_dashboard_header_logo_links_back_to_the_opening_screen(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('class="topbar-brand" @click.prevent="backToIntro()"', false);
        $response->assertSee('aria-label="AquaSense — back to the opening screen"', false);
    }

    public function test_dashboard_keeps_every_view_and_control_in_english(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="cari-percobaan"', false);
        $response->assertSee('Search trials, dates, or evaluations');
        $response->assertSee('Active Sensors');
        $response->assertSee('Last Sync');
        $response->assertSee('Single sensor');
        $response->assertSee('All (2×2)');
        $response->assertSee('Eligibility / Pass rate');
        $response->assertSee('End This Trial');
        $response->assertSee('Drinking Water Trial History');
        $response->assertSee('Avg. Turbidity (NTU)');
        $response->assertSee('<option value="kekeruhan">Turbidity</option>', false);
        $response->assertSee('<option value="suhu">Temperature</option>', false);
        $response->assertSee('x-countup', false);
        $response->assertSee(':data-x0="c.x0" :data-x1="c.x1"', false);
        $response->assertSee('showInputDetected && entered', false);
        $response->assertDontSee('Sinkronkan');
        $response->assertDontSee('>Percobaan', false);
        $response->assertDontSee('Kekeruhan');
    }

    public function test_sync_rejection_message_is_in_english_when_the_device_is_off(): void
    {
        Perangkat::utama()->update(['menyala' => false]);

        $response = $this->postJson(route('api.sinkron'));

        $response->assertConflict();
        $response->assertJsonPath('message', 'The device is turned off. Turn it on first, then sync.');
    }
}

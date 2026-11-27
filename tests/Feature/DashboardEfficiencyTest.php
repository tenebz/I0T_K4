<?php

namespace Tests\Feature;

use App\Models\Perangkat;
use App\Models\Trial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardEfficiencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_incremental_sync_sends_only_readings_after_the_last_one_the_browser_has(): void
    {
        $trial = $this->trial('Running', active: true, readings: 5);

        $response = $this->postJson(route('api.sinkron'), ['sejak_trial' => $trial->id, 'sejak_nomor' => 3]);

        $response->assertOk();
        $response->assertJsonPath('mode', 'tambahan');
        $response->assertJsonCount(2, 'percobaan_baru');
        $response->assertJsonPath('percobaan_baru.0.no', 4);
        $response->assertJsonPath('percobaan_baru.1.no', 5);
        $response->assertJsonPath('active_trial.jumlah_percobaan', 5);
        $response->assertJsonMissingPath('percobaan');
    }

    public function test_incremental_sync_falls_back_to_full_data_when_the_active_trial_changed(): void
    {
        $old = $this->trial('Old', active: false, readings: 2);
        $this->trial('New', active: true, readings: 3);

        $response = $this->postJson(route('api.sinkron'), ['sejak_trial' => $old->id, 'sejak_nomor' => 2]);

        $response->assertOk();
        $response->assertJsonMissingPath('mode');
        $response->assertJsonCount(3, 'percobaan');
        $response->assertJsonPath('active_trial.name', 'New');
    }

    public function test_incremental_sync_requires_the_last_reading_number(): void
    {
        $trial = $this->trial('Running', active: true, readings: 1);

        $this->postJson(route('api.sinkron'), ['sejak_trial' => $trial->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sejak_nomor');
    }

    public function test_repeated_pings_within_the_window_do_not_rewrite_last_online(): void
    {
        $perangkat = Perangkat::utama();
        $perangkat->forceFill(['terakhir_online' => now()->subSeconds(5)])->save();
        $sebelum = $perangkat->fresh()->terakhir_online;

        $perangkat->tandaiOnline();

        $this->assertTrue($sebelum->equalTo($perangkat->fresh()->terakhir_online));
    }

    public function test_ping_after_the_window_updates_last_online(): void
    {
        $perangkat = Perangkat::utama();
        $perangkat->forceFill(['terakhir_online' => now()->subMinute()])->save();

        $perangkat->tandaiOnline();

        $this->assertTrue($perangkat->fresh()->terakhir_online->gt(now()->subSeconds(5)));
    }

    public function test_dashboard_page_does_not_start_a_session(): void
    {
        config(['session.driver' => 'database']);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertCookieMissing(config('session.cookie'));
        $this->assertSame(0, DB::table('sessions')->count());
    }

    private function trial(string $name, bool $active, int $readings): Trial
    {
        $perangkat = Perangkat::utama();
        $trial = Trial::create([
            'perangkat_id' => $perangkat->id,
            'name' => $name,
            'is_active' => $active,
            'started_at' => now()->subHour(),
            'ended_at' => $active ? null : now(),
        ]);

        for ($nomor = 1; $nomor <= $readings; $nomor++) {
            $perangkat->percobaan()->create([
                'trial_id' => $trial->id,
                'nomor' => $nomor,
                'diukur_pada' => now()->subMinutes(60 - $nomor),
                'ph' => 7.0,
                'tds' => 120,
                'kekeruhan' => 1.0,
                'suhu' => 27.0,
            ]);
        }

        return $trial;
    }
}

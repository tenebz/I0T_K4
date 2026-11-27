<?php

namespace Tests\Feature;

use App\Models\Perangkat;
use App\Models\Trial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_sends_only_the_active_trial_and_its_readings_once(): void
    {
        $this->trial('Done', active: false, readings: 5);
        $this->trial('Running', active: true, readings: 3);

        $response = $this->postJson(route('api.sinkron'));

        $response->assertOk();
        $response->assertJsonMissingPath('completed_trials');
        $response->assertJsonCount(3, 'percobaan');
        $response->assertJsonPath('active_trial.name', 'Running');
        $response->assertJsonPath('active_trial.jumlah_percobaan', 3);
        $response->assertJsonMissingPath('active_trial.percobaan');
    }

    public function test_completed_trials_are_listed_as_summaries_without_readings(): void
    {
        $this->trial('Done', active: false, readings: 4);

        $response = $this->getJson(route('api.trial.history'));

        $response->assertOk();
        $response->assertJsonPath('trials.0.name', 'Done');
        $response->assertJsonPath('trials.0.jumlah_percobaan', 4);
        $response->assertJsonPath('trials.0.rata_rata.ph', 7.15);
        $response->assertJsonMissingPath('trials.0.percobaan');
    }

    public function test_trial_detail_loads_its_readings_on_demand(): void
    {
        $trial = $this->trial('Done', active: false, readings: 4);

        $response = $this->getJson(route('api.trial.show', $trial));

        $response->assertOk();
        $response->assertJsonPath('trial.name', 'Done');
        $response->assertJsonCount(4, 'trial.percobaan');
        $response->assertJsonPath('trial.percobaan.0.no', 1);
    }

    public function test_unknown_trial_detail_returns_not_found(): void
    {
        $this->getJson(route('api.trial.show', ['trial' => 4242]))->assertNotFound();
    }

    public function test_dashboard_query_count_does_not_grow_with_completed_trials(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->trial("Done {$i}", active: false, readings: 6);
        }
        $this->trial('Running', active: true, readings: 2);

        DB::enableQueryLog();
        $data = Perangkat::utama()->dataDashboard();
        $queries = count(DB::getQueryLog());

        $this->assertCount(12, $data['completed_trials']);
        $this->assertArrayNotHasKey('percobaan', $data['completed_trials'][0]);
        $this->assertSame(6, $data['completed_trials'][0]['jumlah_percobaan']);
        $this->assertLessThanOrEqual(4, $queries);
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
                'ph' => $nomor % 2 ? 7.0 : 7.3,
                'tds' => 120,
                'kekeruhan' => 1.0,
                'suhu' => 27.0,
            ]);
        }

        return $trial;
    }
}

<?php

namespace Tests\Feature;

use App\Models\Perangkat;
use App\Models\Trial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_trial_is_deleted_with_its_readings(): void
    {
        $hapus = $this->trial('Cleo', active: false, readings: 3);
        $simpan = $this->trial('Aquades', active: false, readings: 2);

        $response = $this->deleteJson(route('api.trial.destroy', $hapus));

        $response->assertOk();
        $response->assertJsonCount(1, 'trials');
        $response->assertJsonPath('trials.0.name', 'Aquades');
        $this->assertModelMissing($hapus);
        $this->assertDatabaseMissing('percobaan', ['trial_id' => $hapus->id]);
        $this->assertDatabaseCount('percobaan', 2);
        $this->assertModelExists($simpan);
    }

    public function test_active_trial_cannot_be_deleted(): void
    {
        $aktif = $this->trial('Cleo', active: true, readings: 2);

        $response = $this->deleteJson(route('api.trial.destroy', $aktif));

        $response->assertConflict();
        $response->assertJsonPath('message', 'End this trial before deleting it.');
        $this->assertModelExists($aktif);
        $this->assertDatabaseCount('percobaan', 2);
    }

    public function test_unknown_trial_returns_not_found(): void
    {
        $this->deleteJson(route('api.trial.destroy', ['trial' => 12345]))->assertNotFound();
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
                'diukur_pada' => now()->subMinutes(30 - $nomor),
                'ph' => 7.0,
                'tds' => 120,
                'kekeruhan' => 1.0,
                'suhu' => 27.0,
            ]);
        }

        return $trial;
    }
}

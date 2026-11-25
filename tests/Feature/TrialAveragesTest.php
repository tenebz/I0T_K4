<?php

namespace Tests\Feature;

use App\Models\Perangkat;
use App\Models\Trial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialAveragesTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_trial_without_readings_has_no_averages(): void
    {
        $trial = $this->completedTrial('Empty session');

        $this->assertNull($trial->rataRata());
        $this->assertNull($trial->untukDashboard()['rata_rata']);
        $this->assertSame(0, $trial->untukDashboard()['jumlah_percobaan']);
    }

    public function test_completed_trial_averages_its_readings(): void
    {
        $trial = $this->completedTrial('Two readings');
        foreach ([[7.0, 300, 2.0, 26.0], [8.0, 400, 4.0, 28.0]] as $index => [$ph, $tds, $kekeruhan, $suhu]) {
            $trial->perangkat->percobaan()->create([
                'trial_id' => $trial->id,
                'nomor' => $index + 1,
                'diukur_pada' => now()->subMinutes(10 - $index),
                'ph' => $ph,
                'tds' => $tds,
                'kekeruhan' => $kekeruhan,
                'suhu' => $suhu,
            ]);
        }

        $this->assertSame(['ph' => 7.5, 'tds' => 350.0, 'kekeruhan' => 3.0, 'suhu' => 27.0], $trial->rataRata());
    }

    private function completedTrial(string $name): Trial
    {
        return Trial::create([
            'perangkat_id' => Perangkat::utama()->id,
            'name' => $name,
            'is_active' => false,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);
    }
}

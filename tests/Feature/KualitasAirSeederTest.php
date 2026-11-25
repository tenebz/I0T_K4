<?php

namespace Tests\Feature;

use App\Models\Perangkat;
use Database\Seeders\KualitasAirSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KualitasAirSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_three_trials_with_numbering_restarting_per_trial(): void
    {
        $this->seed(KualitasAirSeeder::class);

        $perangkat = Perangkat::query()->sole();

        $this->assertDatabaseCount('trials', 3);
        $this->assertDatabaseCount('percobaan', 12);

        $jumlahPerTrial = $perangkat->trials()
            ->withCount('percobaan')
            ->orderBy('started_at')
            ->pluck('percobaan_count', 'name')
            ->all();
        $this->assertSame(['AQUA' => 5, 'Le Minerale' => 4, 'Pristine' => 3], $jumlahPerTrial);

        $aktif = $perangkat->trials()->where('is_active', true)->sole();
        $this->assertSame('Pristine', $aktif->name);
        $this->assertNull($aktif->ended_at);
        $this->assertSame([1, 2, 3], $aktif->percobaan->pluck('nomor')->all());

        $this->assertDatabaseHas('percobaan', [
            'trial_id' => $aktif->id,
            'nomor' => 3,
            'ph' => 7.2,
            'tds' => 520,
            'kekeruhan' => 6,
            'suhu' => 27.5,
        ]);
    }

    public function test_seed_is_repeatable_and_resets_readings_to_the_fixture(): void
    {
        $this->seed(KualitasAirSeeder::class);

        $perangkat = Perangkat::query()->sole();
        $perangkat->percobaan()->where('nomor', 1)->update(['ph' => 4]);
        $perangkat->percobaan()->create([
            'nomor' => 99,
            'diukur_pada' => now(),
            'ph' => 7.2,
            'tds' => 320,
            'kekeruhan' => 3.1,
            'suhu' => 27.5,
        ]);

        $this->seed(KualitasAirSeeder::class);

        $this->assertDatabaseCount('perangkat', 1);
        $this->assertDatabaseCount('trials', 3);
        $this->assertDatabaseCount('percobaan', 12);
        $this->assertDatabaseMissing('percobaan', ['nomor' => 99]);
        $this->assertDatabaseMissing('percobaan', ['ph' => 4]);
    }
}

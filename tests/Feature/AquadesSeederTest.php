<?php

namespace Tests\Feature;

use App\Models\Perangkat;
use App\Models\Trial;
use Database\Seeders\AquadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AquadesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_one_active_aquades_trial_with_ten_ordered_readings(): void
    {
        $this->seed(AquadesSeeder::class);

        $trial = Trial::query()->where('is_active', true)->sole();

        $this->assertSame('Aquades', $trial->name);
        $this->assertSame(range(1, 10), $trial->percobaan->pluck('nomor')->all());
        $waktu = $trial->percobaan->map(fn ($percobaan): int => $percobaan->diukur_pada->getTimestamp())->all();
        $urut = $waktu;
        sort($urut);
        $this->assertSame($urut, $waktu);
        $this->assertTrue(Perangkat::query()->sole()->menyala);
    }

    public function test_seed_is_repeatable_and_closes_other_active_trials(): void
    {
        $perangkat = Perangkat::utama();
        $lama = Trial::create([
            'perangkat_id' => $perangkat->id,
            'name' => 'Old session',
            'is_active' => true,
            'started_at' => now()->subDay(),
        ]);

        $this->seed(AquadesSeeder::class);
        $this->seed(AquadesSeeder::class);

        $this->assertDatabaseCount('percobaan', 10);
        $this->assertSame(1, Trial::query()->where('name', 'Aquades')->count());
        $this->assertFalse($lama->fresh()->is_active);
        $this->assertNotNull($lama->fresh()->ended_at);
    }

    public function test_dashboard_shows_the_aquades_readings(): void
    {
        $this->seed(AquadesSeeder::class);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('awal', fn (array $awal): bool => $awal['percobaan']->count() === 10
            && $awal['active_trial']['name'] === 'Aquades');
    }
}

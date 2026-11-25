<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perangkat;
use App\Models\Trial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrialController extends Controller
{
    /** Memulai trial baru: user memasukkan nama trial. */
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $perangkat = Perangkat::utama();

        // Tutup trial aktif lama jika ada.
        Trial::where('perangkat_id', $perangkat->id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'ended_at' => now()]);

        $trial = Trial::create([
            'perangkat_id' => $perangkat->id,
            'name' => $data['name'],
            'is_active' => true,
            'started_at' => now(),
        ]);

        return response()->json([
            'trial' => $trial->untukDashboard(),
            'perangkat' => $perangkat->untukDashboard(),
        ], 201);
    }

    /** Mengakhiri trial aktif: hitung rata-rata dan simpan. */
    public function end(): JsonResponse
    {
        $perangkat = Perangkat::utama();

        $trial = Trial::where('perangkat_id', $perangkat->id)
            ->where('is_active', true)
            ->first();

        if (! $trial) {
            return response()->json(['message' => 'There is no active trial.'], 404);
        }

        $trial->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);

        return response()->json([
            'trial' => $trial->fresh()->untukDashboard(),
            'perangkat' => $perangkat->untukDashboard(),
        ]);
    }

    /** Daftar semua trial yang sudah selesai (untuk halaman evaluasi). */
    public function history(): JsonResponse
    {
        return response()->json(['trials' => $this->historiSelesai(Perangkat::utama())]);
    }

    /**
     * Menghapus satu trial yang sudah selesai dari Evaluasi, beserta semua percobaannya.
     * Trial yang masih aktif tidak bisa dihapus (akhiri dulu lewat "End This Trial").
     */
    public function destroy(Trial $trial): JsonResponse
    {
        $perangkat = Perangkat::utama();

        if ($trial->perangkat_id !== $perangkat->id) {
            return response()->json(['message' => 'Trial not found.'], 404);
        }

        if ($trial->is_active) {
            return response()->json(['message' => 'End this trial before deleting it.'], 409);
        }

        DB::transaction(function () use ($trial): void {
            $trial->percobaan()->delete();
            $trial->delete();
        });

        return response()->json(['trials' => $this->historiSelesai($perangkat)]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function historiSelesai(Perangkat $perangkat): Collection
    {
        return Trial::where('perangkat_id', $perangkat->id)
            ->where('is_active', false)
            ->orderByDesc('ended_at')
            ->with('percobaan')
            ->get()
            ->map->untukDashboard()
            ->values();
    }

    /** Cek apakah ada trial aktif. */
    public function status(): JsonResponse
    {
        $perangkat = Perangkat::utama();

        $trial = Trial::where('perangkat_id', $perangkat->id)
            ->where('is_active', true)
            ->with('percobaan')
            ->first();

        return response()->json([
            'has_active_trial' => $trial !== null,
            'trial' => $trial?->untukDashboard(),
        ]);
    }
}

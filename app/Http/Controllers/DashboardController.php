<?php

namespace App\Http\Controllers;

use App\Models\Perangkat;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $awal = Perangkat::utama()->dataDashboard() + [
            'sensor' => config('kualitas_air.sensor'),
            'url' => [
                'sinkron' => route('api.sinkron'),
                'daya' => route('api.daya'),
                'trial_start' => route('api.trial.start'),
                'trial_end' => route('api.trial.end'),
                'trial_status' => route('api.trial.status'),
                'trial_history' => route('api.trial.history'),
                // ":id" diganti dengan id trial di dashboard.js.
                'trial_destroy' => str_replace('999999999', ':id', route('api.trial.destroy', ['trial' => 999999999])),
            ],
        ];

        return view('dashboard', ['awal' => $awal]);
    }
}

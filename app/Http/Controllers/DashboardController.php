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
            ],
        ];

        return view('dashboard', ['awal' => $awal]);
    }
}

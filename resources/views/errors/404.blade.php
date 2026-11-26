@extends('errors.layout')

@section('code', '404')
@section('title', 'Page not found')
@section('zone', 'Depth: unknown')
@section('heading', 'You drifted all the way to the seabed')
@section('message')
    The page you're looking for was carried off by the current, or it never existed. Check the address, or swim back up to the <strong>AquaSense</strong> dashboard.
@endsection

@section('actions')
    <a class="btn btn-primary" href="{{ url('/') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 9.5V20h13V9.5"/><path d="M10 20v-5.5h4V20"/></svg>
        Back to dashboard
    </a>
    <button type="button" class="btn btn-secondary" data-action="back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
        Go back
    </button>
@endsection

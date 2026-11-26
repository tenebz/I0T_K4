@extends('errors.layout')

@section('code', '419')
@section('title', 'Page expired')
@section('zone', 'Session expired')
@section('heading', 'Your session drifted away')
@section('message')
    This page was open for too long and its security token expired, so your last action wasn't sent. Reload the page and try again.
@endsection

@section('actions')
    <button type="button" class="btn btn-primary" data-action="reload">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/></svg>
        Reload page
    </button>
    <a class="btn btn-secondary" href="{{ url('/') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 9.5V20h13V9.5"/><path d="M10 20v-5.5h4V20"/></svg>
        Back to dashboard
    </a>
@endsection

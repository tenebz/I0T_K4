@extends('errors.layout')

@section('code', '503')
@section('title', 'Under maintenance')
@section('zone', 'Under maintenance')
@section('heading', 'Divers are servicing the pipes')
@section('message')
    <strong>AquaSense</strong> is down for a short maintenance and will be back shortly. Readings sent by the device during this time may not be saved. Try again in a few minutes.
@endsection

@section('actions')
    <button type="button" class="btn btn-primary" data-action="reload">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/></svg>
        Try again
    </button>
@endsection

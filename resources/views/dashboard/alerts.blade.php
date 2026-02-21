@extends('layouts.app')

@section('title', 'Alerts')
@section('page-title', 'Alerts')
@section('page-subtitle', 'Actions that need your attention')

@section('content')
    <div class="alerts-list">
        @foreach($alerts as $alert)
            <div class="alert-card {{ $alert['type'] }}">
                <div class="alert-icon">{{ $alert['icon'] }}</div>
                <div class="alert-body">
                    <div class="alert-title">{{ $alert['title'] }}</div>
                    <div class="alert-message">{{ $alert['message'] }}</div>
                    <div class="alert-meta">
                        <span class="alert-client">{{ $alert['client'] }}</span>
                        <span class="alert-time">{{ $alert['time'] }}</span>
                    </div>
                </div>
                <button class="alert-dismiss" onclick="this.closest('.alert-card').style.display='none'" aria-label="Dismiss">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
        @endforeach
    </div>
@endsection
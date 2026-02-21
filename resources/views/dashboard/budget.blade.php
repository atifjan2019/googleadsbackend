@extends('layouts.app')

@section('title', 'Budget')
@section('page-title', 'Budget Tracker')
@section('page-subtitle', 'Monthly budget utilisation per client')

@section('content')
    <div class="budget-grid">
        @foreach($clients as $client)
            @php
                $pct = round(($client['spend'] / $client['budget']) * 100);
                $remaining = $client['budget'] - $client['spend'];
                $statusClass = $pct > 95 ? 'danger' : ($pct > 75 ? 'warning' : 'ok');
                $daysLeft = 10; // Mock
                $projectedSpend = round($client['spend'] * (30 / 20)); // Mock projection
                $projectedPct = round(($projectedSpend / $client['budget']) * 100);
            @endphp
            <div class="budget-card {{ $statusClass }}">
                <div class="budget-card-header">
                    <div class="budget-client-info">
                        <div class="client-avatar-sm" style="background: #EE314F">
                            {{ strtoupper(substr($client['name'], 0, 1)) }}
                        </div>
                        <h4>{{ $client['name'] }}</h4>
                    </div>
                    <span class="budget-status-tag {{ $statusClass }}">
                        @if($statusClass === 'danger') ⚠️ Critical
                        @elseif($statusClass === 'warning') ⚡ Watch
                        @else ✅ On Track
                        @endif
                    </span>
                </div>

                <div class="budget-numbers">
                    <div class="budget-stat">
                        <span class="budget-stat-label">Spent</span>
                        <span class="budget-stat-value">£{{ number_format($client['spend']) }}</span>
                    </div>
                    <div class="budget-stat">
                        <span class="budget-stat-label">Budget</span>
                        <span class="budget-stat-value">£{{ number_format($client['budget']) }}</span>
                    </div>
                    <div class="budget-stat">
                        <span class="budget-stat-label">Remaining</span>
                        <span
                            class="budget-stat-value {{ $remaining < 200 ? 'negative-text' : '' }}">£{{ number_format($remaining) }}</span>
                    </div>
                </div>

                <div class="budget-progress">
                    <div class="budget-progress-bar">
                        <div class="budget-progress-fill {{ $statusClass }}" style="width: {{ min($pct, 100) }}%"></div>
                    </div>
                    <div class="budget-progress-labels">
                        <span>{{ $pct }}% used</span>
                        <span>{{ $daysLeft }} days left</span>
                    </div>
                </div>

                <div class="budget-projection">
                    <span class="projection-label">Projected end-of-month:</span>
                    <span class="projection-value {{ $projectedPct > 100 ? 'negative-text' : '' }}">
                        £{{ number_format($projectedSpend) }}
                        @if($projectedPct > 100)
                            ({{ $projectedPct - 100 }}% over budget)
                        @else
                            ({{ $projectedPct }}% of budget)
                        @endif
                    </span>
                </div>
            </div>
        @endforeach
    </div>
@endsection
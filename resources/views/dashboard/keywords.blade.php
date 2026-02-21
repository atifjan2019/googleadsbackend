@extends('layouts.app')

@section('title', 'Keywords')
@section('page-title', 'Keywords')
@section('page-subtitle', 'Keyword performance overview')

@section('content')
    <div class="kpi-grid four">
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Top Keywords</span></div>
            <div class="kpi-value">{{ $kpis['top_count'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Avg. CPC</span></div>
            <div class="kpi-value">{{ $kpis['avg_cpc'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Wasted Spend</span></div>
            <div class="kpi-value negative-text">{{ $kpis['wasted_spend'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header"><span class="kpi-label">Avg. Quality Score</span></div>
            <div class="kpi-value">{{ $kpis['avg_qs'] }}</div>
        </div>
    </div>

    <div class="charts-row keywords-row">
        <div class="table-card">
            <div class="table-header">
                <h3>🏆 Top Performing Keywords</h3>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Match</th>
                            <th>Clicks</th>
                            <th>Conv.</th>
                            <th>CPA</th>
                            <th>QS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($keywords['top'] as $kw)
                            <tr>
                                <td><code class="keyword-text">{{ $kw['keyword'] }}</code></td>
                                <td><span class="match-badge">{{ $kw['match'] }}</span></td>
                                <td class="number">{{ $kw['clicks'] }}</td>
                                <td class="number">{{ $kw['conversions'] }}</td>
                                <td class="number">£{{ number_format($kw['cpa'], 2) }}</td>
                                <td>
                                    <span class="qs-badge {{ $kw['qs'] >= 8 ? 'high' : ($kw['qs'] >= 6 ? 'medium' : 'low') }}">
                                        {{ $kw['qs'] }}/10
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h3>💸 Wasted Spend Keywords</h3>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Clicks</th>
                            <th>Spend</th>
                            <th>Conv.</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($keywords['wasted'] as $kw)
                            <tr>
                                <td><code class="keyword-text wasted">{{ $kw['keyword'] }}</code></td>
                                <td class="number">{{ $kw['clicks'] }}</td>
                                <td class="number negative-text">£{{ number_format($kw['spend']) }}</td>
                                <td class="number">{{ $kw['conversions'] }}</td>
                                <td>
                                    <button class="action-btn negative"
                                        onclick="this.textContent='✓ Added'; this.disabled=true; this.classList.add('done')">
                                        Add as Negative
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
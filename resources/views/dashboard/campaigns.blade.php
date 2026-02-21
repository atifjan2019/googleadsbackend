@extends('layouts.app')

@section('title', 'Campaigns')
@section('page-title', 'Campaigns')
@section('page-subtitle', 'Track all campaign performance')

@section('content')
    <div class="client-selector">
        <label for="campaignClient">Filter by Client:</label>
        <form method="GET" action="{{ route('dashboard.campaigns') }}" id="campaignFilterForm">
            <select id="campaignClient" name="client" class="date-select"
                onchange="document.getElementById('campaignFilterForm').submit()">
                <option value="all" {{ $clientId === 'all' ? 'selected' : '' }}>All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client['id'] }}" {{ $clientId == $client['id'] ? 'selected' : '' }}>{{ $client['name'] }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h3>Campaign Performance</h3>
            <input type="text" class="search-input" placeholder="Search campaigns..." id="campaignSearch">
        </div>
        <div class="table-wrapper">
            <table class="data-table" id="campaignTable">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Spend</th>
                        <th>Impr.</th>
                        <th>Clicks</th>
                        <th>CTR</th>
                        <th>Conv.</th>
                        <th>CPA</th>
                        <th>ROAS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $campaign)
                        @php
                            $clientName = $campaign['client_name'] ?? (collect($clients)->firstWhere('id', $campaign['client_id'])['name'] ?? 'Unknown');
                        @endphp
                        <tr>
                            <td><span class="campaign-name">{{ $campaign['name'] }}</span></td>
                            <td><span class="client-tag">{{ $clientName }}</span></td>
                            <td><span class="type-badge {{ strtolower($campaign['type']) }}">{{ $campaign['type'] }}</span></td>
                            <td>
                                <span class="status-badge {{ $campaign['status'] }}">
                                    @if($campaign['status'] === 'active') 🟢 Active
                                    @elseif($campaign['status'] === 'warning') 🟡 Attention
                                    @else 🔴 Poor
                                    @endif
                                </span>
                            </td>
                            <td class="number">£{{ number_format($campaign['spend']) }}</td>
                            <td class="number">{{ number_format($campaign['impressions']) }}</td>
                            <td class="number">{{ number_format($campaign['clicks']) }}</td>
                            <td class="number">{{ $campaign['ctr'] }}%</td>
                            <td class="number">{{ $campaign['conversions'] }}</td>
                            <td class="number">£{{ number_format($campaign['cpa'], 2) }}</td>
                            <td>
                                <span
                                    class="roas-badge {{ $campaign['roas'] >= 5 ? 'high' : ($campaign['roas'] >= 3 ? 'medium' : 'low') }}">
                                    {{ $campaign['roas'] }}x
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const s = document.getElementById('campaignSearch');
                if (s) {
                    s.addEventListener('input', function () {
                        const q = this.value.toLowerCase();
                        document.querySelectorAll('#campaignTable tbody tr').forEach(row => {
                            const name = row.querySelector('.campaign-name')?.textContent.toLowerCase() || '';
                            row.style.display = name.includes(q) ? '' : 'none';
                        });
                    });
                }
            });
        </script>
    @endpush
@endsection
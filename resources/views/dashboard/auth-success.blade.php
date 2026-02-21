@extends('layouts.app')

@section('title', 'Google Ads Connected')
@section('page-title', 'Google Ads Connected')
@section('page-subtitle', 'Authorization successful')

@section('content')
    <div class="table-card" style="max-width: 640px;">
        <div style="padding: 40px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <div
                    style="width: 64px; height: 64px; background: var(--success-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px;">
                    ✅</div>
                <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 8px;">Successfully Connected!</h2>
                <p style="color: var(--text-secondary); font-size: 14px;">Your Google Ads account has been authorised.</p>
            </div>

            <div class="form-group">
                <label>Your Refresh Token</label>
                <div style="position: relative;">
                    <textarea id="refreshToken" rows="3" readonly
                        style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace; font-size: 12px; background: var(--bg); resize: none;">{{ $refresh_token }}</textarea>
                    <button
                        onclick="navigator.clipboard.writeText(document.getElementById('refreshToken').value); this.textContent='✓ Copied!'; setTimeout(() => this.textContent='Copy', 2000)"
                        style="position: absolute; top: 8px; right: 8px; padding: 4px 12px; background: var(--primary); color: #fff; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">Copy</button>
                </div>
            </div>

            <div
                style="background: var(--warning-bg); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 8px; padding: 16px; margin-top: 16px;">
                <p style="font-size: 13px; color: var(--text); line-height: 1.6;">
                    <strong>⚠️ Important:</strong> Copy this refresh token and add it to your <code>.env</code> file as:<br>
                    <code
                        style="background: rgba(0,0,0,0.06); padding: 2px 6px; border-radius: 4px;">GOOGLE_ADS_REFRESH_TOKEN={{ $refresh_token }}</code>
                </p>
            </div>

            <div style="text-align: center; margin-top: 24px;">
                <a href="{{ route('dashboard.overview') }}" class="btn-primary" style="text-decoration: none;">Go to
                    Dashboard</a>
            </div>
        </div>
    </div>
@endsection
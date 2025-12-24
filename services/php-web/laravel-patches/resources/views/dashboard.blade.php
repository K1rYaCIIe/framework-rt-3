@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-12 text-center py-5 animate__animated animate__zoomIn">
        <h1 class="display-3">Welcome to Cassiopeia</h1>
        <p class="lead text-muted">A unified platform for space data monitoring and exploration.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100 animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
            <div class="card-body text-center py-5">
                <div class="mb-3"><i class="h1">🛰️</i></div>
                <h3>ISS Tracker</h3>
                <p class="text-muted">Real-time position and telemetry of the International Space Station.</p>
                <a href="/iss" class="btn btn-outline-info">View Live Tracker</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
            <div class="card-body text-center py-5">
                <div class="mb-3"><i class="h1">🔬</i></div>
                <h3>NASA OSDR</h3>
                <p class="text-muted">Explore datasets from NASA's Open Space Data Repository.</p>
                <a href="/osdr" class="btn btn-outline-info">Browse Datasets</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
            <div class="card-body text-center py-5">
                <div class="mb-3"><i class="h1">📊</i></div>
                <h3>Legacy Telemetry</h3>
                <p class="text-muted">Historical data from our legacy sensors and modules.</p>
                <a href="/telemetry" class="btn btn-outline-info">View History</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-12">
        <div class="card p-4">
            <h4>System Summary</h4>
            <div id="summary-content" class="text-center py-4">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function loadSummary() {
        try {
            const res = await fetch('/api/space/summary');
            const data = await res.json();
            
            let html = `
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="h3">${data.osdr_count || 0}</div>
                        <div class="small text-muted">OSDR Items</div>
                    </div>
                    <div class="col-md-3">
                        <div class="h3">${data.iss?.at ? new Date(data.iss.at).toLocaleTimeString() : 'N/A'}</div>
                        <div class="small text-muted">Last ISS Update</div>
                    </div>
                    <div class="col-md-3">
                        <div class="h3">${data.apod?.at ? 'OK' : 'N/A'}</div>
                        <div class="small text-muted">APOD Status</div>
                    </div>
                    <div class="col-md-3">
                        <div class="h3">Online</div>
                        <div class="small text-muted">System Status</div>
                    </div>
                </div>
            `;
            document.getElementById('summary-content').innerHTML = html;
        } catch (e) {
            document.getElementById('summary-content').innerHTML = '<p class="text-danger">Failed to load system summary.</p>';
        }
    }
    loadSummary();
</script>
@endsection

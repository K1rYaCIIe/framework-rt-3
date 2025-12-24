@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="animate__animated animate__fadeInDown">International Space Station (ISS)</h2>
        <p class="text-muted">Real-time tracking and telemetry</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-accent" id="refreshIss">Refresh Data</button>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow">
            <div class="card-body p-0">
                <div id="map"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card mb-4">
            <div class="card-header bg-transparent"><strong>Live Telemetry</strong></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small d-block">Latitude</label>
                    <span id="lat" class="h4">--</span>
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Longitude</label>
                    <span id="lon" class="h4">--</span>
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Altitude</label>
                    <span id="alt" class="h4">--</span> km
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Velocity</label>
                    <span id="vel" class="h4">--</span> km/h
                </div>
                <hr>
                <div class="small text-muted">Last updated: <span id="updated">--</span></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-transparent"><strong>Movement Trend</strong></div>
            <div class="card-body">
                <p id="trend-msg">Analyzing movement...</p>
                <div id="trend-details" class="small" style="display:none;">
                    <div class="d-flex justify-content-between">
                        <span>Distance moved:</span>
                        <span id="trend-dist">--</span> km
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    var map = L.map('map').setView([0, 0], 3);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var issIcon = L.icon({
        iconUrl: 'https://upload.wikimedia.org/wikipedia/commons/d/d0/International_Space_Station.svg',
        iconSize: [50, 30],
        iconAnchor: [25, 15]
    });
    var marker = L.marker([0, 0], {icon: issIcon}).addTo(map);

    function updateIss() {
        fetch('/api/iss/last')
            .then(res => res.json())
            .then(data => {
                if (data.payload) {
                    var p = data.payload;
                    var lat = parseFloat(p.latitude);
                    var lon = parseFloat(p.longitude);
                    marker.setLatLng([lat, lon]);
                    map.panTo([lat, lon]);
                    
                    document.getElementById('lat').textContent = lat.toFixed(4);
                    document.getElementById('lon').textContent = lon.toFixed(4);
                    document.getElementById('alt').textContent = parseFloat(p.altitude).toFixed(1);
                    document.getElementById('vel').textContent = parseFloat(p.velocity).toLocaleString();
                    document.getElementById('updated').textContent = new Date(data.fetched_at).toLocaleString();
                }
            });

        fetch('/api/iss/trend')
            .then(res => res.json())
            .then(data => {
                var msg = data.movement ? "ISS is moving" : "Station position static";
                document.getElementById('trend-msg').textContent = msg;
                if (data.delta_km) {
                    document.getElementById('trend-details').style.display = 'block';
                    document.getElementById('trend-dist').textContent = data.delta_km.toFixed(2);
                }
            });
    }

    document.getElementById('refreshIss').onclick = updateIss;
    updateIss();
    setInterval(updateIss, 10000);
</script>
@endsection

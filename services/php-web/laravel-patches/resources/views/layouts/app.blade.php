<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cassiopeia Space Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        :root {
            --space-bg: #0b0d17;
            --accent: #00d2ff;
        }
        body {
            background-color: var(--space-bg);
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: rgba(11, 13, 23, 0.9) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .nav-link {
            color: #ccc !important;
            transition: color 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--accent) !important;
        }
        .card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            backdrop-filter: blur(10px);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        #map { height: 400px; border-radius: 10px; }
        .btn-accent {
            background: var(--accent);
            color: #000;
            border: none;
        }
        .btn-accent:hover {
            background: #0099cc;
            color: #fff;
        }
        .animate__animated {
            animation-duration: 0.8s;
        }
        .table {
            color: #eee;
        }
    </style>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand animate__animated animate__fadeInLeft" href="/dashboard">
            <strong>CASSIOPEIA</strong> SPACE
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}" href="/dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link {{ Request::is('iss') ? 'active' : '' }}" href="/iss">ISS Live</a></li>
                <li class="nav-item"><a class="nav-link {{ Request::is('osdr') ? 'active' : '' }}" href="/osdr">NASA OSDR</a></li>
                <li class="nav-item"><a class="nav-link {{ Request::is('telemetry') ? 'active' : '' }}" href="/telemetry">Legacy Telemetry</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container animate__animated animate__fadeIn">
    @yield('content')
</div>

<footer class="mt-5 py-4 border-top border-secondary text-center text-muted">
    <div class="container">
        <p>&copy; {{ date('Y') }} Cassiopeia Space Exploration. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>

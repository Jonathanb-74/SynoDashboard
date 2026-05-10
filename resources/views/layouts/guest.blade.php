<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SynoManager') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #1a1d23;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
        }
        .brand-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .brand-header h1 {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 700;
        }
        .brand-header p {
            color: rgba(255,255,255,.5);
            font-size: .875rem;
        }
    </style>
</head>
<body>
    <div class="auth-card p-3">
        <div class="brand-header">
            <h1><i class="bi bi-hdd-network me-2"></i>SynoManager</h1>
            <p>Console de supervision NAS Synology</p>
        </div>
        <div class="card border-0 shadow">
            <div class="card-body p-4">
                {{ $slot }}
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

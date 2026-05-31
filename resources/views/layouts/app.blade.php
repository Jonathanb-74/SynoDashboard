<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}SynoManager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        [x-cloak] { display: none !important; }
        body { background-color: #f0f2f5; }
        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            background: #1a1d23;
            width: 250px;
            flex-shrink: 0;
        }
        .sidebar .brand {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
            text-decoration: none;
            display: block;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.7);
            padding: .55rem 1.5rem;
            border-radius: 0;
            font-size: .875rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.08);
        }
        .sidebar .nav-link i { width: 1.2rem; }
        .nav-section {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255,255,255,.35);
            padding: 1rem 1.5rem .25rem;
        }
        .main-content { flex: 1; min-width: 0; }
        .top-bar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: .75rem 1.5rem;
        }
        pre.json-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            border-radius: .375rem;
            padding: 1rem;
            font-size: .8rem;
            max-height: 500px;
            overflow: auto;
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="d-flex">

    {{-- Sidebar --}}
    <nav class="sidebar d-flex flex-column">
        <a href="{{ route('dashboard') }}" class="brand">
            <i class="bi bi-hdd-network me-2"></i>SynoManager
        </a>

        <div class="nav-section">Supervision</div>
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
        <a href="{{ route('nas.index') }}"
           class="nav-link {{ request()->routeIs('nas.index') || request()->routeIs('nas.show') ? 'active' : '' }}">
            <i class="bi bi-hdd-stack me-2"></i>NAS
        </a>
        <a href="{{ route('nas.pending') }}"
           class="nav-link {{ request()->routeIs('nas.pending') ? 'active' : '' }}">
            <i class="bi bi-clock-history me-2"></i>En attente
            @php $pendingCount = \App\Models\NasDevice::where('status','pending')->count(); @endphp
            @if($pendingCount > 0)
                <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">{{ $pendingCount }}</span>
            @endif
        </a>

        <div class="nav-section">Modèles</div>
        <a href="{{ route('api-models.index') }}"
           class="nav-link {{ request()->routeIs('api-models.*') ? 'active' : '' }}">
            <i class="bi bi-diagram-3 me-2"></i>Modèles API
        </a>
        <a href="{{ route('decoder-models.index') }}"
           class="nav-link {{ request()->routeIs('decoder-models.*') ? 'active' : '' }}">
            <i class="bi bi-code-square me-2"></i>Décodeurs JSON
        </a>

        @if(auth()->user()->isAdmin())
        <div class="nav-section">Outils</div>
        <a href="{{ route('test.index') }}"
           class="nav-link {{ request()->routeIs('test.*') ? 'active' : '' }}">
            <i class="bi bi-terminal me-2"></i>Test Console
        </a>
        <a href="{{ route('import-export.index') }}"
           class="nav-link {{ request()->routeIs('import-export.*') ? 'active' : '' }}">
            <i class="bi bi-arrow-left-right me-2"></i>Import / Export
        </a>
        <a href="{{ route('docs.agent-api') }}"
           class="nav-link {{ request()->routeIs('docs.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-text me-2"></i>Doc API Agent
        </a>
        <a href="{{ route('api-logs.index') }}"
           class="nav-link {{ request()->routeIs('api-logs.*') ? 'active' : '' }}">
            <i class="bi bi-journal-code me-2"></i>Logs API Agent
        </a>
        @endif

        @auth
        @if(auth()->user()->isAdmin())
        <div class="nav-section">Administration</div>
        <a href="{{ route('users.index') }}"
           class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i class="bi bi-people me-2"></i>Utilisateurs
        </a>
        <a href="{{ route('settings.api-methods.index') }}"
           class="nav-link {{ request()->routeIs('settings.api-methods.*') ? 'active' : '' }}">
            <i class="bi bi-sliders me-2"></i>Méthodes API
        </a>
        <a href="{{ route('settings.smtp.edit') }}"
           class="nav-link {{ request()->routeIs('settings.smtp.*') ? 'active' : '' }}">
            <i class="bi bi-envelope-at me-2"></i>Configuration SMTP
        </a>
        <a href="{{ route('settings.nas-fields.index') }}"
           class="nav-link {{ request()->routeIs('settings.nas-fields.*') ? 'active' : '' }}">
            <i class="bi bi-card-list me-2"></i>Champs NAS
        </a>
        <a href="{{ route('settings.nas-views.index') }}"
           class="nav-link {{ request()->routeIs('settings.nas-views.*') ? 'active' : '' }}">
            <i class="bi bi-table me-2"></i>Vues NAS
        </a>
        <a href="{{ route('settings.dashboard-widgets.index') }}"
           class="nav-link {{ request()->routeIs('settings.dashboard-widgets.*') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2 me-2"></i>Widgets Dashboard
        </a>
        <a href="{{ route('settings.global-attributes.index') }}"
           class="nav-link {{ request()->routeIs('settings.global-attributes.*') ? 'active' : '' }}">
            <i class="bi bi-diagram-2 me-2"></i>Attributs globaux
        </a>
        @endif
        @endauth

        <div class="mt-auto p-3 border-top border-secondary">
            <i class="bi bi-person-circle text-secondary me-1"></i>
            <span class="text-secondary small">{{ Auth::user()->name }}</span>
            @if(auth()->user()->isAdmin())
                <span class="badge bg-danger ms-1" style="font-size:.6rem">admin</span>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="d-inline ms-2">
                @csrf
                <button type="submit" class="btn btn-link btn-sm p-0 text-secondary" title="Déconnexion">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </nav>

    {{-- Main --}}
    <div class="main-content d-flex flex-column">
        <div class="top-bar d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">{{ $title ?? 'Dashboard' }}</h6>
            <small class="text-muted">{{ now()->format('d/m/Y H:i') }}</small>
        </div>

        <div class="p-4 flex-grow-1">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any() && !$errors->has('api'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function tableController() {
    return {
        search: '',
        _sortCol: -1,
        _sortDir: 'asc',
        _rows: [],

        init() {
            const tbody = this.$refs.tbody;
            if (!tbody) return;
            this._rows = Array.from(tbody.querySelectorAll('tr')).map(tr => ({
                el: tr,
                cells: Array.from(tr.querySelectorAll('td')).map(td =>
                    (td.dataset.sort ?? td.textContent).trim().toLowerCase()
                ),
            }));
        },

        sortBy(col) {
            if (this._sortCol === col) {
                this._sortDir = this._sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this._sortCol = col;
                this._sortDir = 'asc';
            }
            this._render();
        },

        sortIcon(col) {
            if (this._sortCol !== col) return 'bi-arrow-down-up opacity-50';
            return this._sortDir === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
        },

        _render() {
            const tbody = this.$refs.tbody;
            if (!tbody) return;
            const q = this.search.toLowerCase().trim();

            let rows = this._rows.filter(r => !q || r.cells.some(c => c.includes(q)));

            if (this._sortCol >= 0) {
                rows.sort((a, b) => {
                    const av = a.cells[this._sortCol] ?? '';
                    const bv = b.cells[this._sortCol] ?? '';
                    const cmp = av.localeCompare(bv, undefined, { numeric: true, sensitivity: 'base' });
                    return this._sortDir === 'asc' ? cmp : -cmp;
                });
            }

            this._rows.forEach(r => r.el.hidden = true);
            rows.forEach(r => { r.el.hidden = false; tbody.appendChild(r.el); });
        },

        get _visibleCount() { return this._rows.filter(r => !r.el.hidden).length; },
        get _totalCount()   { return this._rows.length; },
    };
}
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@stack('scripts')
</body>
</html>

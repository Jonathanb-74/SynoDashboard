<x-app-layout>
    <x-slot name="title">Utilisateurs</x-slot>

    @if($errors->has('delete') || $errors->has('role'))
        <div class="alert alert-danger">{{ $errors->first('delete') ?: $errors->first('role') }}</div>
    @endif
    @if($errors->has('email'))
        <div class="alert alert-danger">{{ $errors->first('email') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-people me-2 text-primary"></i>Utilisateurs</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#inviteModal">
                    <i class="bi bi-envelope-plus me-1"></i>Inviter par email
                </button>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="bi bi-person-plus me-1"></i>Créer directement
                </button>
            </div>
        </div>

        <div x-data="tableController()" x-init="init()">
        <x-table-search />
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.9rem">
                <thead class="table-light user-select-none">
                    <tr>
                        <th @click="sortBy(0)" style="cursor:pointer">Nom <i class="bi small ms-1" :class="sortIcon(0)"></i></th>
                        <th @click="sortBy(1)" style="cursor:pointer">Email <i class="bi small ms-1" :class="sortIcon(1)"></i></th>
                        <th @click="sortBy(2)" style="cursor:pointer">Rôle <i class="bi small ms-1" :class="sortIcon(2)"></i></th>
                        <th @click="sortBy(3)" style="cursor:pointer">Créé le <i class="bi small ms-1" :class="sortIcon(3)"></i></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody x-ref="tbody">
                    @foreach($users as $user)
                    <tr>
                        <td class="fw-medium">
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                                <span class="badge bg-secondary ms-1">vous</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $user->email }}</td>
                        <td>
                            @if($user->isAdmin())
                                <span class="badge bg-danger">Admin</span>
                            @else
                                <span class="badge bg-secondary">Utilisateur</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="text-end pe-3">
                            <button type="button"
                                    class="btn btn-sm btn-outline-warning py-0 px-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal"
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-role="{{ $user->role }}"
                                    data-is-last-admin="{{ $user->isAdmin() && $adminCount <= 1 ? 'true' : 'false' }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('Supprimer « {{ $user->name }} » ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>{{-- /tableController --}}
    </div>

    {{-- ─── Invitations en attente ─────────────────────────────────────── --}}
    @if($invitations->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-2">
            <span class="fw-semibold small">
                <i class="bi bi-envelope-open me-2 text-warning"></i>Invitations en attente
                <span class="badge bg-warning text-dark ms-1">{{ $invitations->count() }}</span>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.88rem">
                <thead class="table-light">
                    <tr>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Invité par</th>
                        <th>Expire le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invitations as $inv)
                    <tr>
                        <td class="font-monospace">{{ $inv->email }}</td>
                        <td>
                            @if($inv->role === 'admin')
                                <span class="badge bg-danger">Admin</span>
                            @else
                                <span class="badge bg-secondary">Utilisateur</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $inv->invitedBy?->name ?? '—' }}</td>
                        <td class="text-muted small">{{ $inv->expires_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end pe-3">
                            <form method="POST" action="{{ route('invitations.resend', $inv) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-2" title="Renvoyer l'email d'invitation">
                                    <i class="bi bi-send"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('invitations.destroy', $inv) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('Annuler cette invitation ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Annuler l'invitation">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ─── Invite modal ──────────────────────────────────────────────────── --}}
    <div class="modal fade" id="inviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-envelope-plus me-2"></i>Inviter un utilisateur</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('invitations.store') }}">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Un email contenant un lien d'invitation (valable 72h) sera envoyé à l'adresse indiquée.
                        </p>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Adresse email *</label>
                            <input type="email" name="email" class="form-control form-control-sm"
                                   value="{{ old('email') }}" required autofocus>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-medium">Rôle *</label>
                            <select name="role" class="form-select form-select-sm">
                                <option value="user" @selected(old('role','user')==='user')>Utilisateur</option>
                                <option value="admin" @selected(old('role')==='admin')>Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-send me-1"></i>Envoyer l'invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── Create modal ────────────────────────────────────────────────── --}}
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-person-plus me-2"></i>Créer un utilisateur</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="modal-body">
                        @if($errors->has('name') || $errors->has('email') || $errors->has('password'))
                            <div class="alert alert-danger py-2 small">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Nom *</label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                   value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Email *</label>
                            <input type="email" name="email" class="form-control form-control-sm"
                                   value="{{ old('email') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Rôle *</label>
                            <select name="role" class="form-select form-select-sm">
                                <option value="user" @selected(old('role','user')==='user')>Utilisateur</option>
                                <option value="admin" @selected(old('role')==='admin')>Administrateur</option>
                            </select>
                            <div class="form-text">Les admins accèdent à la gestion des utilisateurs et aux paramètres.</div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Mot de passe *</label>
                            <input type="password" name="password" class="form-control form-control-sm"
                                   autocomplete="new-password" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-medium">Confirmer le mot de passe *</label>
                            <input type="password" name="password_confirmation" class="form-control form-control-sm"
                                   autocomplete="new-password" required>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-sm btn-primary">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── Edit modal ──────────────────────────────────────────────────── --}}
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-pencil me-2"></i>Modifier l'utilisateur</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm" action="">
                    @csrf @method('PATCH')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Nom *</label>
                            <input type="text" name="name" id="editName" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Email *</label>
                            <input type="email" name="email" id="editEmail" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Rôle *</label>
                            <select name="role" id="editRole" class="form-select form-select-sm">
                                <option value="user">Utilisateur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                            <div id="lastAdminWarning" class="alert alert-warning py-2 mt-2 mb-0 small d-none">
                                <i class="bi bi-shield-lock-fill me-1"></i>
                                Seul administrateur du système — le rôle ne peut pas être modifié.
                            </div>
                        </div>
                        <hr>
                        <p class="text-muted small mb-2">Laisser vide pour ne pas changer le mot de passe.</p>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Nouveau mot de passe</label>
                            <input type="password" name="password" class="form-control form-control-sm"
                                   autocomplete="new-password">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-medium">Confirmer</label>
                            <input type="password" name="password_confirmation" class="form-control form-control-sm"
                                   autocomplete="new-password">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-sm btn-warning">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.getElementById('editModal').addEventListener('show.bs.modal', function (event) {
        const btn         = event.relatedTarget;
        const isLastAdmin = btn.dataset.isLastAdmin === 'true';
        const form        = document.getElementById('editForm');
        const roleSelect  = document.getElementById('editRole');
        const warning     = document.getElementById('lastAdminWarning');

        form.action = '/users/' + btn.dataset.id;
        document.getElementById('editName').value  = btn.dataset.name;
        document.getElementById('editEmail').value = btn.dataset.email;
        roleSelect.value = btn.dataset.role;

        roleSelect.disabled = isLastAdmin;
        warning.classList.toggle('d-none', !isLastAdmin);

        // A disabled select isn't submitted — keep a hidden fallback for the last admin
        let hidden = document.getElementById('editRoleHidden');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'role';
            hidden.id   = 'editRoleHidden';
            form.appendChild(hidden);
        }
        hidden.disabled = !isLastAdmin;
        hidden.value    = btn.dataset.role;
    });
    </script>
    @endpush
</x-app-layout>

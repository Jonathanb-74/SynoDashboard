<x-guest-layout>
    <h5 class="fw-semibold mb-1">Créer votre compte</h5>
    <p class="text-muted small mb-4">
        Vous avez été invité(e) en tant que
        <span class="badge {{ $invitation->role === 'admin' ? 'bg-danger' : 'bg-secondary' }}">
            {{ $invitation->role === 'admin' ? 'Administrateur' : 'Utilisateur' }}
        </span>
    </p>

    <form method="POST" action="{{ route('invitations.accept', $token) }}">
        @csrf

        {{-- Email (readonly, from invitation) --}}
        <div class="mb-3">
            <label class="form-label fw-medium">Adresse email</label>
            <input type="email" class="form-control bg-light" value="{{ $invitation->email }}" readonly>
        </div>

        <div class="mb-3">
            <label for="name" class="form-label fw-medium">Nom complet</label>
            <input id="name" type="text" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-medium">Mot de passe</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="new-password">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-medium">Confirmer le mot de passe</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-person-check me-2"></i>Créer mon compte
        </button>
    </form>
</x-guest-layout>

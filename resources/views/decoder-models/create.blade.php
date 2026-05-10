<x-app-layout>
    <x-slot name="title">Nouveau décodeur JSON</x-slot>

    <div class="card border-0 shadow-sm" style="max-width:600px">
        <div class="card-header bg-white">
            <h6 class="mb-0 fw-semibold">Nouveau décodeur JSON</h6>
        </div>
        <form method="POST" action="{{ route('decoder-models.store') }}">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">Nom *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                </div>
                <p class="text-muted small mb-0">
                    Après la création, vous pourrez ajouter des champs simples et des boucles.
                </p>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Créer et configurer
                </button>
                <a href="{{ route('decoder-models.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>

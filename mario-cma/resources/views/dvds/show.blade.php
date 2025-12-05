@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-disc"></i>
                        DVDs pour : <strong>{{ $film['title'] ?? 'Film inconnu' }}</strong>
                    </h5>
                    <div>
                        <button type="button"
                                class="btn btn-primary btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#addDvdModal">
                            <i class="bi bi-plus-circle"></i> Ajouter DVD
                        </button>
                        <a href="{{ route('dvds.index') }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Informations du film -->
                    <div class="alert alert-info mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Année :</strong> {{ $film['releaseYear'] ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Durée :</strong> {{ $film['length'] ?? 'N/A' }} min</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Note :</strong>
                                    @if(isset($film['rating']))
                                        <span class="badge bg-info">{{ $film['rating'] }}</span>
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </p>
                                <p class="mb-0"><strong>Total DVDs :</strong> {{ count($inventories) }}</p>
                            </div>
                        </div>
                    </div>

                    @if (empty($inventories))
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Aucun DVD disponible pour ce film.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID DVD</th>
                                        <th>Titre du film</th>
                                        <th>ID Store</th>
                                        <th>Disponibilité</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventories as $inventory)
                                        <tr>
                                            <td><strong>#{{ $inventory['inventoryId'] ?? $inventory['id'] ?? 'N/A' }}</strong></td>
                                            <td>{{ $film['title'] ?? 'Sans titre' }}</td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    Store #{{ $inventory['storeId'] ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(isset($inventory['available']) && $inventory['available'])
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> Disponible
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-x-circle"></i> En location
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('dvds.edit', $inventory['inventoryId'] ?? $inventory['id']) }}"
                                                       class="btn btn-sm btn-warning">
                                                        Modifier
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteDvdModal"
                                                            data-inventory-id="{{ $inventory['inventoryId'] ?? $inventory['id'] }}"
                                                            data-inventory-available="{{ $inventory['available'] ? 'true' : 'false' }}">
                                                        Supprimer
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <p class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Total : <strong>{{ count($inventories) }}</strong> DVD(s) pour ce film
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un DVD -->
<div class="modal fade" id="addDvdModal" tabindex="-1" aria-labelledby="addDvdModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('dvds.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addDvdModalLabel">Ajouter un DVD</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="filmId" value="{{ $film['filmId'] ?? $film['id'] }}">

                    <div class="mb-3">
                        <label class="form-label"><strong>Film :</strong></label>
                        <p class="text-muted">{{ $film['title'] ?? 'Sans titre' }}</p>
                    </div>

                    <div class="mb-3">
                        <label for="storeId" class="form-label">Store <span class="text-danger">*</span></label>
                        <select class="form-select @error('storeId') is-invalid @enderror"
                                id="storeId"
                                name="storeId"
                                required>
                            <option value="">-- Sélectionner un store --</option>
                            @foreach($stores as $store)
                                <option value="{{ $store['storeId'] ?? $store['id'] }}">
                                    Store #{{ $store['storeId'] ?? $store['id'] }} - {{ $store['address'] ?? 'Adresse non disponible' }}
                                </option>
                            @endforeach
                        </select>
                        @error('storeId')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Sélectionnez le store où ce DVD sera disponible
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour supprimer un DVD -->
<div class="modal fade" id="deleteDvdModal" tabindex="-1" aria-labelledby="deleteDvdModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="deleteDvdForm" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteDvdModalLabel">
                        <i class="bi bi-exclamation-triangle"></i> Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage">Êtes-vous sûr de vouloir supprimer ce DVD ?</p>
                    <p id="deleteWarning" class="text-danger small" style="display: none;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Attention :</strong> Ce DVD a un historique de location. La suppression supprimera également l'historique associé.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Script pour gérer la suppression de DVD
    document.addEventListener('DOMContentLoaded', function() {
        const deleteDvdModal = document.getElementById('deleteDvdModal');

        deleteDvdModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const inventoryId = button.getAttribute('data-inventory-id');
            const available = button.getAttribute('data-inventory-available');

            const form = document.getElementById('deleteDvdForm');
            form.action = "{{ route('dvds.destroy', ':id') }}".replace(':id', inventoryId);

            // Afficher un avertissement si le DVD a été loué (logique simplifiée)
            // Dans un cas réel, cette info devrait venir du backend
            const deleteWarning = document.getElementById('deleteWarning');
            if (available === 'false') {
                deleteWarning.style.display = 'block';
            } else {
                deleteWarning.style.display = 'none';
            }
        });
    });
</script>
@endsection
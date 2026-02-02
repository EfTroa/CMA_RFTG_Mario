@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Gestion des stocks DVD</h5>
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

                    @if (empty($films))
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Aucun film disponible ou erreur lors de la récupération des données de l'API.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Titre</th>
                                        <th>Description</th>
                                        <th>Année</th>
                                        <th>Durée</th>
                                        <th>Nombre de DVDs</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($films as $film)
                                        <tr style="cursor: pointer;" onclick="window.location='{{ route('dvds.show', $film['filmId'] ?? $film['id']) }}'">
                                            <td><strong>{{ $film['title'] ?? 'Sans titre' }}</strong></td>
                                            <td>{{ Str::limit($film['description'] ?? 'Aucune description', 80) }}</td>
                                            <td>{{ $film['releaseYear'] ?? 'N/A' }}</td>
                                            <td>{{ $film['length'] ?? 'N/A' }} min</td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ $film['dvdCount'] ?? 0 }} DVD(s)
                                                </span>
                                            </td>
                                            <td onclick="event.stopPropagation();">
                                                <button type="button"
                                                        class="btn btn-sm btn-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#addDvdModal"
                                                        data-film-id="{{ $film['filmId'] ?? $film['id'] }}"
                                                        data-film-title="{{ $film['title'] ?? 'Sans titre' }}">
                                                    <i class="bi bi-plus-circle"></i> Ajouter DVD
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <p class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Total : <strong>{{ count($films) }}</strong> film(s)
                            </p>
                            <p class="text-muted">
                                <i class="bi bi-hand-index"></i>
                                Cliquez sur un film pour voir ses DVDs
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
                    <input type="hidden" name="filmId" id="modalFilmId">

                    <div class="mb-3">
                        <label class="form-label"><strong>Film :</strong></label>
                        <p id="modalFilmTitle" class="text-muted"></p>
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

@endsection

@section('scripts')
<script>
    // Script pour peupler le modal avec les données du film
    document.addEventListener('DOMContentLoaded', function() {
        const addDvdModal = document.getElementById('addDvdModal');

        addDvdModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const filmId = button.getAttribute('data-film-id');
            const filmTitle = button.getAttribute('data-film-title');

            document.getElementById('modalFilmId').value = filmId;
            document.getElementById('modalFilmTitle').textContent = filmTitle;
        });
    });
</script>
@endsection
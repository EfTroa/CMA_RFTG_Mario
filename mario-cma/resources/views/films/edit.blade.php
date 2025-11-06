@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Modifier le film</h5>
                    <a href="{{ route('films.show', $film['filmId'] ?? $film['id']) }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Retour au film
                    </a>
                </div>

                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle"></i>
                        <strong>Note :</strong> La modification des films n'est pas encore activée. Vous pouvez visualiser les champs pré-remplis, mais le bouton de sauvegarde est désactivé pour le moment.
                    </div>

                    <form action="{{ route('films.update', $film['filmId'] ?? $film['id']) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('title') is-invalid @enderror"
                                       id="title"
                                       name="title"
                                       value="{{ old('title', $film['title'] ?? '') }}"
                                       required
                                       placeholder="Entrez le titre du film">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="releaseYear" class="form-label">Année de sortie <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control @error('releaseYear') is-invalid @enderror"
                                       id="releaseYear"
                                       name="releaseYear"
                                       value="{{ old('releaseYear', $film['releaseYear'] ?? '') }}"
                                       min="1900"
                                       max="2100"
                                       required
                                       placeholder="2024">
                                @error('releaseYear')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="4"
                                      placeholder="Décrivez le film...">{{ old('description', $film['description'] ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="languageId" class="form-label">Langue (ID) <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control @error('languageId') is-invalid @enderror"
                                       id="languageId"
                                       name="languageId"
                                       value="{{ old('languageId', $film['languageId'] ?? 1) }}"
                                       min="1"
                                       required>
                                <small class="form-text text-muted">Ex: 1 = Anglais, 2 = Français</small>
                                @error('languageId')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="length" class="form-label">Durée (minutes)</label>
                                <input type="number"
                                       class="form-control @error('length') is-invalid @enderror"
                                       id="length"
                                       name="length"
                                       value="{{ old('length', $film['length'] ?? '') }}"
                                       min="1"
                                       max="500"
                                       placeholder="120">
                                @error('length')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="rating" class="form-label">Note</label>
                                <select class="form-select @error('rating') is-invalid @enderror"
                                        id="rating"
                                        name="rating">
                                    <option value="">-- Choisir --</option>
                                    <option value="G" {{ old('rating', $film['rating'] ?? '') == 'G' ? 'selected' : '' }}>G</option>
                                    <option value="PG" {{ old('rating', $film['rating'] ?? '') == 'PG' ? 'selected' : '' }}>PG</option>
                                    <option value="PG-13" {{ old('rating', $film['rating'] ?? '') == 'PG-13' ? 'selected' : '' }}>PG-13</option>
                                    <option value="R" {{ old('rating', $film['rating'] ?? '') == 'R' ? 'selected' : '' }}>R</option>
                                    <option value="NC-17" {{ old('rating', $film['rating'] ?? '') == 'NC-17' ? 'selected' : '' }}>NC-17</option>
                                </select>
                                @error('rating')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="rentalDuration" class="form-label">Durée de location (jours) <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control @error('rentalDuration') is-invalid @enderror"
                                       id="rentalDuration"
                                       name="rentalDuration"
                                       value="{{ old('rentalDuration', $film['rentalDuration'] ?? 3) }}"
                                       min="1"
                                       max="30"
                                       required
                                       placeholder="3">
                                <small class="form-text text-muted">Nombre de jours de location</small>
                                @error('rentalDuration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="rentalRate" class="form-label">Tarif de location (€) <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control @error('rentalRate') is-invalid @enderror"
                                       id="rentalRate"
                                       name="rentalRate"
                                       value="{{ old('rentalRate', $film['rentalRate'] ?? 4.99) }}"
                                       min="0"
                                       step="0.01"
                                       required
                                       placeholder="4.99">
                                @error('rentalRate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="replacementCost" class="form-label">Coût de remplacement (€) <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control @error('replacementCost') is-invalid @enderror"
                                       id="replacementCost"
                                       name="replacementCost"
                                       value="{{ old('replacementCost', $film['replacementCost'] ?? 19.99) }}"
                                       min="0"
                                       step="0.01"
                                       required
                                       placeholder="19.99">
                                @error('replacementCost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="specialFeatures" class="form-label">Caractéristiques spéciales</label>
                                <input type="text"
                                       class="form-control @error('specialFeatures') is-invalid @enderror"
                                       id="specialFeatures"
                                       name="specialFeatures"
                                       value="{{ old('specialFeatures', $film['specialFeatures'] ?? '') }}"
                                       placeholder="Trailers, Commentaries, Deleted Scenes">
                                @error('specialFeatures')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('films.show', $film['filmId'] ?? $film['id']) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-warning" disabled title="Fonctionnalité désactivée">
                                <i class="bi bi-save"></i> Enregistrer les modifications (désactivé)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

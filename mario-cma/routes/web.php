<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\FilmController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Routes films protégées par authentification
Route::middleware('auth')->group(function () {
    // Liste des films
    Route::get('/films', [FilmController::class, 'index'])->name('films.index');

    // Formulaire d'ajout (AVANT /films/{id} pour éviter que "create" soit considéré comme un ID)
    Route::get('/films/create', [FilmController::class, 'create'])->name('films.create');

    // Création d'un film
    Route::post('/films', [FilmController::class, 'store'])->name('films.store');

    // Affichage d'un film
    Route::get('/films/{id}', [FilmController::class, 'show'])->name('films.show');

    // Formulaire d'édition
    Route::get('/films/{id}/edit', [FilmController::class, 'edit'])->name('films.edit');

    // Mise à jour d'un film (désactivé pour l'instant)
    Route::put('/films/{id}', [FilmController::class, 'update'])->name('films.update');

    // Suppression d'un film
    Route::delete('/films/{id}', [FilmController::class, 'destroy'])->name('films.destroy');
});

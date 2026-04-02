<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\FilmController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RentalController;

Route::get('/', function () {
    return redirect('/login');
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

    // Routes Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Routes Rentals
    Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
    Route::put('/rentals/{id}/status', [RentalController::class, 'updateStatus'])->name('rentals.updateStatus');

    // Routes DVDs (Inventory management)
    // Liste des films avec gestion DVD
    Route::get('/dvds', [InventoryController::class, 'index'])->name('dvds.index');

    // Affichage des DVDs pour un film spécifique
    Route::get('/dvds/film/{filmId}', [InventoryController::class, 'show'])->name('dvds.show');

    // Création d'un DVD (ajout à un film)
    Route::post('/dvds', [InventoryController::class, 'store'])->name('dvds.store');

    // Formulaire d'édition d'un DVD
    Route::get('/dvds/{inventoryId}/edit', [InventoryController::class, 'edit'])->name('dvds.edit');

    // Mise à jour d'un DVD (migration de store)
    Route::put('/dvds/{inventoryId}', [InventoryController::class, 'update'])->name('dvds.update');

    // Suppression d'un DVD
    Route::delete('/dvds/{inventoryId}', [InventoryController::class, 'destroy'])->name('dvds.destroy');
});

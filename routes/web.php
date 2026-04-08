<?php

/*
|--------------------------------------------------------------------------
| routes/web.php — Fichier de définition des routes HTTP de l'application
|--------------------------------------------------------------------------
|
| Ce fichier déclare toutes les URL accessibles dans l'application web.
| Chaque route fait le lien entre une URL (+ méthode HTTP) et une méthode
| d'un contrôleur. C'est le "chef d'orchestre" qui dit : "quand l'utilisateur
| va sur /films, appelle FilmController@index".
|
| Méthodes HTTP utilisées :
|   GET    → lire/afficher des données
|   POST   → créer une nouvelle ressource
|   PUT    → modifier entièrement une ressource existante
|   DELETE → supprimer une ressource
|
*/

// Import du Facade Route : classe utilitaire Laravel pour déclarer les routes
use Illuminate\Support\Facades\Route;

// Import du Facade Auth : utilisé ici pour générer automatiquement les routes d'auth
use Illuminate\Support\Facades\Auth;

// Import des contrôleurs métier de l'application
use App\Http\Controllers\FilmController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RentalController;

/*
|--------------------------------------------------------------------------
| Route racine → redirection vers la page de connexion
|--------------------------------------------------------------------------
| Quand l'utilisateur arrive sur "/", on le redirige directement vers
| "/login" car l'application nécessite une authentification.
*/
Route::get('/', function () {
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| Routes d'authentification générées automatiquement par Laravel
|--------------------------------------------------------------------------
| Auth::routes() crée d'un coup toutes les routes nécessaires pour :
|   - /login          (GET + POST)  → connexion
|   - /logout         (POST)        → déconnexion
|   - /register       (GET + POST)  → inscription
|   - /password/reset (GET + POST)  → réinitialisation de mot de passe
| Ces routes sont gérées par les contrôleurs dans app/Http/Controllers/Auth/
*/
Auth::routes();

/*
|--------------------------------------------------------------------------
| Route du tableau de bord (dashboard) après connexion
|--------------------------------------------------------------------------
| name('home') assigne un nom à la route → on peut l'utiliser avec route('home')
| dans les vues Blade ou les redirections PHP, sans coder l'URL en dur.
*/
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Groupe de routes protégées par le middleware 'auth'
|--------------------------------------------------------------------------
| Le middleware 'auth' vérifie que l'utilisateur est connecté avant d'accéder
| à ces routes. Si non connecté → redirection automatique vers /login.
| Toutes les routes métier (films, clients, locations, DVDs) sont protégées.
*/
Route::middleware('auth')->group(function () {

    /*
    |----------------------------------------------------------------------
    | ROUTES FILMS — CRUD complet du catalogue de films
    |----------------------------------------------------------------------
    | On déclare la route /films/create AVANT /films/{id}
    | car Laravel lit les routes dans l'ordre : si "create" était après,
    | Laravel l'interpréterait comme un ID et appellerait show('create').
    */

    // Affiche la liste paginée de tous les films
    Route::get('/films', [FilmController::class, 'index'])->name('films.index');

    // Affiche le formulaire de création d'un film (doit être avant /{id} !)
    Route::get('/films/create', [FilmController::class, 'create'])->name('films.create');

    // Traite la soumission du formulaire de création (envoie à l'API Toad)
    Route::post('/films', [FilmController::class, 'store'])->name('films.store');

    // Affiche le détail d'un film par son identifiant
    Route::get('/films/{id}', [FilmController::class, 'show'])->name('films.show');

    // Affiche le formulaire de modification d'un film existant
    Route::get('/films/{id}/edit', [FilmController::class, 'edit'])->name('films.edit');

    // Traite la soumission du formulaire de modification (PUT = mise à jour complète)
    Route::put('/films/{id}', [FilmController::class, 'update'])->name('films.update');

    // Supprime un film (DELETE) — déclenché via formulaire HTML avec @method('DELETE')
    Route::delete('/films/{id}', [FilmController::class, 'destroy'])->name('films.destroy');

    /*
    |----------------------------------------------------------------------
    | ROUTES CLIENTS — CRUD complet de la gestion des clients
    |----------------------------------------------------------------------
    | Même logique que les films : create avant {id}.
    */

    // Liste tous les clients
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    // Formulaire de création d'un client
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');

    // Enregistre un nouveau client via l'API Toad
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');

    // Affiche le détail d'un client
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');

    // Formulaire de modification d'un client existant
    Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');

    // Met à jour les données d'un client via l'API Toad
    Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customers.update');

    // Supprime un client
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    /*
    |----------------------------------------------------------------------
    | ROUTES LOCATIONS — Gestion du statut des locations
    |----------------------------------------------------------------------
    | Ici, pas de création ni de suppression depuis l'interface :
    | on peut seulement lister et changer le statut d'une location existante.
    */

    // Liste toutes les locations avec leur statut
    Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');

    // Met à jour le statut d'une location (ex: En cours → Terminé)
    Route::put('/rentals/{id}/status', [RentalController::class, 'updateStatus'])->name('rentals.updateStatus');

    /*
    |----------------------------------------------------------------------
    | ROUTES DVDs (Inventaire) — Gestion du stock physique de DVDs
    |----------------------------------------------------------------------
    | Un DVD = une ligne dans la table inventory (filmId + storeId).
    | Plusieurs DVDs peuvent exister pour le même film (copies physiques).
    */

    // Liste tous les films avec leur nombre de DVDs disponibles
    Route::get('/dvds', [InventoryController::class, 'index'])->name('dvds.index');

    // Affiche tous les DVDs d'un film spécifique
    Route::get('/dvds/film/{filmId}', [InventoryController::class, 'show'])->name('dvds.show');

    // Ajoute un nouveau DVD (nouvelle copie physique) pour un film
    Route::post('/dvds', [InventoryController::class, 'store'])->name('dvds.store');

    // Formulaire pour déplacer un DVD (changer son magasin)
    Route::get('/dvds/{inventoryId}/edit', [InventoryController::class, 'edit'])->name('dvds.edit');

    // Applique le déplacement d'un DVD vers un autre magasin
    Route::put('/dvds/{inventoryId}', [InventoryController::class, 'update'])->name('dvds.update');

    // Supprime un DVD (et son historique de location si nécessaire)
    Route::delete('/dvds/{inventoryId}', [InventoryController::class, 'destroy'])->name('dvds.destroy');
});

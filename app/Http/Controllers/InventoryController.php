<?php

/*
|--------------------------------------------------------------------------
| InventoryController.php — Contrôleur de gestion du stock DVD
|--------------------------------------------------------------------------
|
| Ce contrôleur gère les "inventaires" : chaque ligne d'inventaire représente
| un DVD physique (un exemplaire d'un film dans un magasin).
|
| Modèle de données :
|   Un film peut avoir N DVDs (N copies physiques).
|   Chaque DVD est lié à : un film (filmId) + un magasin (storeId).
|
| Particularité importante : contrainte ON DELETE RESTRICT
|   La BDD Sakila interdit de supprimer un DVD s'il a un historique de location.
|   Ce contrôleur gère ce cas en supprimant d'abord les locations associées.
|
| Ce contrôleur utilise DEUX services :
|   - ToadInventoryService : gestion des DVDs (inventaires)
|   - ToadFilmService      : récupération des infos des films
|
*/

namespace App\Http\Controllers;

// Service pour gérer les inventaires (DVDs) via l'API Toad
use App\Services\ToadInventoryService;

// Service pour récupérer les informations des films (titres, années, etc.)
use App\Services\ToadFilmService;

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Classe InventoryController
|--------------------------------------------------------------------------
*/
class InventoryController extends Controller
{
    // Service de gestion du stock DVD
    private ToadInventoryService $inventoryService;

    // Service des films (pour afficher le titre, l'année, etc.)
    private ToadFilmService $filmService;

    /**
     * Constructeur : injection des deux services nécessaires.
     *
     * Laravel injecte automatiquement les deux instances grâce à
     * l'injection de dépendances (type hinting dans les paramètres).
     *
     * @param ToadInventoryService $inventoryService
     * @param ToadFilmService $filmService
     */
    public function __construct(ToadInventoryService $inventoryService, ToadFilmService $filmService)
    {
        $this->middleware('auth');
        $this->inventoryService = $inventoryService;
        $this->filmService = $filmService;
    }

    /**
     * Affiche la liste de tous les films avec leur nombre de DVDs.
     *
     * Pour chaque film, on compte le nombre de DVDs disponibles.
     * Cette méthode fait donc N+1 appels API (1 pour les films + 1 par film).
     * Ce n'est pas optimal, mais c'est la contrainte de l'API externe.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Récupère tous les films depuis l'API
        $films = $this->filmService->getAllFilms();

        // Récupère la liste des magasins pour le formulaire d'ajout de DVD
        $stores = $this->inventoryService->getAllStores();

        // Pour chaque film, on ajoute le nombre de DVDs qu'il possède
        if ($films) {
            // Le & devant $film est crucial : passage par référence
            // Sans &, $film est une copie et la modification ne serait pas conservée
            foreach ($films as &$film) {
                // Récupère tous les DVDs pour ce film (gère les deux noms de clé possibles)
                $inventories = $this->inventoryService->getInventoriesByFilmId($film['filmId'] ?? $film['id']);
                // Ajoute le comptage au tableau du film (0 si l'API retourne null)
                $film['dvdCount'] = $inventories ? count($inventories) : 0;
            }
        }

        return view('dvds.index', [
            'films'  => $films ?? [],
            'stores' => $stores ?? []
        ]);
    }

    /**
     * Affiche tous les DVDs (inventaires) d'un film spécifique.
     *
     * Pour chaque DVD, on vérifie également s'il est disponible
     * (non actuellement en location) via l'API.
     *
     * @param int|string $filmId  ID du film dont on veut voir les DVDs
     * @return \Illuminate\View\View
     */
    public function show($filmId)
    {
        // Récupère le film pour afficher ses informations en en-tête
        $film = $this->filmService->getFilmById($filmId);

        if (!$film) {
            abort(404, 'Film non trouvé');
        }

        // Récupère tous les DVDs de ce film
        $inventories = $this->inventoryService->getInventoriesByFilmId($filmId);

        // Pour chaque DVD, vérifie sa disponibilité (disponible/en location)
        if ($inventories) {
            foreach ($inventories as &$inventory) {
                // Appel API pour savoir si ce DVD est actuellement loué
                $availability = $this->inventoryService->checkInventoryAvailability($inventory['inventoryId']);
                // Si l'API ne répond pas, on suppose que le DVD est disponible (true)
                $inventory['available'] = $availability['available'] ?? true;
            }
        }

        // Liste des magasins pour le formulaire d'ajout de DVD (modal Bootstrap)
        $stores = $this->inventoryService->getAllStores();

        return view('dvds.show', [
            'film'        => $film,
            'inventories' => $inventories ?? [],
            'stores'      => $stores ?? []
        ]);
    }

    /**
     * Enregistre un nouveau DVD (nouvelle copie physique d'un film).
     *
     * Reçoit filmId + storeId et crée une nouvelle ligne dans la table inventory.
     * Redirige ensuite vers la liste des DVDs de ce film.
     *
     * @param Request $request  filmId et storeId du formulaire
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'filmId'  => 'required|integer|min:1',
            'storeId' => 'required|integer|min:1',
        ], [
            'filmId.required'  => 'L\'ID du film est obligatoire.',
            'storeId.required' => 'Le store est obligatoire.',
        ]);

        $newInventory = $this->inventoryService->createInventory($validatedData);

        if ($newInventory) {
            // Redirige vers la vue des DVDs du film (pas vers dvds.index)
            return redirect()
                ->route('dvds.show', $validatedData['filmId'])
                ->with('success', 'DVD ajouté avec succès !');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Erreur lors de l\'ajout du DVD. Veuillez réessayer.');
    }

    /**
     * Affiche le formulaire d'édition d'un DVD (pour changer son magasin).
     *
     * Le seul champ modifiable est storeId : un DVD ne peut pas changer de film,
     * mais peut être transféré dans un autre magasin.
     *
     * @param int|string $inventoryId  ID de l'inventaire (DVD) à modifier
     * @return \Illuminate\View\View
     */
    public function edit($inventoryId)
    {
        // Récupère les données actuelles du DVD
        $inventory = $this->inventoryService->getInventoryById($inventoryId);

        if (!$inventory) {
            abort(404, 'DVD non trouvé');
        }

        // Récupère le film associé pour afficher ses infos dans le formulaire
        $film   = $this->filmService->getFilmById($inventory['filmId']);
        $stores = $this->inventoryService->getAllStores();

        return view('dvds.edit', [
            'inventory' => $inventory,
            'film'      => $film,
            'stores'    => $stores ?? []
        ]);
    }

    /**
     * Met à jour un DVD (change son magasin de rattachement).
     *
     * On récupère d'abord l'inventaire existant pour conserver filmId
     * (inchangé) et n'envoyer à l'API que les données complètes et valides.
     *
     * @param Request $request      Nouveau storeId
     * @param int|string $inventoryId  ID du DVD à mettre à jour
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $inventoryId)
    {
        $validatedData = $request->validate([
            'storeId' => 'required|integer|min:1',
        ], [
            'storeId.required' => 'Le store est obligatoire.',
        ]);

        // Récupère l'inventaire existant pour extraire filmId (requis par l'API PUT)
        $inventory = $this->inventoryService->getInventoryById($inventoryId);

        if (!$inventory) {
            abort(404, 'DVD non trouvé');
        }

        // Construit le payload complet : filmId inchangé + nouveau storeId
        // (int) force le type entier car l'API attend des entiers, pas des chaînes
        $payload = [
            'filmId'  => (int) $inventory['filmId'],
            'storeId' => (int) $validatedData['storeId'],
        ];

        $updatedInventory = $this->inventoryService->updateInventory($inventoryId, $payload);

        if ($updatedInventory) {
            // Redirige vers la liste des DVDs du film
            return redirect()
                ->route('dvds.show', $inventory['filmId'])
                ->with('success', 'DVD mis à jour avec succès !');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Erreur lors de la mise à jour du DVD. Veuillez réessayer.');
    }

    /**
     * Supprime un DVD avec gestion de la contrainte ON DELETE RESTRICT.
     *
     * La BDD Sakila a une contrainte de clé étrangère : on ne peut pas supprimer
     * un inventaire s'il a des locations associées (ON DELETE RESTRICT).
     * Pour contourner cela :
     *   1. On vérifie que le DVD n'est pas en location active
     *   2. On supprime d'abord toutes les locations historiques associées
     *   3. Ensuite seulement, on supprime le DVD
     *
     * @param int|string $inventoryId  ID du DVD à supprimer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($inventoryId)
    {
        // Récupère le DVD (pour avoir filmId pour la redirection finale)
        $inventory = $this->inventoryService->getInventoryById($inventoryId);

        if (!$inventory) {
            abort(404, 'DVD non trouvé');
        }

        // Vérifie si ce DVD est actuellement en location active
        $availability = $this->inventoryService->checkInventoryAvailability($inventoryId);

        // Si le DVD est en cours de location → refus de suppression
        if (isset($availability['available']) && !$availability['available']) {
            return redirect()
                ->back()
                ->with('error', 'Impossible de supprimer ce DVD. Il est actuellement en location.');
        }

        // Récupère l'historique complet des locations de ce DVD
        $rentals = $this->inventoryService->getRentalsByInventoryId($inventoryId);

        // Si le DVD a un historique de location → supprime chaque location d'abord
        // (nécessaire pour lever la contrainte FK avant de supprimer l'inventaire)
        if ($rentals && count($rentals) > 0) {
            foreach ($rentals as $rental) {
                // Gère les deux noms de clé possibles selon la version de l'API
                $rentalId = $rental['rentalId'] ?? $rental['id'];
                $deleteRentalSuccess = $this->inventoryService->deleteRental($rentalId);

                // Si une suppression de location échoue → arrêt et message d'erreur
                if (!$deleteRentalSuccess) {
                    return redirect()
                        ->back()
                        ->with('error', 'Erreur lors de la suppression de l\'historique de location. Veuillez réessayer.');
                }
            }
        }

        // Maintenant que les locations sont supprimées, on peut supprimer le DVD
        $success = $this->inventoryService->deleteInventory($inventoryId);

        if ($success) {
            return redirect()
                ->route('dvds.show', $inventory['filmId'])
                ->with('success', 'DVD supprimé avec succès !');
        }

        return redirect()
            ->back()
            ->with('error', 'Erreur lors de la suppression du DVD. Veuillez réessayer.');
    }
}

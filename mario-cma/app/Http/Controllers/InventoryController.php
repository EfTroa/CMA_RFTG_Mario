<?php

namespace App\Http\Controllers;

use App\Services\ToadInventoryService;
use App\Services\ToadFilmService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private ToadInventoryService $inventoryService;
    private ToadFilmService $filmService;

    public function __construct(ToadInventoryService $inventoryService, ToadFilmService $filmService)
    {
        $this->middleware('auth');
        $this->inventoryService = $inventoryService;
        $this->filmService = $filmService;
    }

    /**
     * Display film list with "Ajouter DVD" button
     */
    public function index()
    {
        $films = $this->filmService->getAllFilms();
        $stores = $this->inventoryService->getAllStores();

        // Ajouter le comptage de DVDs pour chaque film
        if ($films) {
            foreach ($films as &$film) {
                $inventories = $this->inventoryService->getInventoriesByFilmId($film['filmId'] ?? $film['id']);
                $film['dvdCount'] = $inventories ? count($inventories) : 0;
            }
        }

        return view('dvds.index', [
            'films' => $films ?? [],
            'stores' => $stores ?? []
        ]);
    }

    /**
     * Display all DVDs (inventory rows) for a specific film
     */
    public function show($filmId)
    {
        $film = $this->filmService->getFilmById($filmId);

        if (!$film) {
            abort(404, 'Film non trouvé');
        }

        $inventories = $this->inventoryService->getInventoriesByFilmId($filmId);

        // Check availability for each inventory
        if ($inventories) {
            foreach ($inventories as &$inventory) {
                $availability = $this->inventoryService->checkInventoryAvailability($inventory['inventoryId']);
                $inventory['available'] = $availability['available'] ?? true;
            }
        }

        $stores = $this->inventoryService->getAllStores();

        return view('dvds.show', [
            'film' => $film,
            'inventories' => $inventories ?? [],
            'stores' => $stores ?? []
        ]);
    }

    /**
     * Store a new inventory row (add DVD)
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'filmId' => 'required|integer|min:1',
            'storeId' => 'required|integer|min:1',
        ], [
            'filmId.required' => 'L\'ID du film est obligatoire.',
            'storeId.required' => 'Le store est obligatoire.',
        ]);

        $newInventory = $this->inventoryService->createInventory($validatedData);

        if ($newInventory) {
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
     * Show form to edit inventory row (change store_id)
     */
    public function edit($inventoryId)
    {
        $inventory = $this->inventoryService->getInventoryById($inventoryId);

        if (!$inventory) {
            abort(404, 'DVD non trouvé');
        }

        $film = $this->filmService->getFilmById($inventory['filmId']);
        $stores = $this->inventoryService->getAllStores();

        return view('dvds.edit', [
            'inventory' => $inventory,
            'film' => $film,
            'stores' => $stores ?? []
        ]);
    }

    /**
     * Update inventory row (change store_id)
     */
    public function update(Request $request, $inventoryId)
    {
        $validatedData = $request->validate([
            'storeId' => 'required|integer|min:1',
        ], [
            'storeId.required' => 'Le store est obligatoire.',
        ]);

        $inventory = $this->inventoryService->getInventoryById($inventoryId);

        if (!$inventory) {
            abort(404, 'DVD non trouvé');
        }

        $updatedInventory = $this->inventoryService->updateInventory($inventoryId, $validatedData);

        if ($updatedInventory) {
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
     * Delete inventory row with ON DELETE RESTRICT constraint handling
     */
    public function destroy($inventoryId)
    {
        $inventory = $this->inventoryService->getInventoryById($inventoryId);

        if (!$inventory) {
            abort(404, 'DVD non trouvé');
        }

        // Check if DVD is available (not currently rented)
        $availability = $this->inventoryService->checkInventoryAvailability($inventoryId);

        if (isset($availability['available']) && !$availability['available']) {
            return redirect()
                ->back()
                ->with('error', 'Impossible de supprimer ce DVD. Il est actuellement en location.');
        }

        // Get rental history to handle ON DELETE RESTRICT constraint
        $rentals = $this->inventoryService->getRentalsByInventoryId($inventoryId);

        // If there are rentals, delete them first
        if ($rentals && count($rentals) > 0) {
            foreach ($rentals as $rental) {
                $rentalId = $rental['rentalId'] ?? $rental['id'];
                $deleteRentalSuccess = $this->inventoryService->deleteRental($rentalId);

                if (!$deleteRentalSuccess) {
                    return redirect()
                        ->back()
                        ->with('error', 'Erreur lors de la suppression de l\'historique de location. Veuillez réessayer.');
                }
            }
        }

        // Now delete the inventory row
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
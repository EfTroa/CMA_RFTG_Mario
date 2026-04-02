<?php

namespace App\Http\Controllers;

use App\Services\ToadRentalService;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    private ToadRentalService $rentalService;

    private array $statuses = [
        1 => 'Terminé',
        2 => 'Dans le panier',
        3 => 'En cours',
    ];

    public function __construct(ToadRentalService $rentalService)
    {
        $this->middleware('auth');
        $this->rentalService = $rentalService;
    }

    public function index()
    {
        $rentals = $this->rentalService->getAllRentals();

        return view('rentals.index', [
            'rentals'  => $rentals ?? [],
            'statuses' => $this->statuses,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'statusId'    => 'required|integer|in:1,2,3',
            'rentalDate'  => 'required',
            'inventoryId' => 'required|integer',
            'customerId'  => 'required|integer',
        ]);

        $data = [
            'rentalId'    => (int) $id,
            'rentalDate'  => $request->input('rentalDate'),
            'inventoryId' => (int) $request->input('inventoryId'),
            'customerId'  => (int) $request->input('customerId'),
            'staffId'     => $request->input('staffId') ? (int) $request->input('staffId') : null,
            'returnDate'  => $request->input('returnDate') ?: null,
            'statusId'    => (int) $request->input('statusId'),
        ];

        $updated = $this->rentalService->updateRental((int) $id, $data);

        if ($updated) {
            return redirect()->route('rentals.index')->with('success', 'Statut mis à jour avec succès !');
        }

        return redirect()->route('rentals.index')->with('error', 'Erreur lors de la mise à jour du statut.');
    }
}
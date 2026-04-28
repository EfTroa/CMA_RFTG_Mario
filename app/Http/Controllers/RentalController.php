<?php

namespace App\Http\Controllers;

use App\Services\ToadRentalService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RentalController extends Controller
{
    private ToadRentalService $rentalService;

    private array $statuses = [
        1 => 'Terminé',
        2 => 'Dans le panier',
        3 => 'En cours',
    ];

    // Statuses available in the update dropdown — status 2 (panier) is excluded
    // because /rentals/all filters it out, making the rental disappear from the list.
    private array $editableStatuses = [
        1 => 'Terminé',
        3 => 'En cours',
    ];

    public function __construct(ToadRentalService $rentalService)
    {
        $this->middleware('auth');
        $this->rentalService = $rentalService;
    }

    public function index(Request $request)
    {
        $perPage = 20;
        $page    = (int) $request->get('page', 1);
        $offset  = ($page - 1) * $perPage;

        $rentals = $this->rentalService->getAllRentals($perPage, $offset);
        $total   = $this->rentalService->getRentalsCount();

        $paginator = new LengthAwarePaginator(
            $rentals ?? [],
            $total,
            $perPage,
            $page,
            ['path' => $request->url()]
        );

        return view('rentals.index', [
            'rentals'         => $paginator,
            'statuses'        => $this->statuses,
            'editableStatuses' => $this->editableStatuses,
        ]);
    }

    public function create()
    {
        return view('rentals.create', [
            'statuses' => $this->statuses,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customerId'  => 'required|integer|min:1',
            'inventoryId' => 'required|integer|min:1',
            'staffId'     => 'nullable|integer|min:1',
            'statusId'    => 'required|integer|in:1,2,3',
            'rentalDate'  => 'required|date',
            'returnDate'  => 'nullable|date|after_or_equal:rentalDate',
        ], [
            'customerId.required'  => 'L\'ID client est obligatoire.',
            'inventoryId.required' => 'L\'ID inventaire est obligatoire.',
            'statusId.required'    => 'Le statut est obligatoire.',
            'rentalDate.required'  => 'La date de location est obligatoire.',
            'returnDate.after_or_equal' => 'La date de retour doit être après la date de location.',
        ]);

        $data = [
            'customerId'  => (int) $validatedData['customerId'],
            'inventoryId' => (int) $validatedData['inventoryId'],
            'staffId'     => isset($validatedData['staffId']) ? (int) $validatedData['staffId'] : null,
            'statusId'    => (int) $validatedData['statusId'],
            'rentalDate'  => $validatedData['rentalDate'],
            'returnDate'  => $validatedData['returnDate'] ?? null,
        ];

        $newRental = $this->rentalService->createRental($data);

        if ($newRental) {
            return redirect()
                ->route('rentals.index')
                ->with('success', 'Location créée avec succès !');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Erreur lors de la création de la location. Veuillez réessayer.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'statusId'    => 'required|integer|in:1,2,3',
            'rentalDate'  => 'required',
            'inventoryId' => 'required|integer',
            'customerId'  => 'required|integer',
        ]);

        // staff_id is NOT NULL in the DB but is absent from RentalHistoryDTO.
        // Fetch the full rental to get the current staffId before updating.
        $existing = $this->rentalService->getRentalById((int) $id);
        $staffId  = $existing['staffId'] ?? null;

        $data = [
            'rentalId'    => (int) $id,
            'rentalDate'  => $request->input('rentalDate'),
            'inventoryId' => (int) $request->input('inventoryId'),
            'customerId'  => (int) $request->input('customerId'),
            'staffId'     => $staffId,
            'returnDate'  => $request->input('returnDate') ?: null,
            'statusId'    => (int) $request->input('statusId'),
        ];

        $updated = $this->rentalService->updateRental((int) $id, $data);

        if ($updated) {
            return redirect()->route('rentals.index')->with('success', 'Statut mis à jour avec succès !');
        }

        return redirect()->route('rentals.index')->with('error', 'Erreur lors de la mise à jour du statut.');
    }

    public function destroy($id)
    {
        $success = $this->rentalService->deleteRental((int) $id);

        if ($success) {
            return redirect()
                ->route('rentals.index')
                ->with('success', 'Location supprimée avec succès !');
        }

        return redirect()
            ->back()
            ->with('error', 'Erreur lors de la suppression de la location. Veuillez réessayer.');
    }
}

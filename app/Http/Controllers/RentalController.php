<?php

/*
|--------------------------------------------------------------------------
| RentalController.php — Contrôleur de gestion des locations
|--------------------------------------------------------------------------
|
| Ce contrôleur est plus limité que les autres : il permet seulement
| de LISTER les locations et de CHANGER leur statut.
|
| On ne crée pas de location depuis cette interface (c'est géré par
| l'API côté client ou panier), mais on peut modifier son état :
|   1 = Terminé
|   2 = Dans le panier
|   3 = En cours
|
*/

namespace App\Http\Controllers;

// Service dédié aux appels API Toad pour les locations
use App\Services\ToadRentalService;

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Classe RentalController
|--------------------------------------------------------------------------
*/
class RentalController extends Controller
{
    // Instance du service de locations
    private ToadRentalService $rentalService;

    /**
     * Tableau associatif des statuts de location (ID → Libellé).
     *
     * Ce tableau est déclaré comme propriété de classe pour pouvoir
     * être réutilisé dans plusieurs méthodes (index, updateStatus).
     * Il est passé à la vue pour afficher les libellés dans les menus déroulants.
     *
     * @var array
     */
    private array $statuses = [
        1 => 'Terminé',
        2 => 'Dans le panier',
        3 => 'En cours',
    ];

    /**
     * Constructeur : injection du service + middleware d'authentification.
     *
     * @param ToadRentalService $rentalService  Injecté par Laravel
     */
    public function __construct(ToadRentalService $rentalService)
    {
        $this->middleware('auth');
        $this->rentalService = $rentalService;
    }

    /**
     * Affiche la liste de toutes les locations.
     *
     * Récupère toutes les locations via l'API Toad et les affiche
     * avec leurs statuts traduits en français.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $rentals = $this->rentalService->getAllRentals();

        return view('rentals.index', [
            'rentals'  => $rentals ?? [],     // Tableau vide si l'API échoue
            'statuses' => $this->statuses,    // Tableau des statuts pour les menus déroulants
        ]);
    }

    /**
     * Met à jour le statut d'une location existante.
     *
     * Cette méthode reçoit un formulaire qui contient :
     * - Le nouveau statusId (obligatoire, doit être 1, 2 ou 3)
     * - Toutes les autres données de la location (inchangées, mais requises par l'API PUT)
     *
     * Pourquoi envoyer toutes les données ? Car l'API Toad utilise PUT (remplacement
     * complet), donc si on n'envoie que statusId, les autres champs seraient écrasés.
     *
     * @param Request $request  Données du formulaire
     * @param int|string $id    Identifiant de la location à mettre à jour
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        // Validation : statusId doit obligatoirement être 1, 2 ou 3 (valeurs du enum)
        $request->validate([
            'statusId'    => 'required|integer|in:1,2,3',
            'rentalDate'  => 'required',
            'inventoryId' => 'required|integer',
            'customerId'  => 'required|integer',
        ]);

        // Construction du payload complet pour l'API (PUT remplace toute la ressource)
        $data = [
            'rentalId'    => (int) $id,                                // Cast en entier pour l'API
            'rentalDate'  => $request->input('rentalDate'),
            'inventoryId' => (int) $request->input('inventoryId'),
            'customerId'  => (int) $request->input('customerId'),
            // Opérateur ternaire : si staffId existe → cast en int, sinon → null
            'staffId'     => $request->input('staffId') ? (int) $request->input('staffId') : null,
            // ?: = opérateur Elvis : si returnDate est vide/null → null (pas de chaîne vide)
            'returnDate'  => $request->input('returnDate') ?: null,
            'statusId'    => (int) $request->input('statusId'),
        ];

        // Envoi de la mise à jour à l'API Toad
        $updated = $this->rentalService->updateRental((int) $id, $data);

        if ($updated) {
            return redirect()->route('rentals.index')->with('success', 'Statut mis à jour avec succès !');
        }

        return redirect()->route('rentals.index')->with('error', 'Erreur lors de la mise à jour du statut.');
    }
}

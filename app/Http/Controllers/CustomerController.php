<?php

/*
|--------------------------------------------------------------------------
| CustomerController.php — Contrôleur CRUD de la gestion des clients
|--------------------------------------------------------------------------
|
| Ce contrôleur gère toutes les opérations sur les clients (customers) :
| lister, afficher, créer, modifier, supprimer.
|
| Même architecture que FilmController :
|   - Reçoit la requête HTTP → valide → délègue au Service → retourne une vue
|   - Toutes les opérations passent par l'API Toad (ToadCustomerService)
|
*/

namespace App\Http\Controllers;

// Service dédié aux appels API Toad pour les clients
use App\Services\ToadCustomerService;

// Classe de la requête HTTP (accès aux données du formulaire)
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Classe CustomerController
|--------------------------------------------------------------------------
*/
class CustomerController extends Controller
{
    // Stocke l'instance du service clients (injectée via le constructeur)
    private ToadCustomerService $customerService;

    /**
     * Constructeur : injection du service + protection par middleware.
     *
     * @param ToadCustomerService $customerService  Injecté automatiquement par Laravel
     */
    public function __construct(ToadCustomerService $customerService)
    {
        // Oblige l'utilisateur à être connecté pour accéder à ces pages
        $this->middleware('auth');
        $this->customerService = $customerService;
    }

    /**
     * Affiche la liste de tous les clients.
     *
     * Récupère tous les clients via l'API Toad et les passe à la vue.
     * Utilise "?? []" pour passer un tableau vide si l'API retourne null
     * (évite les erreurs dans la vue lors de la boucle @foreach).
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $customers = $this->customerService->getAllCustomers();

        return view('customers.index', [
            // Si l'API échoue et retourne null, on envoie un tableau vide à la vue
            'customers' => $customers ?? []
        ]);
    }

    /**
     * Affiche le détail d'un client par son ID.
     *
     * @param int|string $id  Identifiant du client (depuis l'URL /customers/{id})
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $customer = $this->customerService->getCustomerById($id);

        // Si le client n'existe pas dans l'API, retourne une page 404
        if (!$customer) {
            abort(404, 'Client non trouvé');
        }

        return view('customers.show', [
            'customer' => $customer
        ]);
    }

    /**
     * Affiche le formulaire de création d'un nouveau client.
     *
     * Aucune donnée à récupérer au préalable : retourne simplement la vue vide.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Traite la soumission du formulaire de création d'un client.
     *
     * Étapes :
     * 1. Validation des champs obligatoires
     * 2. Récupération de la case à cocher "active"
     * 3. Envoi à l'API Toad
     * 4. Redirection ou retour avec erreur
     *
     * @param Request $request  Données soumises par le formulaire
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validation : arrête l'exécution et renvoie les erreurs si une règle échoue
        $validatedData = $request->validate([
            'firstName'  => 'required|string|max:45',
            'lastName'   => 'required|string|max:45',
            'email'      => 'required|email|max:50',  // 'email' vérifie le format email
            'password'   => 'required|string|min:4|max:255',
            'storeId'    => 'required|integer|min:1',
            'addressId'  => 'required|integer|min:1',
        ], [
            // Messages personnalisés pour chaque règle (format: champ.règle)
            'firstName.required'  => 'Le prénom est obligatoire.',
            'lastName.required'   => 'Le nom est obligatoire.',
            'email.required'      => 'L\'adresse email est obligatoire.',
            'email.email'         => 'L\'adresse email n\'est pas valide.',
            'password.required'   => 'Le mot de passe est obligatoire.',
            'password.min'        => 'Le mot de passe doit comporter au moins 4 caractères.',
            'storeId.required'    => 'L\'identifiant du magasin est obligatoire.',
            'addressId.required'  => 'L\'identifiant de l\'adresse est obligatoire.',
        ]);

        // Une case à cocher (checkbox) n'est PAS envoyée dans la requête si elle est décochée.
        // $request->has('active') retourne true si la case est cochée, false sinon.
        $validatedData['active'] = $request->has('active');

        // Envoi des données validées à l'API Toad via le service
        $newCustomer = $this->customerService->createCustomer($validatedData);

        if ($newCustomer) {
            // Redirige vers la page de détail du client créé avec un message flash
            return redirect()
                ->route('customers.show', $newCustomer['customerId'])
                ->with('success', 'Client créé avec succès !');
        }

        // Retour au formulaire avec les données saisies conservées (withInput)
        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Erreur lors de la création du client. Veuillez réessayer.');
    }

    /**
     * Affiche le formulaire de modification d'un client existant.
     *
     * Récupère les données actuelles du client pour préremplir le formulaire.
     *
     * @param int|string $id  Identifiant du client à modifier
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $customer = $this->customerService->getCustomerById($id);

        if (!$customer) {
            abort(404, 'Client non trouvé');
        }

        return view('customers.edit', [
            'customer' => $customer
        ]);
    }

    /**
     * Traite la soumission du formulaire de modification d'un client.
     *
     * Points importants :
     * - Le mot de passe est "nullable" : laisser vide = conserver l'ancien
     * - On récupère le createDate original pour ne pas l'écraser avec null
     *
     * @param Request $request  Nouvelles données soumises
     * @param int|string $id    Identifiant du client à mettre à jour
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'firstName'  => 'required|string|max:45',
            'lastName'   => 'required|string|max:45',
            'email'      => 'required|email|max:50',
            // 'nullable' : le champ peut être absent/vide sans erreur de validation
            'password'   => 'nullable|string|min:4|max:255',
            'storeId'    => 'required|integer|min:1',
            'addressId'  => 'required|integer|min:1',
        ], [
            'firstName.required'  => 'Le prénom est obligatoire.',
            'lastName.required'   => 'Le nom est obligatoire.',
            'email.required'      => 'L\'adresse email est obligatoire.',
            'email.email'         => 'L\'adresse email n\'est pas valide.',
            'storeId.required'    => 'L\'identifiant du magasin est obligatoire.',
            'addressId.required'  => 'L\'identifiant de l\'adresse est obligatoire.',
        ]);

        // Même logique checkbox : cochée = true, absente du formulaire = false
        $validatedData['active'] = $request->has('active');

        // Si le champ mot de passe est vide (non saisi), on ne l'envoie pas à l'API
        // pour éviter d'écraser l'ancien mot de passe avec une valeur vide
        if (empty($validatedData['password'])) {
            unset($validatedData['password']);
        }

        // Récupère les données actuelles du client pour préserver la date de création
        // car l'API exige ce champ lors d'une mise à jour (NOT NULL en BDD)
        $original = $this->customerService->getCustomerById((int) $id);
        if ($original && isset($original['createDate'])) {
            // On injecte le createDate original pour qu'il ne soit pas perdu
            $validatedData['createDate'] = $original['createDate'];
        }

        $updatedCustomer = $this->customerService->updateCustomer($id, $validatedData);

        if ($updatedCustomer) {
            return redirect()
                ->route('customers.show', $updatedCustomer['customerId'])
                ->with('success', 'Client mis à jour avec succès !');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Erreur lors de la mise à jour du client. Veuillez réessayer.');
    }

    /**
     * Supprime un client.
     *
     * Appelle l'API Toad pour supprimer le client,
     * puis redirige vers la liste des clients.
     *
     * @param int|string $id  Identifiant du client à supprimer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $success = $this->customerService->deleteCustomer($id);

        if ($success) {
            return redirect()
                ->route('customers.index')
                ->with('success', 'Client supprimé avec succès !');
        }

        return redirect()
            ->back()
            ->with('error', 'Erreur lors de la suppression du client. Veuillez réessayer.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\ToadCustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private ToadCustomerService $customerService;

    public function __construct(ToadCustomerService $customerService)
    {
        $this->middleware('auth');
        $this->customerService = $customerService;
    }

    public function index()
    {
        $customers = $this->customerService->getAllCustomers();

        return view('customers.index', [
            'customers' => $customers ?? []
        ]);
    }

    public function show($id)
    {
        $customer = $this->customerService->getCustomerById($id);

        if (!$customer) {
            abort(404, 'Client non trouvé');
        }

        return view('customers.show', [
            'customer' => $customer
        ]);
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'firstName'  => 'required|string|max:45',
            'lastName'   => 'required|string|max:45',
            'email'      => 'required|email|max:50',
            'password'   => 'required|string|min:4|max:255',
            'storeId'    => 'required|integer|min:1',
            'addressId'  => 'required|integer|min:1',
        ], [
            'firstName.required'  => 'Le prénom est obligatoire.',
            'lastName.required'   => 'Le nom est obligatoire.',
            'email.required'      => 'L\'adresse email est obligatoire.',
            'email.email'         => 'L\'adresse email n\'est pas valide.',
            'password.required'   => 'Le mot de passe est obligatoire.',
            'password.min'        => 'Le mot de passe doit comporter au moins 4 caractères.',
            'storeId.required'    => 'L\'identifiant du magasin est obligatoire.',
            'addressId.required'  => 'L\'identifiant de l\'adresse est obligatoire.',
        ]);

        $validatedData['active'] = $request->has('active');

        $newCustomer = $this->customerService->createCustomer($validatedData);

        if ($newCustomer) {
            return redirect()
                ->route('customers.show', $newCustomer['customerId'])
                ->with('success', 'Client créé avec succès !');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Erreur lors de la création du client. Veuillez réessayer.');
    }

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

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'firstName'  => 'required|string|max:45',
            'lastName'   => 'required|string|max:45',
            'email'      => 'required|email|max:50',
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

        $validatedData['active'] = $request->has('active');

        // Ne pas envoyer le mot de passe s'il est vide
        if (empty($validatedData['password'])) {
            unset($validatedData['password']);
        }

        // Récupérer le createDate original pour ne pas l'écraser avec NULL
        $original = $this->customerService->getCustomerById((int) $id);
        if ($original && isset($original['createDate'])) {
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
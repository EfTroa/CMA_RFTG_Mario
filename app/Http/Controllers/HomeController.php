<?php

/*
|--------------------------------------------------------------------------
| HomeController.php — Contrôleur du tableau de bord (dashboard)
|--------------------------------------------------------------------------
|
| Ce contrôleur est le plus simple de l'application : il affiche la page
| d'accueil après la connexion. Son seul rôle est de confirmer que
| l'utilisateur est bien authentifié avant de lui montrer le dashboard.
|
*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Classe HomeController
|--------------------------------------------------------------------------
| Hérite de Controller (classe de base Laravel) qui fournit des outils
| communs à tous les contrôleurs (middleware, validation, etc.).
*/
class HomeController extends Controller
{
    /**
     * Constructeur : applique le middleware 'auth' à toutes les méthodes.
     *
     * Le middleware 'auth' vérifie à chaque requête que l'utilisateur est
     * connecté. S'il ne l'est pas, Laravel le redirige automatiquement
     * vers la page de connexion (/login).
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche le tableau de bord principal de l'application.
     *
     * Cette méthode est appelée quand l'utilisateur accède à /home.
     * Elle retourne simplement la vue 'home' (resources/views/home.blade.php).
     * C'est la page que l'utilisateur voit juste après sa connexion.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }
}

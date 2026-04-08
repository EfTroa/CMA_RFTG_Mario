<?php

/*
|--------------------------------------------------------------------------
| FilmController.php — Contrôleur CRUD du catalogue de films
|--------------------------------------------------------------------------
|
| Ce contrôleur gère toutes les opérations sur les films :
| lister, afficher, créer, modifier, supprimer.
|
| Architecture MVC :
|   - Le contrôleur reçoit la requête HTTP (depuis routes/web.php)
|   - Il délègue les appels API à ToadFilmService (couche Service)
|   - Il retourne une vue Blade avec les données récupérées
|
| Toutes les opérations passent par l'API Toad (REST externe),
| il n'y a aucun accès direct à la base de données ici.
|
*/

namespace App\Http\Controllers;

// Service qui gère tous les appels HTTP vers l'API Toad pour les films
use App\Services\ToadFilmService;

// Classe Request de Laravel : représente la requête HTTP entrante (formulaires, URL, etc.)
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Classe FilmController
|--------------------------------------------------------------------------
| Hérite de Controller (classe de base Laravel).
| Implémente le pattern CRUD : index, show, create, store, edit, update, destroy.
*/
class FilmController extends Controller
{
    // Propriété privée pour stocker l'instance du service films
    // Le type "ToadFilmService" garantit qu'on ne peut y mettre que ce service
    private ToadFilmService $filmService;

    /**
     * Constructeur : injection de dépendance + protection par middleware.
     *
     * L'injection de dépendance (Dependency Injection) signifie que Laravel
     * crée automatiquement une instance de ToadFilmService et la passe ici.
     * C'est plus propre que de faire "new ToadFilmService()" à la main.
     *
     * $this->middleware('auth') assure que seuls les utilisateurs connectés
     * peuvent accéder aux méthodes de ce contrôleur.
     *
     * @param ToadFilmService $filmService  Instance injectée par Laravel
     */
    public function __construct(ToadFilmService $filmService)
    {
        $this->middleware('auth');
        $this->filmService = $filmService;
    }

    /**
     * Affiche la liste paginée de tous les films.
     *
     * La pagination est gérée manuellement car les données viennent d'une API
     * externe (pas d'Eloquent). On calcule l'offset à partir du numéro de page,
     * puis on crée manuellement un objet LengthAwarePaginator.
     *
     * @param Request $request  Contient les paramètres GET (ex: ?page=2)
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Nombre de films affichés par page
        $perPage = 20;

        // Récupère le numéro de page depuis l'URL (?page=X), défaut : 1
        $page    = $request->get('page', 1);

        // Calcule le décalage (offset) pour l'API : page 2 → skip les 20 premiers
        $offset  = ($page - 1) * $perPage;

        // Appel API pour récupérer la page courante
        $films = $this->filmService->getAllFilms($perPage, $offset);

        // Appel API séparé pour connaître le nombre total de films (pour la pagination)
        $total = $this->filmService->getCountFilms();

        // LengthAwarePaginator = paginator Laravel qui connaît le nombre total d'éléments
        // Nécessaire pour afficher les liens de pagination (précédent/suivant/numéros)
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $films ?? [],   // Les films de la page courante (tableau vide si null)
            $total,         // Nombre total de films (pour calculer le nombre de pages)
            $perPage,       // Éléments par page
            $page,          // Page courante
            ['path' => $request->url()]  // URL de base pour les liens de pagination
        );

        // Passe le paginator à la vue (qui peut appeler $films->links() pour les boutons)
        return view('films.index', ['films' => $paginator]);
    }

    /**
     * Affiche le détail d'un film par son ID.
     *
     * Si le film n'existe pas (API retourne null), on déclenche une erreur 404.
     * On passe aussi les listes de langues et fonctionnalités spéciales à la vue
     * pour afficher des libellés lisibles plutôt que des IDs bruts.
     *
     * @param int|string $id  Identifiant du film (depuis l'URL /films/{id})
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // Récupère le film via l'API Toad
        $film = $this->filmService->getFilmById($id);

        // Si l'API retourne null (film inexistant ou erreur), renvoie une page 404
        if (!$film) {
            abort(404, 'Film non trouvé');
        }

        // Récupère les tableaux de correspondance ID→Nom pour l'affichage
        $languages = $this->getLanguages();
        $specialFeatures = $this->getSpecialFeatures();

        return view('films.show', [
            'film' => $film,
            'languages' => $languages,
            'specialFeatures' => $specialFeatures
        ]);
    }

    /**
     * Affiche le formulaire de création d'un nouveau film.
     *
     * On passe les listes de langues et fonctionnalités spéciales
     * pour remplir les menus déroulants du formulaire.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $languages = $this->getLanguages();
        $specialFeatures = $this->getSpecialFeatures();

        return view('films.create', [
            'languages' => $languages,
            'specialFeatures' => $specialFeatures
        ]);
    }

    /**
     * Traite la soumission du formulaire de création d'un film.
     *
     * Étapes :
     * 1. Validation des données du formulaire (règles + messages d'erreur)
     * 2. Transformation des données (tableau → chaîne, renommage de champ)
     * 3. Envoi à l'API Toad via le service
     * 4. Redirection vers la page du film créé ou retour avec erreur
     *
     * @param Request $request  Données soumises par le formulaire
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validation Laravel : si une règle échoue, Laravel retourne automatiquement
        // à la page précédente avec les erreurs, sans exécuter la suite du code
        $validatedData = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'releaseYear'      => 'required|integer|min:1900|max:2100',
            'languageId'       => 'required|integer|min:1',
            'length'           => 'nullable|integer|min:1|max:500',
            // La notation ENUM : seules ces valeurs sont acceptées
            'rating'           => 'nullable|string|in:G,PG,PG-13,R,NC-17',
            // 'array' : le champ doit être un tableau (select multiple)
            'specialFeatures'  => 'nullable|array',
            // '.*' : validation de chaque élément du tableau
            'specialFeatures.*' => 'string',
            'rentalDuration'   => 'required|integer|min:1|max:30',
            'rentalRate'       => 'required|numeric|min:0',
            'replacementCost'  => 'required|numeric|min:0',
        ], [
            // Messages d'erreur personnalisés en français (format: champ.règle)
            'title.required'           => 'Le titre est obligatoire.',
            'title.max'                => 'Le titre ne peut pas dépasser 255 caractères.',
            'releaseYear.required'     => 'L\'année de sortie est obligatoire.',
            'releaseYear.min'          => 'L\'année doit être supérieure ou égale à 1900.',
            'releaseYear.max'          => 'L\'année ne peut pas dépasser 2100.',
            'languageId.required'      => 'La langue est obligatoire.',
            'rating.in'                => 'La note doit être G, PG, PG-13, R ou NC-17.',
            'rentalDuration.required'  => 'La durée de location est obligatoire.',
            'rentalRate.required'      => 'Le tarif de location est obligatoire.',
            'replacementCost.required' => 'Le coût de remplacement est obligatoire.',
        ]);

        // Conversion : le formulaire renvoie un tableau ['Trailers', 'Commentaries']
        // L'API Toad attend une chaîne : "Trailers,Commentaries"
        if (isset($validatedData['specialFeatures']) && is_array($validatedData['specialFeatures'])) {
            $validatedData['specialFeatures'] = implode(',', $validatedData['specialFeatures']);
        }

        // L'API Toad utilise "originalLanguageId" et non "languageId"
        // On renomme donc le champ pour correspondre au format attendu par l'API
        if (isset($validatedData['languageId'])) {
            $validatedData['originalLanguageId'] = $validatedData['languageId'];
            unset($validatedData['languageId']); // Supprime l'ancien nom du tableau
        }

        // Envoi à l'API Toad — retourne le film créé (avec son ID) ou null si erreur
        $newFilm = $this->filmService->createFilm($validatedData);

        if ($newFilm) {
            // Redirection vers la page de détail du film nouvellement créé
            // On gère les deux noms possibles de la clé ID selon la version de l'API
            return redirect()
                ->route('films.show', $newFilm['filmId'] ?? $newFilm['id'])
                ->with('success', 'Film créé avec succès !');
        }

        // En cas d'échec API : retour au formulaire avec les données saisies (withInput)
        // et un message d'erreur affiché dans la session flash
        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Erreur lors de la création du film. Veuillez réessayer.');
    }

    /**
     * Affiche le formulaire de modification d'un film existant.
     *
     * Récupère d'abord le film pour préremplir le formulaire avec ses données.
     *
     * @param int|string $id  Identifiant du film à modifier
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $film = $this->filmService->getFilmById($id);

        if (!$film) {
            abort(404, 'Film non trouvé');
        }

        $languages = $this->getLanguages();
        $specialFeatures = $this->getSpecialFeatures();

        return view('films.edit', [
            'film' => $film,
            'languages' => $languages,
            'specialFeatures' => $specialFeatures
        ]);
    }

    /**
     * Traite la soumission du formulaire de modification d'un film.
     *
     * Même logique que store() mais envoie une requête PUT (mise à jour)
     * plutôt que POST (création).
     *
     * @param Request $request  Nouvelles données soumises
     * @param int|string $id    Identifiant du film à mettre à jour
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Mêmes règles de validation que store()
        $validatedData = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'releaseYear'      => 'required|integer|min:1900|max:2100',
            'languageId'       => 'required|integer|min:1',
            'length'           => 'nullable|integer|min:1|max:500',
            'rating'           => 'nullable|string|in:G,PG,PG-13,R,NC-17',
            'specialFeatures'  => 'nullable|array',
            'specialFeatures.*' => 'string',
            'rentalDuration'   => 'required|integer|min:1|max:30',
            'rentalRate'       => 'required|numeric|min:0',
            'replacementCost'  => 'required|numeric|min:0',
        ], [
            'title.required'           => 'Le titre est obligatoire.',
            'title.max'                => 'Le titre ne peut pas dépasser 255 caractères.',
            'releaseYear.required'     => 'L\'année de sortie est obligatoire.',
            'releaseYear.min'          => 'L\'année doit être supérieure ou égale à 1900.',
            'releaseYear.max'          => 'L\'année ne peut pas dépasser 2100.',
            'languageId.required'      => 'La langue est obligatoire.',
            'rating.in'                => 'La note doit être G, PG, PG-13, R ou NC-17.',
            'rentalDuration.required'  => 'La durée de location est obligatoire.',
            'rentalRate.required'      => 'Le tarif de location est obligatoire.',
            'replacementCost.required' => 'Le coût de remplacement est obligatoire.',
        ]);

        // Même transformation des specialFeatures (tableau → chaîne CSV)
        if (isset($validatedData['specialFeatures']) && is_array($validatedData['specialFeatures'])) {
            $validatedData['specialFeatures'] = implode(',', $validatedData['specialFeatures']);
        }

        // Même renommage de champ languageId → originalLanguageId
        if (isset($validatedData['languageId'])) {
            $validatedData['originalLanguageId'] = $validatedData['languageId'];
            unset($validatedData['languageId']);
        }

        // Envoi de la mise à jour à l'API Toad
        $updatedFilm = $this->filmService->updateFilm($id, $validatedData);

        if ($updatedFilm) {
            return redirect()
                ->route('films.show', $updatedFilm['filmId'] ?? $updatedFilm['id'])
                ->with('success', 'Film mis à jour avec succès !');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Erreur lors de la mise à jour du film. Veuillez réessayer.');
    }

    /**
     * Supprime un film.
     *
     * Appelle l'API Toad pour supprimer le film, puis redirige vers la liste.
     * Retourne une erreur si la suppression échoue (ex: contrainte de clé étrangère).
     *
     * @param int|string $id  Identifiant du film à supprimer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // deleteFilm retourne true si succès, false sinon
        $success = $this->filmService->deleteFilm($id);

        if ($success) {
            return redirect()
                ->route('films.index')
                ->with('success', 'Film supprimé avec succès !');
        }

        return redirect()
            ->back()
            ->with('error', 'Erreur lors de la suppression du film. Veuillez réessayer.');
    }

    /**
     * Retourne la liste des langues disponibles (ID → Nom lisible).
     *
     * Ces IDs correspondent aux IDs de la table "language" dans la BDD Sakila/Peach.
     * On les définit ici en dur (hardcodé) pour éviter un appel API supplémentaire.
     * Utilisé pour afficher "Anglais" plutôt que "1" dans les vues.
     *
     * @return array  Tableau associatif [id => 'nom de la langue']
     */
    private function getLanguages(): array
    {
        return [
            1 => 'Anglais',
            2 => 'Français',
            3 => 'Espagnol',
            4 => 'Allemand',
            5 => 'Italien',
            6 => 'Japonais',
            7 => 'Mandarin',
            8 => 'Portugais',
        ];
    }

    /**
     * Retourne la liste des fonctionnalités spéciales disponibles.
     *
     * Correspondance entre la valeur envoyée à l'API (clé anglaise)
     * et le libellé affiché à l'utilisateur (valeur française).
     * Ces valeurs correspondent au type ENUM de la BDD Sakila.
     *
     * @return array  Tableau associatif ['valeur_api' => 'Libellé affiché']
     */
    private function getSpecialFeatures(): array
    {
        return [
            'Trailers'         => 'Bandes-annonces',
            'Commentaries'     => 'Commentaires',
            'Deleted Scenes'   => 'Scènes supprimées',
            'Behind the Scenes' => 'Coulisses',
        ];
    }
}

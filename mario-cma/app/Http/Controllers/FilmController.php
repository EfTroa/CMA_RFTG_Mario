<?php

namespace App\Http\Controllers;

use App\Services\ToadFilmService;
use Illuminate\Http\Request;

class FilmController extends Controller
{
    private ToadFilmService $filmService;

    public function __construct(ToadFilmService $filmService)
    {
        $this->middleware('auth');
        $this->filmService = $filmService;
    }

    public function index()
    {
        $films = $this->filmService->getAllFilms();

        return view('films.index', [
            'films' => $films ?? []
        ]);
    }

    public function show($id)
    {
        $film = $this->filmService->getFilmById($id);

        if (!$film) {
            abort(404, 'Film non trouvé');
        }

        return view('films.show', [
            'film' => $film
        ]);
    }

    /**
     * Affiche le formulaire de création d'un film
     */
    public function create()
    {
        return view('films.create');
    }

    /**
     * Enregistre un nouveau film via l'API Toad
     */
    public function store(Request $request)
    {
        // Validation des données
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'releaseYear' => 'required|integer|min:1900|max:2100',
            'languageId' => 'required|integer|min:1',
            'length' => 'nullable|integer|min:1|max:500',
            'rating' => 'nullable|string|in:G,PG,PG-13,R,NC-17',
            'specialFeatures' => 'nullable|string|max:255',
            'rentalDuration' => 'required|integer|min:1|max:30',
            'rentalRate' => 'required|numeric|min:0',
            'replacementCost' => 'required|numeric|min:0',
        ], [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'releaseYear.required' => 'L\'année de sortie est obligatoire.',
            'releaseYear.min' => 'L\'année doit être supérieure ou égale à 1900.',
            'releaseYear.max' => 'L\'année ne peut pas dépasser 2100.',
            'languageId.required' => 'La langue est obligatoire.',
            'rating.in' => 'La note doit être G, PG, PG-13, R ou NC-17.',
            'rentalDuration.required' => 'La durée de location est obligatoire.',
            'rentalRate.required' => 'Le tarif de location est obligatoire.',
            'replacementCost.required' => 'Le coût de remplacement est obligatoire.',
        ]);

        // Appel à l'API Toad
        $newFilm = $this->filmService->createFilm($validatedData);

        if ($newFilm) {
            return redirect()
                ->route('films.show', $newFilm['filmId'] ?? $newFilm['id'])
                ->with('success', 'Film créé avec succès !');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Erreur lors de la création du film. Veuillez réessayer.');
    }

    /**
     * Affiche le formulaire d'édition d'un film
     */
    public function edit($id)
    {
        $film = $this->filmService->getFilmById($id);

        if (!$film) {
            abort(404, 'Film non trouvé');
        }

        return view('films.edit', [
            'film' => $film
        ]);
    }

    /**
     * Met à jour un film (désactivé pour l'instant)
     */
    public function update(Request $request, $id)
    {
        // Méthode désactivée volontairement
        return redirect()
            ->back()
            ->with('info', 'La mise à jour des films n\'est pas encore activée.');
    }

    /**
     * Supprime un film via l'API Toad
     */
    public function destroy($id)
    {
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
}
<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ToadFilmService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.toad.url', 'http://localhost:8180'), '/');
    }

    public function getAllFilms(): ?array
    {
        $url = $this->baseUrl . '/films';

        try {
            $headers = ['Accept' => 'application/json'];
            
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Appel API Films', ['url' => $url, 'has_token' => !empty($token)]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Films API KO', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur API Films', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function getFilmById(int $id): ?array
    {
        $url = $this->baseUrl . '/films/' . $id;

        try {
            $headers = ['Accept' => 'application/json'];
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur API Film', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function createFilm(array $data): ?array
    {
        $url = $this->baseUrl . '/films';

        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Création film via API', ['url' => $url, 'data' => $data]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($url, $data);

            if ($response->successful()) {
                Log::info('Film créé avec succès', ['response' => $response->json()]);
                return $response->json();
            }

            $errorBody = $response->body();
            $errorJson = $response->json();
            Log::warning('Création film KO', [
                'status' => $response->status(),
                'body' => $errorBody,
                'json' => $errorJson,
                'headers' => $response->headers()
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur création film', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function deleteFilm(int $id): bool
    {
        $url = $this->baseUrl . '/films/' . $id;

        try {
            $headers = ['Accept' => 'application/json'];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Suppression film via API', ['url' => $url, 'filmId' => $id]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->delete($url);

            if ($response->successful() || $response->status() === 204) {
                Log::info('Film supprimé avec succès', ['filmId' => $id]);
                return true;
            }

            Log::warning('Suppression film KO', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Erreur suppression film', ['msg' => $e->getMessage()]);
            return false;
        }
    }

    public function updateFilm(int $id, array $data): ?array
    {
        $url = $this->baseUrl . '/films/' . $id;

        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Mise à jour film via API', ['url' => $url, 'data' => $data]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->put($url, $data);

            if ($response->successful()) {
                Log::info('Film mis à jour avec succès', ['response' => $response->json()]);
                return $response->json();
            }

            $errorBody = $response->body();
            $errorJson = $response->json();
            Log::warning('Mise à jour film KO', [
                'status' => $response->status(),
                'body' => $errorBody,
                'json' => $errorJson,
                'headers' => $response->headers()
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour film', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    private function getUserToken(): ?string
    {
        $userData = session('toad_user');
        Log::info('Récupération token utilisateur', ['userData' => $userData]);

        return $userData['token'] ?? null;
    }
}
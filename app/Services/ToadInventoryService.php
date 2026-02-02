<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ToadInventoryService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.toad.url', 'http://localhost:8180'), '/');
    }

    /**
     * Get all inventory rows (DVDs) for a specific film
     */
    public function getInventoriesByFilmId(int $filmId): ?array
    {
        $url = $this->baseUrl . '/films/' . $filmId . '/inventories';

        try {
            $headers = ['Accept' => 'application/json'];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Appel API Inventories par Film', ['url' => $url, 'filmId' => $filmId]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Inventories API KO', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur API Inventories', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get a single inventory row by ID
     */
    public function getInventoryById(int $inventoryId): ?array
    {
        $url = $this->baseUrl . '/inventories/' . $inventoryId;

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
            Log::error('Erreur API Inventory', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create a new inventory row (add DVD)
     */
    public function createInventory(array $data): ?array
    {
        $url = $this->baseUrl . '/inventories';

        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Création inventory via API', ['url' => $url, 'data' => $data]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($url, $data);

            if ($response->successful()) {
                Log::info('Inventory créé avec succès', ['response' => $response->json()]);
                return $response->json();
            }

            $errorBody = $response->body();
            $errorJson = $response->json();
            Log::warning('Création inventory KO', [
                'status' => $response->status(),
                'body' => $errorBody,
                'json' => $errorJson,
                'headers' => $response->headers()
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur création inventory', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Update an inventory row (e.g., change store_id)
     */
    public function updateInventory(int $inventoryId, array $data): ?array
    {
        $url = $this->baseUrl . '/inventories/' . $inventoryId;

        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Mise à jour inventory via API', ['url' => $url, 'data' => $data]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->put($url, $data);

            if ($response->successful()) {
                Log::info('Inventory mis à jour avec succès', ['response' => $response->json()]);
                return $response->json();
            }

            $errorBody = $response->body();
            $errorJson = $response->json();
            Log::warning('Mise à jour inventory KO', [
                'status' => $response->status(),
                'body' => $errorBody,
                'json' => $errorJson,
                'headers' => $response->headers()
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour inventory', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Delete an inventory row (remove DVD)
     */
    public function deleteInventory(int $inventoryId): bool
    {
        $url = $this->baseUrl . '/inventories/' . $inventoryId;

        try {
            $headers = ['Accept' => 'application/json'];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Suppression inventory via API', ['url' => $url, 'inventoryId' => $inventoryId]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->delete($url);

            if ($response->successful() || $response->status() === 204) {
                Log::info('Inventory supprimé avec succès', ['inventoryId' => $inventoryId]);
                return true;
            }

            Log::warning('Suppression inventory KO', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Erreur suppression inventory', ['msg' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if a DVD (inventory row) is available
     */
    public function checkInventoryAvailability(int $inventoryId): ?array
    {
        $url = $this->baseUrl . '/inventories/' . $inventoryId . '/availability';

        try {
            $headers = ['Accept' => 'application/json'];
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Vérification disponibilité inventory', ['url' => $url, 'inventoryId' => $inventoryId]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur vérification disponibilité', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get all rental records for a specific inventory_id
     * Used to handle ON DELETE RESTRICT constraint
     */
    public function getRentalsByInventoryId(int $inventoryId): ?array
    {
        $url = $this->baseUrl . '/inventories/' . $inventoryId . '/rentals';

        try {
            $headers = ['Accept' => 'application/json'];
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Récupération rentals pour inventory', ['url' => $url, 'inventoryId' => $inventoryId]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur récupération rentals', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Delete a rental record
     * Used when deleting an inventory row that has rental history
     */
    public function deleteRental(int $rentalId): bool
    {
        $url = $this->baseUrl . '/rentals/' . $rentalId;

        try {
            $headers = ['Accept' => 'application/json'];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Suppression rental via API', ['url' => $url, 'rentalId' => $rentalId]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->delete($url);

            if ($response->successful() || $response->status() === 204) {
                Log::info('Rental supprimé avec succès', ['rentalId' => $rentalId]);
                return true;
            }

            Log::warning('Suppression rental KO', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Erreur suppression rental', ['msg' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get all stores for dropdown selection
     */
    public function getAllStores(): ?array
    {
        $url = $this->baseUrl . '/stores';

        try {
            $headers = ['Accept' => 'application/json'];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Appel API Stores', ['url' => $url]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Stores API KO', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur API Stores', ['msg' => $e->getMessage()]);
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
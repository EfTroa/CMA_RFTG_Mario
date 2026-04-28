<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ToadRentalService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.toad.url', 'http://localhost:8180'), '/');
    }

    public function getAllRentals(int $limit = 20, int $offset = 0): ?array
    {
        $url = $this->baseUrl . '/rentals/all';

        try {
            $headers = ['Accept' => 'application/json'];
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->get($url, ['limit' => $limit, 'offset' => $offset]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['content'] ?? $data;
            }

            Log::warning('Rentals API KO', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur API Rentals', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function getRentalsCount(): int
    {
        $url = $this->baseUrl . '/rentals/all/count';

        try {
            $headers = ['Accept' => 'application/json'];
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            $response = Http::withHeaders($headers)->timeout(15)->get($url);

            if ($response->successful()) {
                return (int) $response->body();
            }

            return 0;
        } catch (\Throwable $e) {
            Log::error('Erreur count Rentals', ['msg' => $e->getMessage()]);
            return 0;
        }
    }

    public function getRentalById(int $id): ?array
    {
        $url = $this->baseUrl . '/rentals/' . $id;

        try {
            $headers = ['Accept' => 'application/json'];
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            $response = Http::withHeaders($headers)->timeout(10)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Rental by ID KO', ['id' => $id, 'status' => $response->status()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur rental by ID', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function createRental(array $data): ?array
    {
        $url = $this->baseUrl . '/rentals';

        try {
            $headers = [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ];
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Création rental via API', ['url' => $url, 'data' => $data]);

            $response = Http::withHeaders($headers)->timeout(15)->post($url, $data);

            if ($response->successful()) {
                Log::info('Rental créé avec succès', ['response' => $response->json()]);
                return $response->json();
            }

            Log::warning('Création rental KO', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur création rental', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function updateRental(int $id, array $data): ?array
    {
        $url = $this->baseUrl . '/rentals/' . $id;

        try {
            $headers = [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ];
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            $response = Http::withHeaders($headers)->timeout(15)->put($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Update rental KO', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur update rental', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function deleteRental(int $id): bool
    {
        $url = $this->baseUrl . '/rentals/' . $id;

        try {
            $headers = ['Accept' => 'application/json'];
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Suppression rental via API', ['url' => $url, 'rentalId' => $id]);

            $response = Http::withHeaders($headers)->timeout(15)->delete($url);

            if ($response->successful() || $response->status() === 204) {
                Log::info('Rental supprimé avec succès', ['rentalId' => $id]);
                return true;
            }

            Log::warning('Suppression rental KO', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Erreur suppression rental', ['msg' => $e->getMessage()]);
            return false;
        }
    }

    public function getRentalHistory(int $customerId): ?array
    {
        $url = $this->baseUrl . '/rentals/history/' . $customerId;

        try {
            $headers = ['Accept' => 'application/json'];
            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            $response = Http::withHeaders($headers)->timeout(15)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Rental history API KO', ['status' => $response->status(), 'customerId' => $customerId]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur rental history', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    private function getUserToken(): ?string
    {
        $staticToken = config('services.toad.token');
        if (!empty($staticToken)) {
            return $staticToken;
        }

        $userData = session('toad_user');
        return $userData['token'] ?? null;
    }
}

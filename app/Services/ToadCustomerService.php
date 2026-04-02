<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ToadCustomerService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.toad.url', 'http://localhost:8180'), '/');
    }

    public function getAllCustomers(): ?array
    {
        $url = $this->baseUrl . '/customers';

        try {
            $headers = ['Accept' => 'application/json'];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Appel API Customers', ['url' => $url, 'has_token' => !empty($token)]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Customers API KO', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur API Customers', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function getCustomerById(int $id): ?array
    {
        $url = $this->baseUrl . '/customers/' . $id;

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
            Log::error('Erreur API Customer', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function createCustomer(array $data): ?array
    {
        $url = $this->baseUrl . '/customers';

        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            // createDate est NOT NULL en base, on l'injecte si absent
            if (!isset($data['createDate'])) {
                $data['createDate'] = now()->format('Y-m-d\TH:i:s');
            }

            // Hachage MD5 du mot de passe avant envoi à l'API
            if (!empty($data['password'])) {
                $data['password'] = md5($data['password']);
            }

            Log::info('Création customer via API', ['url' => $url, 'data' => $data]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($url, $data);

            if ($response->successful()) {
                Log::info('Customer créé avec succès', ['response' => $response->json()]);
                return $response->json();
            }

            Log::warning('Création customer KO', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur création customer', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function updateCustomer(int $id, array $data): ?array
    {
        $url = $this->baseUrl . '/customers/' . $id;

        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            // Hachage MD5 du mot de passe si fourni
            if (!empty($data['password'])) {
                $data['password'] = md5($data['password']);
            }

            Log::info('Mise à jour customer via API', ['url' => $url, 'data' => $data]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->put($url, $data);

            if ($response->successful()) {
                Log::info('Customer mis à jour avec succès', ['response' => $response->json()]);
                return $response->json();
            }

            Log::warning('Mise à jour customer KO', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour customer', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    public function deleteCustomer(int $id): bool
    {
        $url = $this->baseUrl . '/customers/' . $id;

        try {
            $headers = ['Accept' => 'application/json'];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            Log::info('Suppression customer via API', ['url' => $url, 'customerId' => $id]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->delete($url);

            if ($response->successful() || $response->status() === 204) {
                Log::info('Customer supprimé avec succès', ['customerId' => $id]);
                return true;
            }

            Log::warning('Suppression customer KO', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Erreur suppression customer', ['msg' => $e->getMessage()]);
            return false;
        }
    }

    private function getUserToken(): ?string
    {
        $userData = session('toad_user');
        return $userData['token'] ?? null;
    }
}
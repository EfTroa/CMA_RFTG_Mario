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
                ->timeout(60)
                ->get($url);

            if (!$response->successful()) {
                Log::warning('Customers API KO', ['status' => $response->status()]);
                return null;
            }

            // The raw body is ~28 MB because each customer embeds all its rentals.
            // Strip "rentals":[...] from the raw string before json_decode so we
            // never allocate the full nested PHP array (which would exceed 128 MB).
            $body = $this->stripRentalsJson($response->body());
            $data = json_decode($body, true);

            if (!is_array($data)) {
                Log::error('Customers API: json_decode failed', ['bodyLen' => strlen($body)]);
                return null;
            }

            Log::info('Customers API OK', ['count' => count($data)]);
            return $data;

        } catch (\Throwable $e) {
            Log::error('Erreur API Customers', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Replaces every "rentals":[...] block in raw JSON with "rentals":[]
     * using bracket counting — no regex, safe for arbitrarily nested objects.
     */
    private function stripRentalsJson(string $body): string
    {
        $result    = '';
        $offset    = 0;
        $marker    = '"rentals":';
        $markerLen = strlen($marker);
        $bodyLen   = strlen($body);

        while (($pos = strpos($body, $marker, $offset)) !== false) {
            $result .= substr($body, $offset, $pos - $offset) . '"rentals":[]';

            // Advance past the marker to find the opening '['
            $i = $pos + $markerLen;
            while ($i < $bodyLen && $body[$i] !== '[') {
                $i++;
            }

            if ($i >= $bodyLen) {
                $offset = $pos + $markerLen;
                continue;
            }

            // Walk forward counting brackets until depth returns to zero
            $depth = 0;
            while ($i < $bodyLen) {
                $c = $body[$i];
                if ($c === '[' || $c === '{') {
                    $depth++;
                } elseif ($c === ']' || $c === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $i++;
                        break;
                    }
                }
                $i++;
            }

            $offset = $i;
        }

        return $result . substr($body, $offset);
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
                ->timeout(15)
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
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            if (!isset($data['createDate'])) {
                $data['createDate'] = now()->format('Y-m-d\TH:i:s');
            }
            $data['lastUpdate'] = now()->format('Y-m-d\TH:i:s');

            Log::info('Création customer via API', ['url' => $url, 'data' => $data]);

            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->post($url, $data);

            if ($response->successful()) {
                Log::info('Customer créé avec succès', ['response' => $response->json()]);
                return $response->json();
            }

            Log::warning('Création customer KO', ['status' => $response->status(), 'body' => $response->body()]);
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
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ];

            $token = $this->getUserToken();
            if ($token) {
                $headers['Authorization'] = "Bearer {$token}";
            }

            $data['lastUpdate'] = now()->format('Y-m-d\TH:i:s');

            Log::info('Mise à jour customer via API', ['url' => $url, 'data' => $data]);

            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->put($url, $data);

            if ($response->successful()) {
                Log::info('Customer mis à jour avec succès', ['response' => $response->json()]);
                return $response->json();
            }

            Log::warning('Mise à jour customer KO', ['status' => $response->status(), 'body' => $response->body()]);
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
                ->timeout(15)
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
        $staticToken = config('services.toad.token');
        if (!empty($staticToken)) {
            return $staticToken;
        }

        $userData = session('toad_user');
        return $userData['token'] ?? null;
    }
}

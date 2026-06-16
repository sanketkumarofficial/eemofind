<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseService
{
    public function __construct(private readonly SettingService $settings) {}

    public function get(string $path): mixed
    {
        return $this->request()->get($this->url($path))->throw()->json();
    }

    public function set(string $path, array $data): mixed
    {
        return $this->request()->put($this->url($path), $data)->throw()->json();
    }

    public function update(string $path, array $data): mixed
    {
        return $this->request()->patch($this->url($path), $data)->throw()->json();
    }

    public function push(string $path, array $data): string
    {
        return (string) $this->request()->post($this->url($path), $data)->throw()->json('name');
    }

    public function delete(string $path): void
    {
        $this->request()->delete($this->url($path))->throw();
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()->asJson()->withToken($this->accessToken())->timeout(15)->retry(2, 300);
    }

    private function url(string $path): string
    {
        $base = rtrim((string) $this->settings->get('firebase_database_url', config('eemo.firebase.database_url')), '/');
        if (! $base) {
            throw new RuntimeException('Firebase database URL is not configured.');
        }

        return $base.'/'.trim($path, '/').'.json';
    }

    private function accessToken(): string
    {
        return Cache::remember('firebase.access_token', 3300, function () {
            $credentials = $this->credentials();
            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.database https://www.googleapis.com/auth/userinfo.email',
                'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR));
            $unsigned = $header.'.'.$claims;
            if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Unable to sign Firebase service token.');
            }
            $assertion = $unsigned.'.'.$this->base64Url($signature);

            return Http::asForm()->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $assertion,
            ])->throw()->json('access_token');
        });
    }

    private function credentials(): array
    {
        $path = $this->settings->get('firebase_credentials_path', config('eemo.firebase.credentials'));
        if (! $path) {
            throw new RuntimeException('Firebase service account is not configured.');
        }
        $absolute = str_starts_with($path, storage_path()) ? $path : storage_path('app/'.$path);
        if (! is_file($absolute)) {
            throw new RuntimeException('Firebase service account file is missing.');
        }

        return json_decode(file_get_contents($absolute), true, flags: JSON_THROW_ON_ERROR);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

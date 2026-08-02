<?php

namespace App\Services\Firebase;

use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Contracts\Firebase\FirebaseTokenService;
use Illuminate\Support\Facades\Cache;

class MockFirebaseClient implements FirebaseRealtimeClient, FirebaseTokenService
{
    public function customToken(string $uid, array $claims): string
    {
        $payload = base64_encode(json_encode(['uid' => $uid, 'claims' => $claims, 'mock' => true], JSON_THROW_ON_ERROR));

        return 'mock.'.rtrim(strtr($payload, '+/', '-_'), '=').'.'.hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    public function set(string $path, array $value): void
    {
        $root = Cache::get('fishtrace.firebase.mock', []);
        data_set($root, str_replace('/', '.', trim($path, '/')), $value);
        Cache::forever('fishtrace.firebase.mock', $root);
    }

    public function remove(string $path): void
    {
        $root = Cache::get('fishtrace.firebase.mock', []);
        data_forget($root, str_replace('/', '.', trim($path, '/')));
        Cache::forever('fishtrace.firebase.mock', $root);
    }

    public function get(string $path): array
    {
        $value = data_get(Cache::get('fishtrace.firebase.mock', []), str_replace('/', '.', trim($path, '/')), []);

        return is_array($value) ? $value : [];
    }

    public function markSynchronized(string $deviceUid, string $messageId, array $metadata): void
    {
        $path = "telemetry/{$deviceUid}/{$messageId}";
        $this->set($path, array_merge($this->get($path), $metadata));
    }
}

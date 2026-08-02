<?php

namespace App\Services\Firebase;

use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Contracts\Firebase\FirebaseTokenService;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Factory;

class FirebaseAdminClient implements FirebaseRealtimeClient, FirebaseTokenService
{
    private Auth $auth;

    private Database $database;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount((string) config('fishtrace.firebase.credentials'))->withDatabaseUri((string) config('fishtrace.firebase.database_url'));
        $this->auth = $factory->createAuth();
        $this->database = $factory->createDatabase();
    }

    public function customToken(string $uid, array $claims): string
    {
        return $this->auth->createCustomToken($uid, $claims)->toString();
    }

    public function set(string $path, array $value): void
    {
        $this->database->getReference($path)->set($value);
    }

    public function remove(string $path): void
    {
        $this->database->getReference($path)->remove();
    }

    public function get(string $path): array
    {
        $value = $this->database->getReference($path)->getValue();

        return is_array($value) ? $value : [];
    }

    public function markSynchronized(string $deviceUid, string $messageId, array $metadata): void
    {
        $this->database->getReference("telemetry/{$deviceUid}/{$messageId}")->update($metadata);
    }
}

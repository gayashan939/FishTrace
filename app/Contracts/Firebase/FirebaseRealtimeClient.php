<?php

namespace App\Contracts\Firebase;

interface FirebaseRealtimeClient
{
    /** @param array<string, mixed> $value */
    public function set(string $path, array $value): void;

    public function remove(string $path): void;

    /** @return array<string, mixed> */
    public function get(string $path): array;

    /** @param array<string, mixed> $metadata */
    public function markSynchronized(string $deviceUid, string $messageId, array $metadata): void;
}

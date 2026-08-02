<?php

namespace App\Contracts\Firebase;

interface FirebaseTokenService
{
    /** @param array<string, scalar|null> $claims */
    public function customToken(string $uid, array $claims): string;
}

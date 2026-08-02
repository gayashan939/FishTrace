<?php

namespace App\Contracts\Firebase;

interface FirebaseDeviceAuth
{
    public function upsert(string $uid, string $email, string $password): void;

    public function disable(string $uid): void;
}

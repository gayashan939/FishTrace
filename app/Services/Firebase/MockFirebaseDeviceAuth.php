<?php

namespace App\Services\Firebase;

use App\Contracts\Firebase\FirebaseDeviceAuth;
use Illuminate\Support\Facades\Cache;

class MockFirebaseDeviceAuth implements FirebaseDeviceAuth
{
    public function upsert(string $uid, string $email, string $password): void
    {
        $users = Cache::get('fishtrace.firebase.mock.users', []);
        $users[$uid] = ['email' => $email, 'disabled' => false];
        Cache::forever('fishtrace.firebase.mock.users', $users);
    }

    public function disable(string $uid): void
    {
        $users = Cache::get('fishtrace.firebase.mock.users', []);
        if (isset($users[$uid])) {
            $users[$uid]['disabled'] = true;
            Cache::forever('fishtrace.firebase.mock.users', $users);
        }
    }
}

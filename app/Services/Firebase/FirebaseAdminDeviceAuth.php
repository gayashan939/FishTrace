<?php

namespace App\Services\Firebase;

use App\Contracts\Firebase\FirebaseDeviceAuth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Factory;

class FirebaseAdminDeviceAuth implements FirebaseDeviceAuth
{
    public function upsert(string $uid, string $email, string $password): void
    {
        $auth = (new Factory)->withServiceAccount((string) config('fishtrace.firebase.credentials'))->createAuth();
        try {
            $auth->getUser($uid);
            $auth->updateUser($uid, ['email' => $email, 'password' => $password, 'disabled' => false]);
        } catch (UserNotFound) {
            $auth->createUser(['uid' => $uid, 'email' => $email, 'password' => $password, 'disabled' => false]);
        }
    }

    public function disable(string $uid): void
    {
        (new Factory)->withServiceAccount((string) config('fishtrace.firebase.credentials'))->createAuth()->disableUser($uid);
    }
}

<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminAccountSecurity
{
    public function get(User $user, string $currentSessionId): array
    {
        return [
            'sessions' => DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->limit(20)->get(['id', 'ip_address', 'user_agent', 'last_activity'])->map(fn (object $session): array => ['id' => $session->id, 'ip_address' => $session->ip_address, 'user_agent' => $session->user_agent, 'last_activity' => $session->last_activity, 'is_current' => hash_equals((string) $session->id, $currentSessionId)]),
            'api_token_count' => $user->tokens()->count(),
        ];
    }
}

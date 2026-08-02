<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    public function __construct(private AuditRedactor $redactor) {}

    public function record(string $action, ?Model $auditable = null, ?array $oldValues = null, ?array $newValues = null, ?User $actor = null, ?string $organizationId = null): AuditLog
    {
        $request = app(Request::class);
        $actor ??= $request->user();
        $organizationId ??= $actor?->primaryOrganization()?->id;

        return AuditLog::create([
            'user_id' => $actor?->id,
            'organization_id' => $organizationId,
            'action' => Str::upper($action),
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $this->redactor->redact($oldValues),
            'new_values' => $this->redactor->redact($newValues),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
            'request_id' => $this->requestId($request),
        ]);
    }

    private function requestId(Request $request): ?string
    {
        $requestId = $request->attributes->get('request_id');

        return is_string($requestId) && Str::isUuid($requestId) ? $requestId : null;
    }
}

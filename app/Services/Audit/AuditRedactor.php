<?php

namespace App\Services\Audit;

use Illuminate\Http\UploadedFile;

class AuditRedactor
{
    private const SENSITIVE_FRAGMENTS = ['password', 'token', 'otp', 'secret', 'credential', 'authorization', 'cookie', 'private_key'];

    private const SENSITIVE_KEYS = ['firebase_email', 'firebase_sync_error', 'error_message', 'path', 'disk', 'raw_payload'];

    public function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return $this->walk($values);
    }

    private function walk(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $normalizedKey = mb_strtolower((string) $key);
            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true) || collect(self::SENSITIVE_FRAGMENTS)->contains(fn (string $fragment): bool => str_contains($normalizedKey, $fragment))) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $result[$key] = $this->walk($value);
            } elseif ($value instanceof UploadedFile) {
                $result[$key] = ['name' => basename($value->getClientOriginalName()), 'size_bytes' => $value->getSize(), 'mime_type' => $value->getMimeType()];
            } elseif (is_scalar($value) || $value === null) {
                $result[$key] = $value;
            } elseif ($value instanceof \BackedEnum) {
                $result[$key] = $value->value;
            } elseif ($value instanceof \Stringable) {
                $result[$key] = (string) $value;
            } else {
                $result[$key] = '['.class_basename($value).']';
            }
        }

        return $result;
    }
}

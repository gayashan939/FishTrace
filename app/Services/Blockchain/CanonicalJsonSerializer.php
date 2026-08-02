<?php

namespace App\Services\Blockchain;

use DateTimeInterface;

class CanonicalJsonSerializer
{
    public function serialize(array $payload): string
    {
        return json_encode($this->normalize($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');
        }
        if (is_float($value)) {
            $normalized = round($value, 12);

            return floor($normalized) === $normalized ? (int) $normalized : $normalized;
        }
        if (is_array($value)) {
            if (array_is_list($value)) {
                return array_map(fn ($item) => $this->normalize($item), $value);
            }
            ksort($value, SORT_STRING);
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalize($item);
            }
        }

        return $value;
    }
}

<?php

namespace App\Contracts\AI;

interface AIPredictionClient
{
    /** @param array<string, mixed> $features @return array{riskLevel:string,confidence:float|int|string,probabilities:array<string,float|int|string>,recommendation:string,modelVersion:string,provider:string} */
    public function predict(array $features): array;
}

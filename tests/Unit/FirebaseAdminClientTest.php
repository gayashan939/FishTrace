<?php

namespace Tests\Unit;

use App\Services\Firebase\FirebaseAdminClient;
use PHPUnit\Framework\TestCase;

class FirebaseAdminClientTest extends TestCase
{
    public function test_construction_does_not_read_credentials_eagerly(): void
    {
        $client = new FirebaseAdminClient;

        $this->assertInstanceOf(FirebaseAdminClient::class, $client);
    }
}

<?php

declare(strict_types=1);

namespace SolveBeam\Twinfield\Connect\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use SolveBeam\Twinfield\Connect\ConnectClient;

final class ConnectClientTest extends TestCase
{
    public function testHoldsTheClientIdAndSecret(): void
    {
        $client = new ConnectClient('connect-client', 'connect-secret');

        self::assertSame('connect-client', $client->clientId);
        self::assertSame('connect-secret', $client->clientSecret);
    }

    #[TestWith(['', 'connect-secret'])]
    #[TestWith(['connect-client', ''])]
    public function testRefusesAnEmptyClientIdOrSecret(string $clientId, string $clientSecret): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ConnectClient($clientId, $clientSecret);
    }
}

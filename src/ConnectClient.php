<?php

declare(strict_types=1);

namespace SolveBeam\Twinfield\Connect;

use InvalidArgumentException;
use SensitiveParameter;

/**
 * Your application as it is registered with Twinfield Connect. The client ID and secret are those
 * of Twinfield Connect, not of Twinfield.
 *
 * Register once with `Registration::register()` and store both values, e.g. next to your other
 * credentials. The secret cannot be read again.
 */
final readonly class ConnectClient
{
    public function __construct(
        public string $clientId,
        #[SensitiveParameter]
        public string $clientSecret,
    ) {
        if ($clientId === '' || $clientSecret === '') {
            throw new InvalidArgumentException('Client ID and client secret must not be empty.');
        }
    }
}

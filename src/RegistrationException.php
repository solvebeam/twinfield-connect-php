<?php

declare(strict_types=1);

namespace SolveBeam\Twinfield\Connect;

use RuntimeException;

/**
 * Twinfield Connect refused the registration, e.g. because a redirect URI is not allowed.
 */
final class RegistrationException extends RuntimeException
{
    public function __construct(
        /** Error code of Twinfield Connect, e.g. `invalid_redirect_uri`. */
        public readonly string $error,
        string $message,
        int $statusCode,
    ) {
        parent::__construct($message, $statusCode);
    }
}

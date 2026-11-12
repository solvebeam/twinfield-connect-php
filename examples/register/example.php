<?php

declare(strict_types=1);

use SolveBeam\Twinfield\Connect\Registration;
use SolveBeam\Twinfield\Connect\RegistrationException;

require __DIR__ . '/../../vendor/autoload.php';

$name = $argv[1] ?? 'My application';
$redirectUri = $argv[2] ?? 'http://localhost:8765/callback';

try {
    $client = (new Registration())->register($name, [$redirectUri]);
} catch (RegistrationException $exception) {
    fwrite(STDERR, "Registration refused ({$exception->error}): {$exception->getMessage()}" . PHP_EOL);
    exit(1);
}

echo 'Client ID:     ', $client->clientId, PHP_EOL;
echo 'Client secret: ', $client->clientSecret, PHP_EOL, PHP_EOL;
echo 'Store both. The secret is not shown again.', PHP_EOL;

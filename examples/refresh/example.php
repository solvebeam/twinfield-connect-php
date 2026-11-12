<?php

declare(strict_types=1);

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use SolveBeam\Twinfield\Connect\Examples\Example;
use SolveBeam\Twinfield\Connect\Provider\TwinfieldConnect;

require __DIR__ . '/../../vendor/autoload.php';

$provider = TwinfieldConnect::forClient(Example::client(), Example::REDIRECT_URI);

try {
    $token = $provider->getAccessToken('refresh_token', ['refresh_token' => Example::refreshToken()]);
    $validation = $provider->getAccessTokenValidation($token);
} catch (IdentityProviderException $exception) {
    fwrite(STDERR, 'Refresh failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

// Twinfield Connect only returns a refresh token when the old one is replaced.
if ($token->getRefreshToken() !== null) {
    Example::storeRefreshToken($token->getRefreshToken());
}

echo 'Organisation:  ', $validation->organisationCode, PHP_EOL;
echo 'User:          ', $validation->organisationUserCode, PHP_EOL;
echo 'Cluster:       ', $validation->clusterUrl, PHP_EOL;
echo 'Access token:  valid until ', date('Y-m-d H:i:s', (int) $token->getExpires()), PHP_EOL;

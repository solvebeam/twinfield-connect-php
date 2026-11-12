<?php

declare(strict_types=1);

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use SolveBeam\Twinfield\Connect\Examples\Example;
use SolveBeam\Twinfield\Connect\Provider\TwinfieldConnect;
use SolveBeam\Twinfield\OAuth2\Scope;

require __DIR__ . '/../../vendor/autoload.php';

// From the command line, start PHP's built-in web server on the host of the redirect URI.
if (PHP_SAPI === 'cli') {
    // Registers the examples with Twinfield Connect on the first run.
    Example::client();

    echo 'Open http://localhost:8765/ to log in. Stop with Ctrl+C.', PHP_EOL;
    passthru(sprintf('%s -S localhost:8765 %s', escapeshellarg(PHP_BINARY), escapeshellarg(__FILE__)));
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
session_start();

$fail = static function (string $message): never {
    http_response_code(400);
    echo $message, PHP_EOL;
    exit;
};

$query = static fn (string $name): string => is_string($_GET[$name] ?? null) ? $_GET[$name] : '';

$provider = TwinfieldConnect::forClient(Example::client(), Example::REDIRECT_URI);

$session = static fn (string $name): string => is_string($_SESSION[$name] ?? null) ? $_SESSION[$name] : '';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

$path = parse_url(is_string($requestUri) ? $requestUri : '/', PHP_URL_PATH);

// 1. Send the user to Twinfield, via Twinfield Connect.
if ($path === '/') {
    $url = $provider->getAuthorizationUrl([
        'scope' => Scope::values(...Scope::identity()),
        'nonce' => bin2hex(random_bytes(16)),
    ]);

    // The provider generated a random state and PKCE code; keep them for the callback.
    $_SESSION['twinfield_state'] = $provider->getState();
    $_SESSION['twinfield_pkce'] = $provider->getPkceCode();

    header('Location: ' . $url);
    exit;
}

if ($path !== parse_url(Example::REDIRECT_URI, PHP_URL_PATH)) {
    http_response_code(404);
    exit;
}

// 2. The user comes back with an error, or with a code and the state.
if ($query('error') !== '') {
    $fail('Login failed (' . $query('error') . '): ' . ($query('error_description') ?: 'no description'));
}

$state = $session('twinfield_state');
$pkceCode = $session('twinfield_pkce');
unset($_SESSION['twinfield_state'], $_SESSION['twinfield_pkce']);

if ($state === '' || !hash_equals($state, $query('state'))) {
    $fail('State mismatch: this redirect does not belong to this login. Start again at /.');
}

// 3. Exchange the code for tokens, with the PKCE code.
try {
    $provider->setPkceCode($pkceCode);

    $token = $provider->getAccessToken('authorization_code', ['code' => $query('code')]);
} catch (IdentityProviderException $exception) {
    $fail('Token request failed: ' . $exception->getMessage());
}

$refreshToken = $token->getRefreshToken() ?? $fail('No refresh token. Request the "offline_access" scope.');

// 4. Store the refresh token, for example with the user or organisation in your database.
Example::storeRefreshToken($refreshToken);

// Who the token belongs to and where the API is, asked from Twinfield directly.
$validation = $provider->getAccessTokenValidation($token);

echo 'Logged in to Twinfield.', PHP_EOL, PHP_EOL;
echo 'Organisation:  ', $validation->organisationCode, PHP_EOL;
echo 'User:          ', $validation->organisationUserCode, PHP_EOL;
echo 'Cluster:       ', $validation->clusterUrl, PHP_EOL;
echo 'Access token:  valid until ', date('Y-m-d H:i:s', (int) $token->getExpires()), PHP_EOL;
echo 'Refresh token: ', $refreshToken, PHP_EOL, PHP_EOL;
echo 'The refresh token is saved for the other examples, try:', PHP_EOL;
echo 'php examples/refresh/example.php', PHP_EOL;

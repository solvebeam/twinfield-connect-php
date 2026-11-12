# Twinfield Connect for PHP

Log in to Twinfield without a Twinfield client ID of your own.

To connect an application to Twinfield you normally need a client ID from Twinfield, and to get one you have to pass Twinfield's certification. [Twinfield Connect](https://github.com/solvebeam/twinfield-connect) is a service of SolveBeam that lets your application log in through SolveBeam's certified Twinfield client instead.

This package is a provider for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client). It works like the [Twinfield provider](https://github.com/solvebeam/twinfield-oauth2-php), and gives you the same Twinfield access tokens.

## When to use it

- You want to try the Twinfield API, or build something for your own organisation, without requesting a client ID.
- You build an application that others install on their own site, where you cannot know the redirect URL in advance.

With your own Twinfield client ID you do not need this package: use [solvebeam/oauth2-twinfield](https://github.com/solvebeam/twinfield-oauth2-php).

Good to know before you start:

- Users see a page of Twinfield Connect before they go to Twinfield. It names your application and the host they return to.
- Twinfield asks the user for permission in the name of SolveBeam, not of your application.
- Logging in and refreshing tokens go through Twinfield Connect. API calls go directly to Twinfield.
- The refresh token only works with Twinfield Connect. If you switch to your own client ID later, your users have to log in again.

## Installation

```sh
composer require solvebeam/twinfield-connect
```

Requires PHP 8.3 or higher.

## Usage

### 1. Register your application

Do this once per application or site, not per user.

```php
<?php

use SolveBeam\Twinfield\Connect\Registration;

$client = (new Registration())->register(
    'My application',
    ['https://example.com/twinfield/callback'],
);

// Store both. The secret is not shown again.
echo $client->clientId, PHP_EOL;
echo $client->clientSecret, PHP_EOL;
```

A redirect URI is an `https` URL, or `http` on `localhost` for development. `register()` throws a `RegistrationException` when Twinfield Connect refuses the registration, and a Guzzle exception when Twinfield Connect cannot be reached.

### 2. Log in

```php
<?php

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use SolveBeam\Twinfield\Connect\ConnectClient;
use SolveBeam\Twinfield\Connect\Provider\TwinfieldConnect;
use SolveBeam\Twinfield\OAuth2\Scope;

session_start();

$provider = TwinfieldConnect::forClient(
    new ConnectClient('stored-client-id', 'stored-client-secret'),
    'https://example.com/twinfield/callback',
);

if (isset($_GET['error'])) {
    // The user refused, or the login failed. Do not send the user to Twinfield again.
    exit('Login failed.');
}

if (!isset($_GET['code'])) {
    // Send the user to Twinfield, via Twinfield Connect.
    $url = $provider->getAuthorizationUrl([
        'scope' => Scope::values(...Scope::identity()),
        'nonce' => bin2hex(random_bytes(16)),
    ]);

    // Keep the state and the PKCE code for the callback.
    $_SESSION['twinfield_state'] = $provider->getState();
    $_SESSION['twinfield_pkce'] = $provider->getPkceCode();

    header('Location: ' . $url);
    exit;
}

$state = $_SESSION['twinfield_state'] ?? '';
$pkceCode = $_SESSION['twinfield_pkce'] ?? '';
unset($_SESSION['twinfield_state'], $_SESSION['twinfield_pkce']);

if ($state === '' || !hash_equals($state, (string) ($_GET['state'] ?? ''))) {
    exit('Invalid state.');
}

try {
    $provider->setPkceCode($pkceCode);

    $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
} catch (IdentityProviderException $exception) {
    exit('Token request failed: ' . $exception->getMessage());
}

// Who the token belongs to and where the API is, asked from Twinfield directly.
$validation = $provider->getAccessTokenValidation($token);

echo $validation->organisationCode, ' ', $validation->clusterUrl, PHP_EOL;

// Store the refresh token to get new access tokens later.
$refreshToken = $token->getRefreshToken();
```

Twinfield Connect requires PKCE. The provider creates the code when you call `getAuthorizationUrl()`; you only have to keep it with the state and give it back with `setPkceCode()`.

### 3. Refresh the access token

```php
$token = $provider->getAccessToken('refresh_token', ['refresh_token' => $refreshToken]);

// Keep the refresh token you have when the response has no new one.
$refreshToken = $token->getRefreshToken() ?? $refreshToken;
```

### With the Twinfield PHP SDK

The [Twinfield PHP SDK](https://php.twinfield.solvebeam.com) accepts this provider wherever it accepts the Twinfield provider:

```php
use SolveBeam\Twinfield\Authentication\RefreshTokenAccessTokenProvider;

$tokens = new RefreshTokenAccessTokenProvider($provider, $refreshToken);
```

## Examples

See [examples/](examples/) for small examples you can run. They need no setup:

```sh
php examples/login/example.php
```

## Your own Twinfield Connect

To use a Twinfield Connect you run yourself, or one on your machine during development, pass its URL:

```php
$client = (new Registration(connectUrl: 'http://localhost:8787'))->register(…);

$provider = TwinfieldConnect::forClient($client, $redirectUri, ['connectUrl' => 'http://localhost:8787']);
```

## Testing

```sh
composer test
composer phpcs
composer phpstan
```

## License

The MIT License (MIT). See [LICENSE](LICENSE).

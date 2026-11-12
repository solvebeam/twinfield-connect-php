# Login

Lets a user log in to Twinfield through Twinfield Connect and gives you the **refresh token** to store.

## Run

```sh
php examples/login/example.php
```

This starts PHP's built-in web server. Open http://localhost:8765 and log in. In your own application, the two steps are two routes in your framework.

```text
Logged in to Twinfield.

Organisation:  EXAMPLE
User:          JDOE
Cluster:       https://api.accounting.twinfield.com
Access token:  valid until 2026-11-12 15:04:05
Refresh token: k1.3f1c…
```

## Flow

1. Send the user to `$provider->getAuthorizationUrl()` with the scopes you need. Keep the `state` and the PKCE code in the session.
2. The user sees a page of Twinfield Connect, logs in at Twinfield and comes back on your redirect URI with `?code=…&state=…`, or with `?error=…` when the login failed.
3. Check the `state`, give the PKCE code back to the provider and exchange the code for tokens.
4. Store the refresh token. See [Refresh](../refresh/) for how to use it.

## Code

```php
$provider = TwinfieldConnect::forClient($client, 'https://example.com/twinfield/callback');

// 1. Login
$url = $provider->getAuthorizationUrl(['scope' => Scope::values(...Scope::identity())]);

$_SESSION['twinfield_state'] = $provider->getState();
$_SESSION['twinfield_pkce'] = $provider->getPkceCode();

header('Location: ' . $url);

// 2–3. Callback
if (!hash_equals($_SESSION['twinfield_state'] ?? '', $_GET['state'] ?? '')) {
    throw new RuntimeException('State mismatch.');
}

$provider->setPkceCode($_SESSION['twinfield_pkce']);

$token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);

// 4. Store this
$refreshToken = $token->getRefreshToken();
```

`$client` is the `ConnectClient` you got when you registered: see [Register](../register/). The example registers itself on the first run.

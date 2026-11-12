# Refresh

Gets a new access token with the refresh token, and asks Twinfield who it belongs to. An access token is valid for one hour.

## Run

Log in first with the [Login](../login/) example, then:

```sh
php examples/refresh/example.php
```

```text
Organisation:  EXAMPLE
User:          JDOE
Cluster:       https://api.accounting.twinfield.com
Access token:  valid until 2026-11-12 16:04:05
```

## Code

```php
$token = $provider->getAccessToken('refresh_token', ['refresh_token' => $refreshToken]);

// Keep the refresh token you have when the response has no new one.
$refreshToken = $token->getRefreshToken() ?? $refreshToken;

// The organisation, the user and the cluster URL for API calls.
$validation = $provider->getAccessTokenValidation($token);
```

The refresh goes through Twinfield Connect. The access token validation, like every API call, goes directly to Twinfield.

# Register

Registers an application with Twinfield Connect and gives you its client ID and secret. Do this once per application or site, not per user.

## Run

```sh
php examples/register/example.php 'My application' https://example.com/twinfield/callback
```

```text
Client ID:     4b1d…
Client secret: 9f2a…

Store both. The secret is not shown again.
```

Every run registers a new application. The other examples do not need this one: they register themselves on the first run.

## Code

```php
$client = (new Registration())->register('My application', ['https://example.com/twinfield/callback']);

echo $client->clientId, PHP_EOL;
echo $client->clientSecret, PHP_EOL;
```

- The name is 1 to 100 characters. Users see it on the page of Twinfield Connect before they go to Twinfield.
- A redirect URI is an `https` URL, or `http` on `localhost` for development. You can register up to 10.
- `register()` throws a `RegistrationException` when Twinfield Connect refuses the registration. Its `error` is a code such as `invalid_redirect_uri`.

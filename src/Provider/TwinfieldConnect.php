<?php

declare(strict_types=1);

namespace SolveBeam\Twinfield\Connect\Provider;

use SolveBeam\Twinfield\Connect\ConnectClient;
use SolveBeam\Twinfield\OAuth2\Provider\AbstractTwinfieldProvider;

/**
 * Logs in at Twinfield through Twinfield Connect, with the certified Twinfield client of SolveBeam
 * instead of a Twinfield client ID of your own.
 *
 *   $provider = TwinfieldConnect::forClient($client, 'https://example.com/callback');
 *
 * The access tokens are Twinfield access tokens: API calls and the access token validation go
 * directly to Twinfield. The refresh token is one of Twinfield Connect and only works here.
 *
 * Twinfield Connect requires PKCE: keep `getPkceCode()` with the state after
 * `getAuthorizationUrl()`, and pass it to `setPkceCode()` before you exchange the code.
 *
 * @see https://github.com/solvebeam/twinfield-connect
 */
final class TwinfieldConnect extends AbstractTwinfieldProvider
{
    public const string CONNECT_URL = 'https://connect.twinfield.solvebeam.com';

    /**
     * Base URL of Twinfield Connect. Only set the `connectUrl` option for a Twinfield Connect you
     * run yourself or for development.
     */
    protected string $connectUrl = self::CONNECT_URL;

    /**
     * @param array<string, mixed> $options Extra provider options, e.g. `connectUrl`.
     * @param array<string, mixed> $collaborators League collaborators, e.g. `httpClient`.
     */
    public static function forClient(
        ConnectClient $client,
        string $redirectUri,
        array $options = [],
        array $collaborators = [],
    ): self {
        return new self(
            ['clientId' => $client->clientId, 'clientSecret' => $client->clientSecret, 'redirectUri' => $redirectUri]
                + $options,
            $collaborators,
        );
    }

    public function getBaseAuthorizationUrl(): string
    {
        return rtrim($this->connectUrl, '/') . '/authorize';
    }

    /**
     * @param array<string, mixed> $params
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return rtrim($this->connectUrl, '/') . '/token';
    }

    protected function getPkceMethod(): string
    {
        return self::PKCE_METHOD_S256;
    }
}

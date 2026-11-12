<?php

declare(strict_types=1);

namespace SolveBeam\Twinfield\Connect;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use JsonException;
use SolveBeam\Twinfield\Connect\Provider\TwinfieldConnect;
use SolveBeam\Twinfield\OAuth2\UnexpectedResponseException;

/**
 * Registers your application with Twinfield Connect. Do this once per application or site, not
 * per user.
 *
 *   $client = (new Registration())->register('My application', ['https://example.com/callback']);
 *
 *   // Store $client->clientId and $client->clientSecret.
 */
final readonly class Registration
{
    private ClientInterface $httpClient;

    public function __construct(
        ?ClientInterface $httpClient = null,
        private string $connectUrl = TwinfieldConnect::CONNECT_URL,
    ) {
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * @param string $name Name of your application; users see it before they go to Twinfield.
     * @param list<string> $redirectUris Where users may come back after logging in: https URLs, or
     *                                   http on localhost for development.
     *
     * @throws RegistrationException When Twinfield Connect refuses the registration.
     * @throws UnexpectedResponseException When the response has no client ID and secret.
     * @throws GuzzleException When Twinfield Connect cannot be reached.
     */
    public function register(string $name, array $redirectUris): ConnectClient
    {
        if ($name === '' || $redirectUris === []) {
            throw new InvalidArgumentException('A name and at least one redirect URI are required.');
        }

        $response = $this->httpClient->request('POST', rtrim($this->connectUrl, '/') . '/register', [
            RequestOptions::JSON => ['client_name' => $name, 'redirect_uris' => $redirectUris],
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        $statusCode = $response->getStatusCode();

        try {
            $data = json_decode((string) $response->getBody(), true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedResponseException(
                sprintf('Twinfield Connect answered HTTP %d without JSON.', $statusCode),
                0,
                $exception,
            );
        }

        if (!is_array($data)) {
            throw new UnexpectedResponseException(
                sprintf('Twinfield Connect answered HTTP %d without a JSON object.', $statusCode),
            );
        }

        if ($statusCode >= 400) {
            $error = $data['error'] ?? null;
            $description = $data['error_description'] ?? null;

            if (!is_string($error)) {
                throw new UnexpectedResponseException(
                    sprintf('Twinfield Connect answered HTTP %d without an error code.', $statusCode),
                );
            }

            throw new RegistrationException($error, is_string($description) ? $description : $error, $statusCode);
        }

        $clientId = $data['client_id'] ?? null;
        $clientSecret = $data['client_secret'] ?? null;

        if (!is_string($clientId) || $clientId === '' || !is_string($clientSecret) || $clientSecret === '') {
            throw new UnexpectedResponseException('Twinfield Connect did not return a client ID and secret.');
        }

        return new ConnectClient($clientId, $clientSecret);
    }
}

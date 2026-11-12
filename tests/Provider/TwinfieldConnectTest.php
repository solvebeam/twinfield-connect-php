<?php

declare(strict_types=1);

namespace SolveBeam\Twinfield\Connect\Tests\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use SolveBeam\Twinfield\Connect\ConnectClient;
use SolveBeam\Twinfield\Connect\Provider\TwinfieldConnect;

final class TwinfieldConnectTest extends TestCase
{
    private MockHandler $mock;

    public function testSendsTheUserToTwinfieldConnectWithPkce(): void
    {
        $provider = $this->provider();
        $url = $provider->getAuthorizationUrl(['scope' => ['openid', 'offline_access']]);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $hash = hash('sha256', (string) $provider->getPkceCode(), true);
        $challenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        self::assertStringStartsWith('https://connect.twinfield.solvebeam.com/authorize?', $url);
        self::assertSame('connect-client', $query['client_id']);
        self::assertSame('https://example.com/callback', $query['redirect_uri']);
        self::assertSame('openid offline_access', $query['scope']);
        self::assertSame('S256', $query['code_challenge_method']);
        self::assertSame($challenge, $query['code_challenge']);
        self::assertArrayNotHasKey('approval_prompt', $query);
    }

    public function testExchangesTheCodeAtTwinfieldConnectWithTheVerifier(): void
    {
        $provider = $this->provider(self::json(['access_token' => 'access-1', 'refresh_token' => 'sealed-1']));
        $provider->setPkceCode('verifier-from-the-session');

        $token = $provider->getAccessToken('authorization_code', ['code' => 'code-1']);
        $request = $this->lastRequest();

        parse_str((string) $request->getBody(), $body);

        self::assertSame('access-1', $token->getToken());
        self::assertSame('sealed-1', $token->getRefreshToken());
        self::assertSame('https://connect.twinfield.solvebeam.com/token', (string) $request->getUri());
        self::assertSame('verifier-from-the-session', $body['code_verifier']);
        self::assertSame('connect-secret', $body['client_secret']);
        self::assertSame('https://example.com/callback', $body['redirect_uri']);
    }

    public function testRefreshesAtTwinfieldConnect(): void
    {
        $provider = $this->provider(self::json(['access_token' => 'access-2']));

        $token = $provider->getAccessToken('refresh_token', ['refresh_token' => 'sealed-1']);

        parse_str((string) $this->lastRequest()->getBody(), $body);

        self::assertSame('access-2', $token->getToken());
        self::assertNull($token->getRefreshToken());
        self::assertSame('https://connect.twinfield.solvebeam.com/token', (string) $this->lastRequest()->getUri());
        self::assertSame('sealed-1', $body['refresh_token']);
    }

    public function testThrowsWithTheErrorOfTwinfieldConnect(): void
    {
        $provider = $this->provider(self::json(
            ['error' => 'invalid_grant', 'error_description' => 'The refresh token is invalid.'],
            400,
        ));

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('The refresh token is invalid.');

        $provider->getAccessToken('refresh_token', ['refresh_token' => 'wrong']);
    }

    public function testValidatesAccessTokensAtTwinfield(): void
    {
        $provider = $this->provider(self::json([
            'exp' => 1794165191,
            'scope' => ['openid', 'twf.organisationUser', 'twf.organisation'],
            'twf.organisationUserCode' => 'JDOE',
            'twf.organisationId' => '0b6a5f6e-1111-2222-3333-444455556666',
            'twf.organisationCode' => 'EXAMPLE',
            'twf.clusterUrl' => 'https://api.accounting.twinfield.com',
        ]));

        $validation = $provider->getAccessTokenValidation(new AccessToken(['access_token' => 'access-1']));

        self::assertSame('EXAMPLE', $validation->organisationCode);
        self::assertSame('login.twinfield.com', $this->lastRequest()->getUri()->getHost());
    }

    public function testUsesAnotherTwinfieldConnect(): void
    {
        $provider = $this->provider(options: ['connectUrl' => 'http://localhost:8787/']);

        self::assertSame('http://localhost:8787/authorize', $provider->getBaseAuthorizationUrl());
        self::assertSame('http://localhost:8787/token', $provider->getBaseAccessTokenUrl([]));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function provider(?Response $response = null, array $options = []): TwinfieldConnect
    {
        $this->mock = new MockHandler($response === null ? [] : [$response]);

        return TwinfieldConnect::forClient(
            new ConnectClient('connect-client', 'connect-secret'),
            'https://example.com/callback',
            $options,
            ['httpClient' => new Client(['handler' => HandlerStack::create($this->mock)])],
        );
    }

    private function lastRequest(): RequestInterface
    {
        return $this->mock->getLastRequest() ?? self::fail('No request was sent.');
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function json(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], (string) json_encode($data));
    }
}

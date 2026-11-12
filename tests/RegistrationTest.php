<?php

declare(strict_types=1);

namespace SolveBeam\Twinfield\Connect\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SolveBeam\Twinfield\Connect\Registration;
use SolveBeam\Twinfield\Connect\RegistrationException;
use SolveBeam\Twinfield\OAuth2\UnexpectedResponseException;

final class RegistrationTest extends TestCase
{
    private MockHandler $mock;

    public function testRegistersAnApplication(): void
    {
        $registration = $this->registration(new Response(201, [], (string) json_encode([
            'client_id' => 'connect-client',
            'client_secret' => 'connect-secret',
            'client_name' => 'My application',
            'redirect_uris' => ['https://example.com/callback'],
        ])));

        $client = $registration->register('My application', ['https://example.com/callback']);
        $request = $this->mock->getLastRequest() ?? self::fail('No request was sent.');

        self::assertSame('connect-client', $client->clientId);
        self::assertSame('connect-secret', $client->clientSecret);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://connect.twinfield.solvebeam.com/register', (string) $request->getUri());
        self::assertSame(
            ['client_name' => 'My application', 'redirect_uris' => ['https://example.com/callback']],
            json_decode((string) $request->getBody(), true),
        );
    }

    public function testThrowsWhenTheRegistrationIsRefused(): void
    {
        $registration = $this->registration(new Response(400, [], (string) json_encode([
            'error' => 'invalid_redirect_uri',
            'error_description' => 'A redirect URI must be an https URL.',
        ])));

        try {
            $registration->register('My application', ['http://example.com/callback']);
            self::fail('Expected a RegistrationException.');
        } catch (RegistrationException $exception) {
            self::assertSame('invalid_redirect_uri', $exception->error);
            self::assertSame('A redirect URI must be an https URL.', $exception->getMessage());
            self::assertSame(400, $exception->getCode());
        }
    }

    public function testThrowsWithoutAClientSecret(): void
    {
        $registration = $this->registration(new Response(201, [], '{"client_id": "connect-client"}'));

        $this->expectException(UnexpectedResponseException::class);

        $registration->register('My application', ['https://example.com/callback']);
    }

    public function testThrowsWhenTheResponseIsNotJson(): void
    {
        $registration = $this->registration(new Response(502, [], '<html>Bad gateway</html>'));

        $this->expectException(UnexpectedResponseException::class);

        $registration->register('My application', ['https://example.com/callback']);
    }

    public function testRefusesARegistrationWithoutRedirectUris(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->registration()->register('My application', []);
    }

    private function registration(Response ...$responses): Registration
    {
        $this->mock = new MockHandler(array_values($responses));

        return new Registration(new Client(['handler' => HandlerStack::create($this->mock)]));
    }
}

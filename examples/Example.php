<?php

declare(strict_types=1);

namespace SolveBeam\Twinfield\Connect\Examples;

use RuntimeException;
use SolveBeam\Twinfield\Connect\ConnectClient;
use SolveBeam\Twinfield\Connect\Registration;

/**
 * What the examples remember between runs, in `~/.config/twinfield-connect/`: the application they
 * registered with Twinfield Connect, and the refresh token of the last login.
 *
 * In your own application you store these yourself, e.g. in your configuration and your database.
 */
final class Example
{
    public const string REDIRECT_URI = 'http://localhost:8765/callback';

    /**
     * The application of the examples. Registers it with Twinfield Connect on the first run.
     */
    public static function client(): ConnectClient
    {
        $stored = json_decode(self::read('client.json') ?? '', true);
        $clientId = is_array($stored) ? ($stored['client_id'] ?? null) : null;
        $clientSecret = is_array($stored) ? ($stored['client_secret'] ?? null) : null;

        if (is_string($clientId) && is_string($clientSecret)) {
            return new ConnectClient($clientId, $clientSecret);
        }

        $client = (new Registration())->register('Twinfield Connect PHP examples', [self::REDIRECT_URI]);

        self::write('client.json', (string) json_encode([
            'client_id' => $client->clientId,
            'client_secret' => $client->clientSecret,
        ]));

        return $client;
    }

    public static function refreshToken(): string
    {
        $refreshToken = self::read('refresh-token');

        if ($refreshToken === null) {
            fwrite(STDERR, 'Log in first: php examples/login/example.php' . PHP_EOL);
            exit(1);
        }

        return $refreshToken;
    }

    public static function storeRefreshToken(string $refreshToken): void
    {
        self::write('refresh-token', $refreshToken);
    }

    private static function read(string $file): ?string
    {
        $path = self::directory() . '/' . $file;
        $contents = is_file($path) ? file_get_contents($path) : false;

        return $contents === false || trim($contents) === '' ? null : trim($contents);
    }

    private static function write(string $file, string $contents): void
    {
        $directory = self::directory();

        if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
            throw new RuntimeException("Could not create {$directory}.");
        }

        $path = $directory . '/' . $file;

        // The client secret and the refresh token are secrets: only readable for you.
        if (file_put_contents($path, $contents . PHP_EOL) === false || !chmod($path, 0600)) {
            throw new RuntimeException("Could not write {$path}.");
        }
    }

    private static function directory(): string
    {
        $home = getenv('HOME') ?: getenv('USERPROFILE') ?: throw new RuntimeException('No home directory found.');

        return $home . '/.config/twinfield-connect';
    }
}

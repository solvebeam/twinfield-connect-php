# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.1] - 2026-11-12

### Changed

- Analyse with PHPStan at level `max`; the login example now checks that the request URI and the session values are strings.

## [0.1.0] - 2026-11-12

### Added

- `TwinfieldConnect` provider for `league/oauth2-client`, to log in at Twinfield through Twinfield Connect.
- `Registration` to register an application with Twinfield Connect, and `ConnectClient` for its client ID and secret.

[unreleased]: https://github.com/solvebeam/twinfield-connect-php/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/solvebeam/twinfield-connect-php/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/solvebeam/twinfield-connect-php/releases/tag/v0.1.0

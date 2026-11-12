# Examples

Small examples you can run. Each folder shows one thing, with an `example.php` and a `README.md` that explains it.

## Setup

There is none: you do not need a Twinfield client ID. Install the dependencies and log in:

```sh
composer install
php examples/login/example.php
```

The first run registers the examples with Twinfield Connect. The client ID and secret it gets, and the refresh token of your login, are saved in `~/.config/twinfield-connect/`, so the other examples start right away. Delete that folder to start over.

## The examples

- [Login](login/): let a user log in to Twinfield and get the refresh token.
- [Refresh](refresh/): get a new access token with the refresh token.
- [Register](register/): register an application of your own with Twinfield Connect.

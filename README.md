# oprf-php

[![Latest Version](https://img.shields.io/packagist/v/noxlogic/oprf.svg)](https://packagist.org/packages/noxlogic/oprf)
[![PHP Version](https://img.shields.io/packagist/php-v/noxlogic/oprf.svg)](https://packagist.org/packages/noxlogic/oprf)
[![License](https://img.shields.io/github/license/noxlogic/oprf-php.svg)](LICENSE)

PHP implementation of the **Oblivious Pseudorandom Function (OPRF)** protocol, base mode, as defined in [RFC 9497](https://www.rfc-editor.org/rfc/rfc9497) using the `ristretto255-SHA-512` suite.

Compatible with [liboprf](https://github.com/stef/liboprf).

## Requirements

- PHP 8.2 or higher
- `ext-sodium` (libsodium ≥ 1.0.18)

## Installation

```bash
composer require noxlogic/oprf
```

## Usage

### Client side

```php
use Noxlogic\Oprf\OprfClient;

$client = new OprfClient();

// Step 1 - blind the input and send $result->blindedElement to the server
$result = $client->blind($input);

// Step 3 - unblind the server's response and compute the pseudonym
$pseudonym = $client->finalize($input, $result->blind, $evaluatedElement);
```

### Server side

```php
use Noxlogic\Oprf\OprfServer;

$server = new OprfServer();

// Generate and store a long-lived key
$key = $server->generateKey();

// Step 2 - evaluate the blinded element received from the client
$evaluatedElement = $server->evaluate($key, $blindedElement);
```

### Full round-trip

```php
$client = new OprfClient();
$server = new OprfServer();
$key    = $server->generateKey();

$blind     = $client->blind('my-input');
$evaluated = $server->evaluate($key, $blind->blindedElement);
$pseudonym = $client->finalize('my-input', $blind->blind, $evaluated);
```

The pseudonym is deterministic: the same input and server key always produce the same output, regardless of the random blind scalar chosen during `blind()`.

## Development

```bash
composer test      # run PHPUnit
composer cs        # check code style
composer cs:fix    # fix code style
composer stan      # run PHPStan (level 8)
composer ci        # run all of the above
```

## License

MIT - see [LICENSE](LICENSE).

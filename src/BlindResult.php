<?php

declare(strict_types=1);

namespace Noxlogic\Oprf;

/**
 * Immutable value object returned by OprfClient::blind().
 * The blind scalar must be kept secret by the client; the blinded element is
 * the value sent to the server.
 */
final class BlindResult
{
    public function __construct(
        /** Random scalar used for blinding (secret, 32 bytes). */
        public readonly string $blind,
        /** Blinded group element to send to the server (32 bytes). */
        public readonly string $blindedElement,
    ) {}
}

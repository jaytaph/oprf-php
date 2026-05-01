<?php

declare(strict_types=1);

namespace Noxlogic\Oprf\Group;

/**
 * Abstraction over the prime-order group used by the OPRF.
 * Designed so the sodium backend can be replaced with a native implementation later.
 */
interface GroupInterface
{
    /** Size of a serialised group element in bytes. */
    public function elementSize(): int;

    /** Size of a scalar in bytes. */
    public function scalarSize(): int;

    /** Map arbitrary bytes to a group element via a hash-to-group function. */
    public function hashToGroup(string $input, string $dst): string;

    /** Reduce 64 bytes of uniform randomness to a scalar. */
    public function hashToScalar(string $input, string $dst): string;

    /** Generate a cryptographically random scalar. */
    public function randomScalar(): string;

    /** Scalar multiplication: scalar * point. */
    public function scalarMult(string $scalar, string $point): string;

    /** Modular inverse of a scalar. */
    public function scalarInvert(string $scalar): string;

    /** Verify that a byte string is a valid group element. */
    public function isValidElement(string $element): bool;
}

<?php

declare(strict_types=1);

namespace Noxlogic\Oprf\Group;

use Noxlogic\Oprf\Exception\OprfException;

/**
 * Ristretto255 group backed by ext-sodium (libsodium >= 1.0.18, PHP >= 8.1).
 *
 * Hash-to-group uses BLAKE2b-512 (sodium_crypto_generichash with 64-byte output)
 * followed by sodium_crypto_core_ristretto255_from_hash.  This approximates the
 * hash_to_ristretto255 construction from RFC 9380 and is intentionally designed
 * to be replaced by a full RFC-compliant implementation later.
 *
 * Constants match OPRF(ristretto255, SHA-512) from RFC 9497.
 */
class Ristretto255Group implements GroupInterface
{
    public function elementSize(): int
    {
        return SODIUM_CRYPTO_CORE_RISTRETTO255_BYTES; // 32
    }

    public function scalarSize(): int
    {
        return SODIUM_CRYPTO_CORE_RISTRETTO255_SCALARBYTES; // 32
    }

    public function hashToGroup(string $input, string $dst): string
    {
        $uniform = $this->expandMessageXmd($input, $dst, 64);

        try {
            return sodium_crypto_core_ristretto255_from_hash($uniform);
        } catch (\SodiumException $e) {
            throw new OprfException('HashToGroup failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function hashToScalar(string $input, string $dst): string
    {
        $uniform = $this->expandMessageXmd($input, $dst, 64);

        return sodium_crypto_core_ristretto255_scalar_reduce($uniform);
    }

    /**
     * expand_message_xmd using SHA-512, as defined in RFC 9380 §5.4.1.
     * SHA-512 parameters: b_in_bytes = 64, r_in_bytes = 128.
     */
    private function expandMessageXmd(string $msg, string $dst, int $lenInBytes): string
    {
        $bInBytes = 64;
        $rInBytes = 128;

        $ell = (int) ceil($lenInBytes / $bInBytes);
        if ($ell > 255 || strlen($dst) > 255) {
            throw new OprfException('expand_message_xmd: parameters out of range');
        }

        $dstPrime = $dst . chr(strlen($dst));
        $msgPrime = str_repeat("\x00", $rInBytes) . $msg . pack('n', $lenInBytes) . "\x00" . $dstPrime;

        $b0 = hash('sha512', $msgPrime, true);
        $bi = hash('sha512', $b0 . "\x01" . $dstPrime, true);
        $uniformBytes = $bi;

        for ($i = 2; $i <= $ell; $i++) {
            $bi = hash('sha512', ($b0 ^ $bi) . chr($i) . $dstPrime, true);
            $uniformBytes .= $bi;
        }

        return substr($uniformBytes, 0, $lenInBytes);
    }

    public function randomScalar(): string
    {
        return sodium_crypto_core_ristretto255_scalar_random();
    }

    public function scalarMult(string $scalar, string $point): string
    {
        try {
            return sodium_crypto_scalarmult_ristretto255($scalar, $point);
        } catch (\SodiumException $e) {
            throw new OprfException('ScalarMult failed: ' . $e->getMessage(), previous: $e);
        }
    }

    public function scalarInvert(string $scalar): string
    {
        return sodium_crypto_core_ristretto255_scalar_invert($scalar);
    }

    public function isValidElement(string $element): bool
    {
        return strlen($element) === $this->elementSize()
            && sodium_crypto_core_ristretto255_is_valid_point($element);
    }
}

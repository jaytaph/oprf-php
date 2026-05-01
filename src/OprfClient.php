<?php

declare(strict_types=1);

namespace Noxlogic\Oprf;

use Noxlogic\Oprf\Exception\OprfException;
use Noxlogic\Oprf\Group\GroupInterface;
use Noxlogic\Oprf\Group\Ristretto255Group;

/**
 * Client side of the OPRF protocol (RFC 9497, base mode).
 *
 * Typical flow:
 *   1. $result = $client->blind($userInput);      // send $result->blindedElement to server
 *   2. $evaluated = $server->evaluate($blindedElement);  // received from server
 *   3. $output = $client->finalize($userInput, $result->blind, $evaluated);
 */
class OprfClient
{
    private string $contextString;

    public function __construct(
        private readonly GroupInterface $group = new Ristretto255Group(),
        string $context = 'OPRFV1',
    ) {
        // Matches liboprf: "OPRFV1-\x00-ristretto255-SHA512"
        $this->contextString = $context . "-\x00-ristretto255-SHA512";
    }

    /**
     * Blind a client input.
     *
     * @return BlindResult  Contains the secret blind scalar and the blinded
     *                      element to transmit to the server.
     */
    public function blind(string $input): BlindResult
    {
        $dst = 'HashToGroup-' . $this->contextString;
        $point = $this->group->hashToGroup($input, $dst);

        $blind = $this->group->randomScalar();
        $blindedElement = $this->group->scalarMult($blind, $point);

        return new BlindResult($blind, $blindedElement);
    }

    /**
     * Finalise the OPRF evaluation.
     *
     * @param string $input           The original client input used in blind().
     * @param string $blind           The secret scalar from BlindResult::$blind.
     * @param string $evaluatedElement The element returned by the server.
     * @return string                 32-byte OPRF output (SHA-512 truncated to the
     *                                hash output length by the RFC — full 64 bytes here).
     * @throws OprfException
     */
    public function finalize(string $input, string $blind, string $evaluatedElement): string
    {
        if (!$this->group->isValidElement($evaluatedElement)) {
            throw new OprfException('Invalid evaluated element received from server.');
        }

        // Unblind: N = r^{-1} * Z  =>  k * HashToGroup(input)
        $invBlind = $this->group->scalarInvert($blind);
        $unblinded = $this->group->scalarMult($invBlind, $evaluatedElement);

        // hashInput per RFC 9497 §3.3.1:
        // I2OSP(len(input), 2) || input || I2OSP(len(N), 2) || N || "Finalize"
        $hashInput = $this->i2osp(strlen($input), 2)
            . $input
            . $this->i2osp(strlen($unblinded), 2)
            . $unblinded
            . 'Finalize';

        return hash('sha512', $hashInput, binary: true);
    }

    /** Encode integer as big-endian byte string of $length bytes (RFC 8017 I2OSP). */
    private function i2osp(int $value, int $length): string
    {
        return str_pad(pack('N', $value), $length, "\x00", STR_PAD_LEFT);
    }
}

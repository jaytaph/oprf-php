<?php

declare(strict_types=1);

namespace Noxlogic\Oprf\Tests;

use Noxlogic\Oprf\BlindResult;
use Noxlogic\Oprf\Exception\OprfException;
use Noxlogic\Oprf\OprfClient;
use Noxlogic\Oprf\OprfServer;
use PHPUnit\Framework\TestCase;

class OprfTest extends TestCase
{
    private OprfServer $server;
    private OprfClient $client;
    private string $key;

    protected function setUp(): void
    {
        $this->server = new OprfServer();
        $this->client = new OprfClient();
        $this->key = $this->server->generateKey();
    }

    public function testFullProtocolRoundTrip(): void
    {
        $input = 'hello world';

        $blindResult = $this->client->blind($input);
        $evaluated = $this->server->evaluate($this->key, $blindResult->blindedElement);
        $output = $this->client->finalize($input, $blindResult->blind, $evaluated);

        $this->assertSame(64, strlen($output)); // SHA-512 output
    }

    public function testSameInputSameKeyProducesSameOutput(): void
    {
        $input = 'deterministic test';

        $r1 = $this->client->blind($input);
        $e1 = $this->server->evaluate($this->key, $r1->blindedElement);
        $o1 = $this->client->finalize($input, $r1->blind, $e1);

        $r2 = $this->client->blind($input);
        $e2 = $this->server->evaluate($this->key, $r2->blindedElement);
        $o2 = $this->client->finalize($input, $r2->blind, $e2);

        // Different blind scalars, same final output — core OPRF property
        $this->assertNotSame($r1->blind, $r2->blind);
        $this->assertSame($o1, $o2);
    }

    public function testDifferentInputsDifferentOutputs(): void
    {
        $r1 = $this->client->blind('input-a');
        $e1 = $this->server->evaluate($this->key, $r1->blindedElement);
        $o1 = $this->client->finalize('input-a', $r1->blind, $e1);

        $r2 = $this->client->blind('input-b');
        $e2 = $this->server->evaluate($this->key, $r2->blindedElement);
        $o2 = $this->client->finalize('input-b', $r2->blind, $e2);

        $this->assertNotSame($o1, $o2);
    }

    public function testDifferentKeysDifferentOutputs(): void
    {
        $key2 = $this->server->generateKey();
        $input = 'same input';

        $r1 = $this->client->blind($input);
        $o1 = $this->client->finalize($input, $r1->blind, $this->server->evaluate($this->key, $r1->blindedElement));

        $r2 = $this->client->blind($input);
        $o2 = $this->client->finalize($input, $r2->blind, $this->server->evaluate($key2, $r2->blindedElement));

        $this->assertNotSame($o1, $o2);
    }

    public function testInvalidEvaluatedElementThrows(): void
    {
        $this->expectException(OprfException::class);

        $blindResult = $this->client->blind('test');
        $this->client->finalize('test', $blindResult->blind, str_repeat("\x00", 32));
    }

    public function testInvalidBlindedElementThrows(): void
    {
        $this->expectException(OprfException::class);

        $this->server->evaluate($this->key, str_repeat("\x00", 32));
    }

    public function testBlindResultContainsCorrectSizes(): void
    {
        $result = $this->client->blind('test');

        $this->assertInstanceOf(BlindResult::class, $result);
        $this->assertSame(32, strlen($result->blind));
        $this->assertSame(32, strlen($result->blindedElement));
    }

    public function testObliviousness(): void
    {
        // Server cannot distinguish between blinded elements for different inputs
        // because blinding is randomised.  We verify that two blindings of the
        // same input look different to an observer (i.e. the server).
        $r1 = $this->client->blind('secret');
        $r2 = $this->client->blind('secret');

        $this->assertNotSame($r1->blindedElement, $r2->blindedElement);
    }

    public function testBinaryInputs(): void
    {
        $input = random_bytes(128);

        $blindResult = $this->client->blind($input);
        $evaluated = $this->server->evaluate($this->key, $blindResult->blindedElement);
        $output = $this->client->finalize($input, $blindResult->blind, $evaluated);

        $this->assertSame(64, strlen($output));
    }

    public function testEmptyInput(): void
    {
        $blindResult = $this->client->blind('');
        $evaluated = $this->server->evaluate($this->key, $blindResult->blindedElement);
        $output = $this->client->finalize('', $blindResult->blind, $evaluated);

        $this->assertSame(64, strlen($output));
    }
}

<?php

namespace Amp\Socket\Test;

use Amp\Socket\ClientSocket;
use Amp\Socket\ClientTlsContext;
use PHPUnit\Framework\TestCase;
use function Amp\Socket\connect;
use function Amp\Socket\cryptoConnect;

class IntegrationTest extends TestCase
{
    /**
     * @dataProvider provideConnectArgs
     */
    public function testConnect($uri): void
    {
        $sock = connect($uri);
        $this->assertInstanceOf(ClientSocket::class, $sock);
    }

    public function provideConnectArgs(): array
    {
        return [
            ['www.google.com:80'],
            ['www.yahoo.com:80'],
        ];
    }

    /**
     * @dataProvider provideCryptoConnectArgs
     */
    public function testCryptoConnect($uri): void
    {
        $sock = cryptoConnect($uri);
        $this->assertInstanceOf(ClientSocket::class, $sock);
    }

    public function provideCryptoConnectArgs(): array
    {
        return [
            ['stackoverflow.com:443'],
            ['github.com:443'],
            ['raw.githubusercontent.com:443'],
        ];
    }

    public function testNoRenegotiationForEqualOptions(): void
    {
        $socket = cryptoConnect('www.google.com:443');
        // For this case renegotiation not needed because options is equals
        $socket->enableCrypto((new ClientTlsContext)->withPeerName("www.google.com"));
        $this->assertTrue(true);
    }

    public function testRenegotiation(): void
    {
        $this->markTestSkipped("Expected failure: proper renegotiation does not work yet");

        $sock = cryptoConnect('www.google.com:443', null, (new ClientTlsContext)->withPeerName("www.google.com"));

        // force renegotiation by different option...
        $sock->enableCrypto((new ClientTlsContext)->withoutPeerVerification());

        $this->assertInstanceOf(ClientSocket::class, $sock);
    }
}

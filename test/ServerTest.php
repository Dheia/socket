<?php

namespace Amp\Socket\Test;

use Amp\Loop;
use Amp\Socket;
use Concurrent\Task;
use PHPUnit\Framework\TestCase;
use function Amp\delay;
use function Amp\rethrow;

class ServerTest extends TestCase
{
    public function testAccept(): void
    {
        $server = Socket\listen("127.0.0.1:0");

        Task::async(function () use ($server) {
            while ($socket = $server->accept()) {
                $this->assertInstanceOf(Socket\ServerSocket::class, $socket);
            }
        });

        Socket\connect($server->getAddress());

        Loop::delay(100, [$server, 'close']);
    }

    public function testTls(): void
    {
        $tlsContext = (new Socket\ServerTlsContext)
            ->withDefaultCertificate(new Socket\Certificate(__DIR__ . "/tls/amphp.org.pem"));
        $server = Socket\listen("127.0.0.1:0", null, $tlsContext);

        rethrow(Task::async(function () use ($server) {
            while ($socket = $server->accept()) {
                rethrow(Task::async(function () use ($socket) {
                    $socket->enableCrypto();
                    $this->assertInstanceOf(Socket\ServerSocket::class, $socket);
                    $this->assertSame("Hello World", $socket->read());
                    $socket->write("test");
                }));
            }
        }));

        $context = (new Socket\ClientTlsContext)
            ->withPeerName("amphp.org")
            ->withCaFile(__DIR__ . "/tls/amphp.org.crt");

        $client = Socket\cryptoConnect($server->getAddress(), null, $context);
        $client->write("Hello World");

        $this->assertSame("test", $client->read());

        $server->close();

        Loop::stop();
    }

    public function testSniWorksWithCorrectHostName(): void
    {
        $tlsContext = (new Socket\ServerTlsContext)
            ->withCertificates(["amphp.org" => new Socket\Certificate(__DIR__ . "/tls/amphp.org.pem")]);
        $server = Socket\listen("127.0.0.1:0", null, $tlsContext);

        rethrow(Task::async(function () use ($server) {
            while ($socket = $server->accept()) {
                rethrow(Task::async(function () use ($socket) {
                    $socket->enableCrypto();
                    $this->assertInstanceOf(Socket\ServerSocket::class, $socket);
                    $this->assertSame("Hello World", $socket->read());
                    $socket->write("test");
                }));
            }
        }));

        $context = (new Socket\ClientTlsContext)
            ->withPeerName("amphp.org")
            ->withCaFile(__DIR__ . "/tls/amphp.org.crt");

        $client = Socket\cryptoConnect($server->getAddress(), null, $context);
        $client->write("Hello World");

        $this->assertSame("test", $client->read());

        $server->close();

        Loop::stop();
    }

    public function testSniWorksWithMultipleCertificates(): void
    {
        $tlsContext = (new Socket\ServerTlsContext)->withCertificates([
            "amphp.org" => new Socket\Certificate(__DIR__ . "/tls/amphp.org.pem"),
            "www.amphp.org" => new Socket\Certificate(__DIR__ . "/tls/www.amphp.org.pem"),
        ]);

        $server = Socket\listen("127.0.0.1:0", null, $tlsContext);

        rethrow(Task::async(function () use ($server) {
            while ($socket = $server->accept()) {
                rethrow(Task::async(function () use ($socket) {
                    $socket->enableCrypto();
                    $this->assertInstanceOf(Socket\ServerSocket::class, $socket);
                    $this->assertSame("Hello World", $socket->read());
                    $socket->write("test");
                }));
            }
        }));

        $context = (new Socket\ClientTlsContext)
            ->withPeerName("amphp.org")
            ->withCaFile(__DIR__ . "/tls/amphp.org.crt");

        $client = Socket\cryptoConnect($server->getAddress(), null, $context);
        $client->write("Hello World");

        $context = (new Socket\ClientTlsContext)
            ->withPeerName("www.amphp.org")
            ->withCaFile(__DIR__ . "/tls/www.amphp.org.crt");

        $client = Socket\cryptoConnect($server->getAddress(), null, $context);
        $client->write("Hello World");

        delay(1);
        $server->close();
        Loop::stop();
    }

    public function testSniWorksWithMultipleCertificatesAndDifferentFilesForCertAndKey(): void
    {
        if (\PHP_VERSION_ID < 70200) {
            $this->markTestSkipped("This test requires PHP 7.2 or higher.");
        }

        Loop::run(function () {
            $tlsContext = (new Socket\ServerTlsContext)->withCertificates([
                "amphp.org" => new Socket\Certificate(__DIR__ . "/tls/amphp.org.crt", __DIR__ . "/tls/amphp.org.key"),
                "www.amphp.org" => new Socket\Certificate(__DIR__ . "/tls/www.amphp.org.crt", __DIR__ . "/tls/www.amphp.org.key"),
            ]);

            $server = Socket\listen("127.0.0.1:0", null, $tlsContext);

            rethrow(Task::async(function () use ($server) {
                while ($socket = $server->accept()) {
                    rethrow(Task::async(function () use ($socket) {
                        $socket->enableCrypto();
                        $this->assertInstanceOf(Socket\ServerSocket::class, $socket);
                        $this->assertSame("Hello World", $socket->read());
                        $socket->write("test");
                    }));
                }
            }));

            $context = (new Socket\ClientTlsContext)
                ->withPeerName("amphp.org")
                ->withCaFile(__DIR__ . "/tls/amphp.org.crt");

            $client = Socket\cryptoConnect($server->getAddress(), null, $context);
            $client->write("Hello World");

            $context = (new Socket\ClientTlsContext)
                ->withPeerName("www.amphp.org")
                ->withCaFile(__DIR__ . "/tls/www.amphp.org.crt");

            $client = Socket\cryptoConnect($server->getAddress(), null, $context);
            $client->write("Hello World");

            delay(1);
            $server->close();
            Loop::stop();
        });
    }
}

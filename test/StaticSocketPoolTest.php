<?php

namespace Amp\Socket\Test;

use Amp\PHPUnit\TestCase;
use Amp\Socket\ClientSocket;
use Amp\Socket\SocketPool;
use Amp\Socket\StaticSocketPool;
use Concurrent\Deferred;

class StaticSocketPoolTest extends TestCase {
    public function testCheckout(): void
    {
        $underlyingSocketPool = $this->prophesize(SocketPool::class);
        $staticSocketPool = new StaticSocketPool('override-uri', $underlyingSocketPool->reveal());

        $mock = $this->createMock(ClientSocket::class);
        $underlyingSocketPool->checkout('override-uri', null)->shouldBeCalled()->willReturn($mock);

        $returned = $staticSocketPool->checkout('test-url');

        self::assertEquals($mock, $returned);
    }

    public function testCheckin(): void
    {
        $underlyingSocketPool = $this->prophesize(SocketPool::class);
        $staticSocketPool = new StaticSocketPool('override-uri', $underlyingSocketPool->reveal());

        $clientSocket = new ClientSocket(fopen('php://memory', 'rw+'));
        $underlyingSocketPool->checkin($clientSocket)->shouldBeCalled();

        $staticSocketPool->checkin($clientSocket);
    }

    public function testClear(): void
    {
        $underlyingSocketPool = $this->prophesize(SocketPool::class);
        $staticSocketPool = new StaticSocketPool('override-uri', $underlyingSocketPool->reveal());

        $clientSocket = new ClientSocket(fopen('php://memory', 'rw+'));
        $underlyingSocketPool->clear($clientSocket)->shouldBeCalled();

        $staticSocketPool->clear($clientSocket);
    }
}

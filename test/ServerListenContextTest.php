<?php

namespace Amp\Socket\Test;

use Amp\Socket\ServerListenContext;
use PHPUnit\Framework\TestCase;

class ServerListenContextTest extends TestCase
{
    public function bindToDataProvider(): array
    {
        return [
            [null],
            ['127.0.0.1:123'],
        ];
    }

    /**
     * @dataProvider bindToDataProvider
     */
    public function testWithBindTo(?string $bindTo): void
    {
        $origContext = new ServerListenContext();
        $clonedContext = $origContext->withBindTo($bindTo);
        $this->assertNull($origContext->getBindTo());
        $this->assertSame($bindTo, $clonedContext->getBindTo());
    }

    public function testWithTcpNoDelay(): void
    {
        $context = new ServerListenContext();
        $clonedContext = $context->withTcpNoDelay();

        $this->assertFalse($context->hasTcpNoDelay());
        $this->assertTrue($clonedContext->hasTcpNoDelay());
    }

    public function backlogDataProvider(): array
    {
        return [
            [10],
            [123],
        ];
    }

    /**
     * @dataProvider backlogDataProvider
     */
    public function testWithBacklog($backlog): void
    {
        $origContext = new ServerListenContext();
        $clonedContext = $origContext->withBacklog($backlog);
        $this->assertSame(128, $origContext->getBacklog());
        $this->assertSame($backlog, $clonedContext->getBacklog());
    }

    public function testWithReusePort(): void
    {
        $origContext = new ServerListenContext();
        $clonedContext = $origContext->withReusePort();
        $this->assertFalse($origContext->hasReusePort());
        $this->assertTrue($clonedContext->hasReusePort());
    }

    public function testWithoutReusePort(): void
    {
        $origContext = new ServerListenContext();
        $clonedContext = $origContext->withoutReusePort();
        $this->assertFalse($origContext->hasReusePort());
        $this->assertFalse($clonedContext->hasReusePort());
    }

    public function testWithBroadcast(): void
    {
        $origContext = new ServerListenContext();
        $clonedContext = $origContext->withBroadcast();
        $this->assertFalse($origContext->hasBroadcast());
        $this->assertTrue($clonedContext->hasBroadcast());
    }

    public function testWithoutBroadcast(): void
    {
        $origContext = new ServerListenContext();
        $clonedContext = $origContext->withoutBroadcast();
        $this->assertFalse($origContext->hasBroadcast());
        $this->assertFalse($clonedContext->hasBroadcast());
    }
}

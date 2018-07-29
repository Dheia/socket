<?php

namespace Amp\Socket\Test;

use Amp\Dns\Record;
use Amp\Socket\ClientConnectContext;
use PHPUnit\Framework\TestCase;

class ClientConnectContextTest extends TestCase
{
    public function bindToDataProvider(): array
    {
        return [
            [null],
            ['127.0.0.1:12345'],
        ];
    }

    /**
     * @dataProvider bindToDataProvider
     */
    public function testWithBindTo(?string $bindTo): void
    {
        $context = new ClientConnectContext();
        $clonedContext = $context->withBindTo($bindTo);

        $this->assertNull($context->getBindTo());
        $this->assertSame($bindTo, $clonedContext->getBindTo());
    }

    public function testWithTcpNoDelay(): void
    {
        $context = new ClientConnectContext();
        $clonedContext = $context->withTcpNoDelay();

        $this->assertFalse($context->hasTcpNoDelay());
        $this->assertTrue($clonedContext->hasTcpNoDelay());
    }

    public function withConnectTimeoutDataProvider(): array
    {
        return [
            [1],
            [12345],
        ];
    }

    /**
     * @dataProvider withConnectTimeoutDataProvider
     */
    public function testWithConnectTimeout($timeout): void
    {
        $context = new ClientConnectContext();
        $clonedContext = $context->withConnectTimeout($timeout);

        $this->assertSame(10000, $context->getConnectTimeout());
        $this->assertSame($timeout, $clonedContext->getConnectTimeout());
    }

    public function withConnectTimeoutInvalidTimeoutDataProvider(): array
    {
        return [
            [0],
            [-1],
            [-123456],
        ];
    }

    /**
     * @dataProvider withConnectTimeoutInvalidTimeoutDataProvider
     */
    public function testWithConnectTimeoutInvalidTimeout($timeout): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage("Invalid connect timeout ({$timeout}), must be greater than 0");
        $context = new ClientConnectContext();
        $context->withConnectTimeout($timeout);
    }

    public function withMaxAttemptsDataProvider(): array
    {
        return [
            [1],
            [12345],
        ];
    }

    /**
     * @dataProvider withMaxAttemptsDataProvider
     */
    public function testWithMaxAttempts($maxAttempts): void
    {
        $context = new ClientConnectContext();
        $clonedContext = $context->withMaxAttempts($maxAttempts);

        $this->assertSame(2, $context->getMaxAttempts());
        $this->assertSame($maxAttempts, $clonedContext->getMaxAttempts());
    }

    public function withMaxAttemptsInvalidTimeoutDataProvider(): array
    {
        return [
            [0],
            [-1],
            [-123456],
        ];
    }

    /**
     * @dataProvider withMaxAttemptsInvalidTimeoutDataProvider
     */
    public function testWithMaxAttemptsInvalidTimeout($maxAttempts): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage("Invalid max attempts ({$maxAttempts}), must be greater than 0");
        $context = new ClientConnectContext();
        $context->withMaxAttempts($maxAttempts);
    }

    public function withDnsTypeRestrictionDataProvider(): array
    {
        return [
            [null],
            [Record::AAAA],
            [Record::A],
        ];
    }

    /**
     * @dataProvider withDnsTypeRestrictionDataProvider
     */
    public function testWithDnsTypeRestriction($type): void
    {
        $context = new ClientConnectContext();
        $clonedContext = $context->withDnsTypeRestriction($type);

        $this->assertNull($context->getDnsTypeRestriction());
        $this->assertSame($type, $clonedContext->getDnsTypeRestriction());
    }

    public function withDnsTypeRestrictionInvalidTypeDataProvider(): array
    {
        return [
            [Record::NS],
            [Record::MX],
        ];
    }

    /**
     * @dataProvider withDnsTypeRestrictionInvalidTypeDataProvider
     * @expectedException \Error
     * @expectedExceptionMessage Invalid resolver type restriction
     */
    public function testWithDnsTypeRestrictionInvalidType($type): void
    {
        $context = new ClientConnectContext();
        $context->withDnsTypeRestriction($type);
    }

    public function testToStreamContextArray(): void
    {
        $context = new ClientConnectContext();
        $clonedContext = $context->withBindTo('127.0.0.1:12345');

        $this->assertSame(['socket' => ['tcp_nodelay' => false]], $context->toStreamContextArray());
        $this->assertSame(['socket' => [
            'tcp_nodelay' => false,
            'bindto' => '127.0.0.1:12345',
        ]], $clonedContext->toStreamContextArray());
    }
}

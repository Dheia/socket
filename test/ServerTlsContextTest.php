<?php

namespace Amp\Socket\Test;

use Amp\Socket\Certificate;
use Amp\Socket\ServerTlsContext;
use PHPUnit\Framework\TestCase;

class ServerTlsContextTest extends TestCase
{
    public function minimumVersionDataProvider(): array
    {
        return [
            [ServerTlsContext::TLSv1_0],
            [ServerTlsContext::TLSv1_1],
            [ServerTlsContext::TLSv1_2],
        ];
    }

    /**
     * @dataProvider minimumVersionDataProvider
     */
    public function testWithMinimumVersion($version): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withMinimumVersion($version);

        $this->assertSame(ServerTlsContext::TLSv1_0, $context->getMinimumVersion());
        $this->assertSame($version, $clonedContext->getMinimumVersion());
    }

    public function minimumVersionInvalidDataProvider(): array
    {
        return [
            [-1],
        ];
    }

    /**
     * @dataProvider minimumVersionInvalidDataProvider
     * @expectedException \Error
     * @expectedExceptionMessage Invalid minimum version, only TLSv1.0, TLSv1.1 or TLSv1.2 allowed
     */
    public function testWithMinimumVersionInvalid($version): void
    {
        (new ServerTlsContext)->withMinimumVersion($version);
    }

    public function peerNameDataProvider(): array
    {
        return [
            [null],
            ['test'],
        ];
    }

    /**
     * @dataProvider peerNameDataProvider
     */
    public function testWithPeerName($peerName): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withPeerName($peerName);

        $this->assertNull($context->getPeerName());
        $this->assertSame($peerName, $clonedContext->getPeerName());
    }

    public function testWithPeerVerification(): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withPeerVerification();

        $this->assertFalse($context->hasPeerVerification());
        $this->assertTrue($clonedContext->hasPeerVerification());
    }

    public function testWithoutPeerVerification(): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withoutPeerVerification();

        $this->assertFalse($context->hasPeerVerification());
        $this->assertFalse($clonedContext->hasPeerVerification());
    }

    public function verifyDepthDataProvider(): array
    {
        return [
            [0],
            [123],
        ];
    }

    /**
     * @dataProvider verifyDepthDataProvider
     */
    public function testWithVerificationDepth($verifyDepth): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withVerificationDepth($verifyDepth);

        $this->assertSame(10, $context->getVerificationDepth());
        $this->assertSame($verifyDepth, $clonedContext->getVerificationDepth());
    }

    public function verifyDepthInvalidDataProvider(): array
    {
        return [
            [-1],
            [-123],
        ];
    }

    /**
     * @dataProvider verifyDepthInvalidDataProvider
     * @expectedException \Error
     * @expectedExceptionMessageRegExp /Invalid verification depth (.*), must be greater than or equal to 0/
     */
    public function testWithVerificationDepthInvalid($verifyDepth): void
    {
        (new ServerTlsContext)->withVerificationDepth($verifyDepth);
    }

    public function ciphersDataProvider(): array
    {
        return [
            ['ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256'],
            ['DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256'],
        ];
    }

    /**
     * @dataProvider ciphersDataProvider
     */
    public function testWithCiphers($ciphers): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withCiphers($ciphers);

        $this->assertSame(\OPENSSL_DEFAULT_STREAM_CIPHERS, $context->getCiphers());
        $this->assertSame($ciphers, $clonedContext->getCiphers());
    }

    public function caFileDataProvider(): array
    {
        return [
            [null],
            ['test'],
        ];
    }

    /**
     * @dataProvider caFileDataProvider
     */
    public function testWithCaFile($caFile): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withCaFile($caFile);

        $this->assertNull($context->getCaFile());
        $this->assertSame($caFile, $clonedContext->getCaFile());
    }

    public function caPathDataProvider(): array
    {
        return [
            [null],
            ['test'],
        ];
    }

    /**
     * @dataProvider caPathDataProvider
     */
    public function testWithCaPath($caPath): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withCaPath($caPath);

        $this->assertNull($context->getCaPath());
        $this->assertSame($caPath, $clonedContext->getCaPath());
    }

    public function testWithPeerCapturing(): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withPeerCapturing();

        $this->assertFalse($context->hasPeerCapturing());
        $this->assertTrue($clonedContext->hasPeerCapturing());
    }

    public function testWithoutPeerCapturing(): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withoutPeerCapturing();

        $this->assertFalse($context->hasPeerCapturing());
        $this->assertFalse($clonedContext->hasPeerCapturing());
    }

    public function defaultCertificateDataProvider(): array
    {
        return [
            [null],
            [new Certificate('test')],
        ];
    }

    /**
     * @dataProvider defaultCertificateDataProvider
     */
    public function testWithDefaultCertificate($defaultCertificate): void
    {
        $context = new ServerTlsContext;
        $clonedContext = $context->withDefaultCertificate($defaultCertificate);

        $this->assertNull($context->getDefaultCertificate());
        $this->assertSame($defaultCertificate, $clonedContext->getDefaultCertificate());
    }

    public function testWithCertificatesErrorWithoutStringKeys(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Expected an array mapping domain names to Certificate instances");

        (new ServerTlsContext)->withCertificates([new Certificate("/foo/bar")]);
    }

    public function testWithCertificatesErrorWithoutCertificateInstances(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Expected an array of Certificate instances");

        (new ServerTlsContext)->withCertificates(["example.com" => "/foo/bar"]);
    }

    public function testWithCertificatesWithDifferentPathsBeforePhp72(): void
    {
        if (\PHP_VERSION_ID >= 70200) {
            $this->markTestSkipped("Only relevant in versions lower to PHP 7.2");
        }

        $this->expectException(\Error::class);
        $this->expectExceptionMessage("Different files for cert and key are not supported on this version of PHP. Please upgrade to PHP 7.2 or later.");

        (new ServerTlsContext)->withCertificates(["example.com" => new Certificate("/var/foo", "/foo/bar")]);
    }

    public function invalidSecurityLevelDataProvider(): array
    {
        return [
            [-1],
            [6],
        ];
    }

    /**
     * @dataProvider invalidSecurityLevelDataProvider
     */
    public function testWithSecurityLevelInvalid($level): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage("Invalid security level ({$level}), must be between 0 and 5.");

        (new ServerTlsContext)->withSecurityLevel($level);
    }

    public function validSecurityLevelDataProvider(): array
    {
        return [
            [0],
            [1],
            [2],
            [3],
            [4],
            [5],
        ];
    }

    /**
     * @dataProvider validSecurityLevelDataProvider
     */
    public function testWithSecurityLevelValid($level): void
    {
        if (\OPENSSL_VERSION_NUMBER >= 0x10100000) {
            $value = (new ServerTlsContext)
                ->withSecurityLevel($level)
                ->getSecurityLevel();

            $this->assertSame($level, $value);
        } else {
            $this->expectException(\Error::class);
            $this->expectExceptionMessage("Can't set a security level, as PHP is compiled with OpenSSL < 1.1.0.");

            (new ServerTlsContext)->withSecurityLevel($level);
        }
    }

    public function testWithSecurityLevelDefaultValue(): void
    {
        if (\OPENSSL_VERSION_NUMBER >= 0x10100000) {
            $this->assertSame(2, (new ServerTlsContext)->getSecurityLevel());
        } else {
            $this->assertSame(0, (new ServerTlsContext)->getSecurityLevel());
        }
    }
}

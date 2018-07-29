<?php

namespace Amp\Socket;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use Amp\ByteStream\ResourceInputStream;
use Amp\ByteStream\ResourceOutputStream;

abstract class Socket implements InputStream, OutputStream
{
    public const DEFAULT_CHUNK_SIZE = ResourceInputStream::DEFAULT_CHUNK_SIZE;

    /** @var ResourceInputStream */
    private $reader;

    /** @var ResourceOutputStream */
    private $writer;

    /** @var int */
    private $streamId;

    /**
     * @param resource $resource Stream resource.
     * @param int      $chunkSize Read and write chunk size.
     *
     * @throws \Error If a stream resource is not given for $resource.
     */
    public function __construct($resource, int $chunkSize = self::DEFAULT_CHUNK_SIZE)
    {
        $this->streamId = (int) $resource;
        $this->reader = new ResourceInputStream($resource, $chunkSize);
        $this->writer = new ResourceOutputStream($resource, $chunkSize);
    }

    /**
     * Raw stream socket resource.
     *
     * @return resource|null
     */
    public function getResource()
    {
        return $this->reader->getResource();
    }

    /**
     * Raw stream socket resource ID.
     *
     * @return int
     */
    public function getResourceId(): int
    {
        return $this->streamId;
    }

    /**
     * Enables encryption on this socket.
     *
     * @throws ClosedException
     */
    abstract public function enableCrypto(): void;

    /**
     * Disables encryption on this socket.
     *
     * @throws ClosedException
     */
    public function disableCrypto(): void
    {
        if (($resource = $this->reader->getResource()) === null) {
            throw new ClosedException("The socket has been closed");
        }

        Internal\disableCrypto($resource);
    }

    /** @inheritdoc */
    public function read(): ?string
    {
        return $this->reader->read();
    }

    /** @inheritdoc */
    public function write(string $data): void
    {
        $this->writer->write($data);
    }

    /** @inheritdoc */
    public function end(string $data = ""): void
    {
        $this->writer->end($data);
        $this->close();
    }

    /**
     * References the read watcher, so the loop keeps running in case there's an active read.
     *
     * @see Loop::reference()
     */
    public function reference(): void
    {
        $this->reader->reference();
    }

    /**
     * Unreferences the read watcher, so the loop doesn't keep running even if there are active reads.
     *
     * @see Loop::unreference()
     */
    public function unreference(): void
    {
        $this->reader->unreference();
    }

    /**
     * Force closes the socket, failing any pending reads or writes.
     */
    public function close(): void
    {
        $this->reader->close();
        $this->writer->close();
    }

    public function getLocalAddress(): ?string
    {
        return $this->getAddress(false);
    }

    public function getRemoteAddress(): ?string
    {
        return $this->getAddress(true);
    }

    private function getAddress(bool $wantPeer): ?string
    {
        $remoteCleaned = Internal\cleanupSocketName(@\stream_socket_get_name($this->getResource(), $wantPeer));

        if ($remoteCleaned !== null) {
            return $remoteCleaned;
        }

        $meta = @stream_get_meta_data($this->getResource()) ?? [];

        if (array_key_exists('stream_type', $meta) && $meta['stream_type'] === 'unix_socket') {
            return Internal\cleanupSocketName(@\stream_socket_get_name($this->getResource(), !$wantPeer));
        }

        return null;
    }
}

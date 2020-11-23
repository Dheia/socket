<?php

namespace Amp\Socket;

use Amp\Loop;

final class DatagramSocket
{
    public const DEFAULT_CHUNK_SIZE = 8192;

    /**
     * Create a new Datagram (UDP server) on the specified server address.
     *
     * @param string           $uri URI in scheme://host:port format. UDP is assumed if no scheme is present.
     * @param BindContext|null $context Context options for listening.
     *
     * @return DatagramSocket
     *
     * @throws SocketException If binding to the specified URI failed.
     * @throws \Error If an invalid scheme is given.
     */
    public static function bind(string $uri, ?BindContext $context = null): self
    {
        $context = $context ?? new BindContext;

        $scheme = \strstr($uri, '://', true);

        if ($scheme === false) {
            $uri = 'udp://' . $uri;
        } elseif ($scheme !== 'udp') {
            throw new \Error('Only udp scheme allowed for datagram creation');
        }

        $streamContext = \stream_context_create($context->toStreamContextArray());

        // Error reporting suppressed since stream_socket_server() emits an E_WARNING on failure (checked below).
        $server = @\stream_socket_server($uri, $errno, $errstr, STREAM_SERVER_BIND, $streamContext);

        if (!$server || $errno) {
            throw new SocketException(
                \sprintf('Could not create datagram %s: [Error: #%d] %s', $uri, $errno, $errstr),
                $errno
            );
        }

        return new self($server, $context->getChunkSize());
    }

    /** @var resource|null UDP socket resource. */
    private $socket;

    private string $watcher;

    private SocketAddress $address;

    private \Closure $enqueue;

    private ?\Fiber $reader = null;

    private int $chunkSize;

    /**
     * @param resource $socket A bound udp socket resource
     * @param int      $chunkSize Maximum chunk size for the
     *
     * @throws \Error If a stream resource is not given for $socket.
     */
    public function __construct($socket, int $chunkSize = self::DEFAULT_CHUNK_SIZE)
    {
        if (!\is_resource($socket) || \get_resource_type($socket) !== 'stream') {
            throw new \Error('Invalid resource given to constructor!');
        }

        $this->socket = $socket;
        $this->address = SocketAddress::fromLocalResource($socket);
        $this->chunkSize = &$chunkSize;

        \stream_set_blocking($this->socket, false);

        $reader = &$this->reader;

        $this->enqueue = static function (\Fiber $fiber) use (&$reader): void {
            $reader = $fiber;
        };

        $this->watcher = Loop::onReadable($this->socket, static function ($watcher, $socket) use (
            &$reader,
            &$chunkSize
        ): void {
            $fiber = $reader;
            $reader = null;

            \assert($fiber !== null);

            $data = @\stream_socket_recvfrom($socket, $chunkSize, 0, $address);

            /** @psalm-suppress TypeDoesNotContainType */
            if ($data === false) {
                Loop::cancel($watcher);
                $fiber->resume();
                return;
            }

            $fiber->resume([SocketAddress::fromSocketName($address), $data]);

            /** @psalm-suppress RedundantCondition Resuming of the fiber above might read immediately again */
            if (!$reader) {
                Loop::disable($watcher);
            }
        });

        Loop::disable($this->watcher);
    }

    /**
     * Automatically cancels the loop watcher.
     */
    public function __destruct()
    {
        if (!$this->socket) {
            return;
        }

        $this->free();
    }

    /**
     * @return array{0: SocketAddress, 1: string}|null Resolves with null if the socket is closed.
     *
     * @throws PendingReceiveError If a receive request is already pending.
     */
    public function receive(): ?array
    {
        if ($this->reader) {
            throw new PendingReceiveError;
        }

        if (!$this->socket) {
            return null; // Resolve with null when endpoint is closed.
        }

        Loop::enable($this->watcher);

        return \Fiber::suspend($this->enqueue, Loop::get());
    }

    /**
     * @param SocketAddress $address
     * @param string        $data
     *
     * @return int Returns with the number of bytes written to the socket.
     *
     * @throws SocketException If the UDP socket closes before the data can be sent.
     */
    public function send(SocketAddress $address, string $data): int
    {
        if (!$this->socket) {
            throw new SocketException('The endpoint is not writable');
        }

        try {
            \set_error_handler(static function (int $errno, string $errstr) {
                throw new SocketException(\sprintf('Could not send packet on endpoint: %s', $errstr));
            });

            $result = \stream_socket_sendto($this->socket, $data, 0, $address->toString());
            /** @psalm-suppress TypeDoesNotContainType */
            if ($result < 0 || $result === false) {
                throw new SocketException('Could not send packet on endpoint: Unknown error');
            }
        } finally {
            \restore_error_handler();
        }

        return $result;
    }

    /**
     * Raw stream socket resource.
     *
     * @return resource|null
     */
    final public function getResource()
    {
        return $this->socket;
    }

    /**
     * References the receive watcher.
     *
     * @see Loop::reference()
     */
    final public function reference(): void
    {
        Loop::reference($this->watcher);
    }

    /**
     * Unreferences the receive watcher.
     *
     * @see Loop::unreference()
     */
    final public function unreference(): void
    {
        Loop::unreference($this->watcher);
    }

    /**
     * Closes the datagram socket and stops receiving data. Any pending read is resolved with null.
     */
    public function close(): void
    {
        if ($this->socket) {
            /** @psalm-suppress InvalidPropertyAssignmentValue */
            \fclose($this->socket);
        }

        $this->free();
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->socket === null;
    }

    /**
     * @return SocketAddress
     */
    public function getAddress(): SocketAddress
    {
        return $this->address;
    }

    /**
     * @param int $chunkSize The new maximum packet size to receive.
     */
    public function setChunkSize(int $chunkSize): void
    {
        $this->chunkSize = $chunkSize;
    }

    private function free(): void
    {
        Loop::cancel($this->watcher);

        $this->socket = null;

        if ($this->reader) {
            $reader = $this->reader;
            $this->reader = null;
            Loop::defer(static fn() => $reader->resume());
        }
    }
}

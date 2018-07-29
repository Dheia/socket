<?php

namespace Amp\Socket;

use Amp\Cancellation\Token;
use Amp\Loop;
use Amp\Struct;
use Amp\Uri\Uri;

final class BasicSocketPool implements SocketPool
{
    private $sockets = [];
    private $socketIdUriMap = [];
    private $pendingCount = [];

    private $idleTimeout;
    private $socketContext;

    public function __construct(int $idleTimeout = 10000, ClientConnectContext $socketContext = null)
    {
        $this->idleTimeout = $idleTimeout;
        $this->socketContext = $socketContext ?? new ClientConnectContext;
    }

    private function normalizeUri(string $uri): string
    {
        if (stripos($uri, 'unix://') === 0) {
            return $uri;
        }

        return (new Uri($uri))->normalize();
    }

    /** @inheritdoc */
    public function checkout(string $uri, Token $token = null): ClientSocket
    {
        // A request might already be cancelled before we reach the checkout, so do not even attempt to checkout in that
        // case. The weird logic is required to throw the token's exception instead of creating a new one.
        if ($token) {
            $token->throwIfRequested();
        }

        $uri = $this->normalizeUri($uri);

        if (empty($this->sockets[$uri])) {
            return $this->checkoutNewSocket($uri, $token);
        }

        foreach ($this->sockets[$uri] as $socketId => $socket) {
            if (!$socket->isAvailable) {
                continue;
            }

            if (!\is_resource($socket->resource) || \feof($socket->resource)) {
                $this->clear(new ClientSocket($socket->resource));
                continue;
            }

            $socket->isAvailable = false;

            if ($socket->idleWatcher !== null) {
                Loop::disable($socket->idleWatcher);
            }

            return new ClientSocket($socket->resource);
        }

        return $this->checkoutNewSocket($uri, $token);
    }

    private function checkoutNewSocket(string $uri, Token $token = null): ClientSocket
    {
        $this->pendingCount[$uri] = ($this->pendingCount[$uri] ?? 0) + 1;

        try {
            $clientSocket = connect($uri, $this->socketContext, $token);
        } finally {
            if (--$this->pendingCount[$uri] === 0) {
                unset($this->pendingCount[$uri]);
            }
        }

        $socketId = $clientSocket->getResourceId();

        $socket = new class
        {
            use Struct;

            public $id;
            public $uri;
            public $resource;
            public $isAvailable;
            public $idleWatcher;
        };

        $socket->id = $socketId;
        $socket->uri = $uri;
        $socket->resource = $clientSocket->getResource();
        $socket->isAvailable = false;

        $this->sockets[$uri][$socketId] = $socket;
        $this->socketIdUriMap[$socketId] = $uri;

        return $clientSocket;
    }

    /** @inheritdoc */
    public function clear(ClientSocket $socket): void
    {
        $socketId = $socket->getResourceId();

        if (!isset($this->socketIdUriMap[$socketId])) {
            throw new \Error(
                sprintf('Unknown socket: %d', $socketId)
            );
        }

        $uri = $this->socketIdUriMap[$socketId];
        $socket = $this->sockets[$uri][$socketId];

        if ($socket->idleWatcher) {
            Loop::cancel($socket->idleWatcher);
        }

        unset(
            $this->sockets[$uri][$socketId],
            $this->socketIdUriMap[$socketId]
        );

        if (empty($this->sockets[$uri])) {
            unset($this->sockets[$uri]);
        }
    }

    /** @inheritdoc */
    public function checkin(ClientSocket $socket): void
    {
        $socketId = $socket->getResourceId();

        if (!isset($this->socketIdUriMap[$socketId])) {
            throw new \Error(
                \sprintf('Unknown socket: %d', $socketId)
            );
        }

        $uri = $this->socketIdUriMap[$socketId];

        if (!\is_resource($socket->getResource()) || \feof($socket->getResource())) {
            $this->clear($socket);
            return;
        }

        $socket = $this->sockets[$uri][$socketId];
        $socket->isAvailable = true;

        if (isset($socket->idleWatcher)) {
            Loop::enable($socket->idleWatcher);
        } else {
            $socket->idleWatcher = Loop::delay($this->idleTimeout, function () use ($socket) {
                $this->clear(new ClientSocket($socket->resource));
            });

            Loop::unreference($socket->idleWatcher);
        }
    }
}

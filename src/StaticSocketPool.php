<?php

namespace Amp\Socket;

use Amp\Cancellation\Token;

final class StaticSocketPool implements SocketPool
{
    private $uri;
    private $socketPool;

    public function __construct(string $uri, SocketPool $socketPool = null)
    {
        $this->uri = $uri;
        $this->socketPool = $socketPool ?? new BasicSocketPool;
    }

    /** @inheritdoc */
    public function checkout(string $uri, Token $token = null): ClientSocket
    {
        return $this->socketPool->checkout($this->uri, $token);
    }

    /** @inheritdoc */
    public function checkin(ClientSocket $socket): void
    {
        $this->socketPool->checkin($socket);
    }

    /** @inheritdoc */
    public function clear(ClientSocket $socket): void
    {
        $this->socketPool->clear($socket);
    }
}

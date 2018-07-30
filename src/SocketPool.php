<?php

namespace Amp\Socket;

use Amp\Cancellation\CancelledException;
use Amp\Cancellation\Token;

/**
 * Allows pooling of connections for stateless protocols.
 */
interface SocketPool
{
    /**
     * Checkout a socket from the specified URI authority.
     *
     * The resulting socket resource should be checked back in via `SocketPool::checkin()` once the calling code is
     * finished with the stream (even if the socket has been closed). Failure to checkin sockets will result in memory
     * leaks and socket queue blockage. Instead of checking the socket in again, it can also be cleared.
     *
     * @param string $uri A string of the form tcp://example.com:80 or tcp://192.168.1.1:443.
     * @param Token  $token Optional cancellation token to cancel the checkout request.
     *
     * @return ClientSocket Socket instance once a connection is available.
     *
     * @throws ConnectException
     * @throws CancelledException If the operation is cancelled.
     */
    public function checkout(string $uri, Token $token = null): ClientSocket;

    /**
     * Return a previously checked-out socket to the pool so it can be reused.
     *
     * @param ClientSocket $socket Socket instance.
     *
     * @throws \Error If the provided resource is unknown to the pool.
     */
    public function checkin(ClientSocket $socket): void;

    /**
     * Remove the specified socket from the pool.
     *
     * @param ClientSocket $socket Socket instance.
     *
     * @throws \Error If the provided resource is unknown to the pool.
     */
    public function clear(ClientSocket $socket): void;
}

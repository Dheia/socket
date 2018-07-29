<?php

namespace Amp\Socket;

use Amp\ByteStream\ClosedException;

class ServerSocket extends Socket
{
    /** @inheritdoc */
    public function enableCrypto(): void
    {
        if (($resource = $this->getResource()) === null) {
            throw new ClosedException("The socket has been closed");
        }

        Internal\enableCrypto($resource, [], true);
    }
}

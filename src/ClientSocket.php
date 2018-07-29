<?php

namespace Amp\Socket;

use Amp\ByteStream\ClosedException;

class ClientSocket extends Socket {
    /**
     * {@inheritdoc}
     *
     * @param ClientTlsContext|null $tlsContext
     */
    public function enableCrypto(ClientTlsContext $tlsContext = null): void {
        if (($resource = $this->getResource()) === null) {
            throw new ClosedException("The socket has been closed");
        }

        $tlsContext = $tlsContext ?? new ClientTlsContext;

        Internal\enableCrypto($resource, $tlsContext->toStreamContextArray());
    }
}

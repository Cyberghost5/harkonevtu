<?php

namespace App\Services;

use App\Exceptions\GiftingApiException;
use App\Services\Gifting\Transports\ProxiedGiftingApiTransport;

class GiftingApiProxyService
{
    /**
     * @var ProxiedGiftingApiTransport
     */
    protected ProxiedGiftingApiTransport $transport;

    public function __construct(?ProxiedGiftingApiTransport $transport = null)
    {
        $this->transport = $transport ?? new ProxiedGiftingApiTransport();
    }

    /**
     * Distribute data gifting request specifically via proxy transport.
     *
     * @param array $payload
     * @return array
     * @throws GiftingApiException
     */
    public function distribute(array $payload): array
    {
        return $this->transport->send($payload);
    }
}

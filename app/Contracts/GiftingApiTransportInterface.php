<?php

namespace App\Contracts;

interface GiftingApiTransportInterface
{
    /**
     * Send payload to Glo's Gifting API endpoint via transport strategy.
     *
     * @param array $payload
     * @param array $headers
     * @return array
     * @throws \App\Exceptions\GiftingApiException
     */
    public function send(array $payload, array $headers = []): array;
}

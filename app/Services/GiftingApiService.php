<?php

namespace App\Services;

use App\Contracts\GiftingApiTransportInterface;
use App\Exceptions\GiftingApiException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * This service supports both direct and proxied calls to Glo's gifting-api.
 * Only servers whose outbound IP is not whitelisted by Glo need GIFTING_TRANSPORT=proxy
 * set in their .env — all other servers should leave it as 'direct' (the default).
 */
class GiftingApiService
{
    /**
     * Create a new GiftingApiService instance.
     *
     * @param GiftingApiTransportInterface $transport
     */
    public function __construct(
        protected GiftingApiTransportInterface $transport
    ) {}

    /**
     * Distribute data gifting request using the injected transport strategy.
     *
     * @param array $payload
     * @param array $headers
     * @return array
     * @throws GiftingApiException
     */
    public function distribute(array $payload, array $headers = []): array
    {
        $transportClass = get_class($this->transport);
        $transportName = class_basename($this->transport);
        $startTime = microtime(true);

        try {
            $result = $this->transport->send($payload, $headers);
            $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::channel('gifting-api')->info("Gifting API request succeeded via {$transportName}", [
                'transport' => $transportClass,
                'response_time_ms' => $responseTimeMs,
                'payload' => $payload,
                'response' => $result,
            ]);

            return $result;

        } catch (Throwable $e) {
            $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);
            $originalMessage = $e->getMessage();
            $contextualMessage = "Failed via {$transportName}: {$originalMessage}";

            Log::channel('gifting-api')->error("Gifting API request failed via {$transportName}", [
                'transport' => $transportClass,
                'response_time_ms' => $responseTimeMs,
                'payload' => $payload,
                'error' => $originalMessage,
            ]);

            throw new GiftingApiException($contextualMessage, (int) $e->getCode(), $e);
        }
    }
}

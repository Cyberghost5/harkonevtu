<?php

namespace App\Services\Gifting\Transports;

use App\Contracts\GiftingApiTransportInterface;
use App\Exceptions\GiftingApiException;
use Illuminate\Support\Facades\Http;
use Throwable;

class DirectGiftingApiTransport implements GiftingApiTransportInterface
{
    /**
     * Send gifting request directly to Glo's distribution API.
     *
     * @param array $payload
     * @return array
     * @throws GiftingApiException
     */
    public function send(array $payload): array
    {
        $directUrl = config('gifting.direct_url', 'https://gifting-api.gloworld.com/v1/distribution');
        $timeout = (int) config('gifting.timeout', 30);
        $connectTimeout = (int) config('gifting.connect_timeout', 10);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->post($directUrl, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $decodedResponse = $response->json();

            if ($response->failed()) {
                $errorMessage = is_array($decodedResponse) && isset($decodedResponse['message'])
                    ? $decodedResponse['message']
                    : (is_array($decodedResponse) && isset($decodedResponse['error'])
                        ? (is_string($decodedResponse['error']) ? $decodedResponse['error'] : json_encode($decodedResponse['error']))
                        : "Direct API HTTP failure [Status {$statusCode}]: {$responseBody}");

                throw new GiftingApiException($errorMessage, $statusCode);
            }

            return is_array($decodedResponse) ? $decodedResponse : [];

        } catch (GiftingApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GiftingApiException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}

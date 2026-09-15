<?php

namespace App\Services\Gifting\Transports;

use App\Contracts\GiftingApiTransportInterface;
use App\Exceptions\GiftingApiException;
use Illuminate\Support\Facades\Http;
use Throwable;

class ProxiedGiftingApiTransport implements GiftingApiTransportInterface
{
    /**
     * Send gifting request via secondary proxy server.
     *
     * @param array $payload
     * @param array $headers
     * @return array
     * @throws GiftingApiException
     */
    public function send(array $payload, array $headers = []): array
    {
        $proxyUrl = config('gifting.proxy_url');
        $proxySecret = config('gifting.proxy_secret');
        $timeout = (int) config('gifting.timeout', 30);
        $connectTimeout = (int) config('gifting.connect_timeout', 10);

        if (empty($proxyUrl) || empty($proxySecret)) {
            throw new GiftingApiException('Gifting API proxy configuration is missing or incomplete (GIFTING_PROXY_URL / PROXY_SHARED_SECRET).');
        }

        $mergedHeaders = array_merge($headers, [
            'X-Proxy-Secret' => $proxySecret,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        try {
            $response = Http::withHeaders($mergedHeaders)
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->post($proxyUrl, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $decodedResponse = $response->json();

            if ($response->failed()) {
                $errorMessage = is_array($decodedResponse) && isset($decodedResponse['message'])
                    ? $decodedResponse['message']
                    : (is_array($decodedResponse) && isset($decodedResponse['error'])
                        ? (is_string($decodedResponse['error']) ? $decodedResponse['error'] : json_encode($decodedResponse['error']))
                        : "Proxy HTTP failure [Status {$statusCode}]: {$responseBody}");

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

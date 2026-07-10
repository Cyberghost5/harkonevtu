<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\MtnErsSequence;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MtnErsSoapService
{
    protected string $endpoint;
    protected ?string $username;
    protected ?string $pin;
    protected ?string $originatorMsisdn;
    protected string $mode;

    public function __construct()
    {
        $this->endpoint = AppSetting::get('mtn_ers_endpoint', 'https://ers.seamless.se/services/ERSExchange3GPort');
        $this->username = AppSetting::get('mtn_ers_username');
        $this->pin = AppSetting::get('mtn_ers_pin');
        $this->originatorMsisdn = AppSetting::get('mtn_ers_originator_msisdn');
        $this->mode = AppSetting::get('mtn_ers_mode', 'sandbox');
    }

    /**
     * Checks if active credentials are set.
     */
    public function isConfigured(): bool
    {
        return !empty($this->username) && !empty($this->pin);
    }

    /**
     * Get the configured originator MSISDN or fallback to default mock number.
     */
    public function getOriginatorMsisdn(): string
    {
        return $this->originatorMsisdn ?: '09062058470';
    }

    /**
     * Parse SOAP XML response, stripping namespaces for clean array parsing.
     */
    public function parseResponse(string $xmlString): array
    {
        try {
            // Strip XML namespaces
            $cleanXml = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$3', $xmlString);
            $xml = new \SimpleXMLElement($cleanXml);

            $body = $xml->Body;
            if (!$body) {
                return ['status' => false, 'message' => 'Invalid SOAP envelope response.'];
            }

            $responseNode = null;
            if (isset($body->vendResponse)) {
                $responseNode = $body->vendResponse;
            } elseif (isset($body->queryTxResponse)) {
                $responseNode = $body->queryTxResponse;
            } elseif (isset($body->lookupResponse)) {
                $responseNode = $body->lookupResponse;
            }

            if (!$responseNode) {
                return ['status' => false, 'message' => 'No matching response elements found inside body.'];
            }

            $data = [];
            foreach ($responseNode->children() as $child) {
                $data[$child->getName()] = (string) $child;
            }

            return [
                'status' => true,
                'data'   => $data
            ];
        } catch (\Exception $e) {
            Log::error('MTN ERS XML Parsing Error', ['message' => $e->getMessage(), 'xml' => $xmlString]);
            return [
                'status' => false,
                'message' => 'XML parsing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build SOAP Vend request payload.
     */
    public function buildVendXml(string $origMsisdn, string $destMsisdn, float $amount, int $sequence, int $tariffTypeId): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://hostif.vtm.prism.co.za/xsd">
 <soapenv:Header/>
 <soapenv:Body>
   <xsd:vend>
     <xsd:origMsisdn>' . htmlspecialchars($origMsisdn) . '</xsd:origMsisdn>
     <xsd:destMsisdn>' . htmlspecialchars($destMsisdn) . '</xsd:destMsisdn>
     <xsd:amount>' . htmlspecialchars($amount) . '</xsd:amount>
     <xsd:sequence>' . htmlspecialchars($sequence) . '</xsd:sequence>
     <xsd:tariffTypeId>' . htmlspecialchars($tariffTypeId) . '</xsd:tariffTypeId>
     <xsd:serviceproviderId>1</xsd:serviceproviderId>
   </xsd:vend>
 </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * Build SOAP QueryTx request payload.
     */
    public function buildQueryTxXml(int $sequence): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://hostif.vtm.prism.co.za/xsd">
 <soapenv:Header/>
 <soapenv:Body>
   <xsd:querytx>
     <xsd:sequence>' . htmlspecialchars($sequence) . '</xsd:sequence>
   </xsd:querytx>
 </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * Dispatch SOAP request. Supports sandbox mock modes and auto-retry sequence sync recovery.
     */
    public function sendRequest(string $soapAction, string $xmlPayload, int $sequenceAttempt, string $originatorMsisdn, callable $retryAction = null): array
    {
        if ($this->mode === 'sandbox' || !$this->isConfigured()) {
            return $this->handleSandboxMock($soapAction, $xmlPayload, $sequenceAttempt, $originatorMsisdn);
        }

        try {
            $basicAuth = base64_encode("{$this->username}:{$this->pin}");

            $response = Http::withHeaders([
                'Authorization' => "Basic {$basicAuth}",
                'Content-Type'  => 'application/xml; charset=utf-8',
                'SoapAction'    => $soapAction,
            ])->send('POST', $this->endpoint, [
                'body' => $xmlPayload
            ]);

            if ($response->successful()) {
                $parsed = $this->parseResponse($response->body());
                if (!$parsed['status']) {
                    return $parsed;
                }

                $data = $parsed['data'];
                $responseCode = (int) ($data['responseCode'] ?? -1);

                // Handle out-of-sync sequence (Code 106) with auto-retry
                if ($responseCode === 106 && $retryAction) {
                    $lastSeq = (int) ($data['lastseq'] ?? $data['sequence'] ?? 0);
                    if ($lastSeq > 0) {
                        MtnErsSequence::setNextSequence($originatorMsisdn, $lastSeq + 2);
                        Log::info("MTN ERS sequence auto-synced to " . ($lastSeq + 2));
                        return $retryAction($lastSeq + 1); // retry with new sequence
                    }
                }

                return [
                    'status' => $responseCode === 0,
                    'message' => $data['responseMessage'] ?? 'Success',
                    'data' => $data
                ];
            }

            Log::error('MTN ERS SOAP HTTP Request Failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'status' => false,
                'message' => 'MTN ERS Gateway error (HTTP ' . $response->status() . ').'
            ];

        } catch (\Exception $e) {
            Log::error('MTN ERS SOAP Exception', ['message' => $e->getMessage()]);
            return [
                'status' => false,
                'message' => 'MTN ERS Gateway connection timeout or error.'
            ];
        }
    }

    /**
     * Helper to format MSISDN to standard 11 digits starting with 0.
     */
    public function formatMsisdn(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleaned, '234') && strlen($cleaned) === 13) {
            return '0' . substr($cleaned, 3);
        }
        return $cleaned;
    }

    /**
     * Executes airtime/data/voucher disbursements using automatic sequence tracking.
     */
    public function vend(string $destMsisdn, float $amount, int $tariffTypeId): array
    {
        $originator = $this->formatMsisdn($this->originatorMsisdn ?: '09062058470');
        $target = $this->formatMsisdn($destMsisdn);

        $execute = function (int $seq) use ($originator, $target, $amount, $tariffTypeId, &$execute) {
            $xml = $this->buildVendXml($originator, $target, $amount, $seq, $tariffTypeId);
            return $this->sendRequest(
                'urn:Vend', 
                $xml, 
                $seq, 
                $originator, 
                function ($nextSeq) use ($execute) {
                    return $execute($nextSeq);
                }
            );
        };

        $sequence = MtnErsSequence::getAndIncrement($originator);
        return $execute($sequence);
    }

    /**
     * Queries status of a specific transaction sequence.
     */
    public function queryTx(int $sequence): array
    {
        $xml = $this->buildQueryTxXml($sequence);
        $originator = $this->formatMsisdn($this->originatorMsisdn ?: '09062058470');
        return $this->sendRequest('urn:QyeryTx', $xml, $sequence, $originator);
    }

    /**
     * Sandbox mock simulation matching MTN ERS specifications.
     */
    protected function handleSandboxMock(string $soapAction, string $xmlPayload, int $sequence, string $originator): array
    {
        // Parse input numbers if possible
        $destMsisdn = '09062058617';
        if (preg_match('/<xsd:destMsisdn>(.*?)<\/xsd:destMsisdn>/', $xmlPayload, $matches)) {
            $destMsisdn = $matches[1];
        }

        $tariffTypeId = 1;
        if (preg_match('/<xsd:tariffTypeId>(.*?)<\/xsd:tariffTypeId>/', $xmlPayload, $matches)) {
            $tariffTypeId = (int) $matches[1];
        }

        if ($soapAction === 'urn:Vend') {
            // Check for mock failure triggers
            if (str_contains($destMsisdn, '9999')) { // Trigger mock error
                $mockResponse = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
 <soapenv:Body>
   <vendResponse>
     <destBalance>0.0</destBalance>
     <origBalance>0.0</origBalance>
     <responseCode>301</responseCode>
     <responseMessage>Insufficient Airtime</responseMessage>
     <sequence>' . $sequence . '</sequence>
     <statusId>540</statusId>
     <txRefId>ERS-MOCK-FAIL-' . uniqid() . '</txRefId>
   </vendResponse>
 </soapenv:Body>
</soapenv:Envelope>';
            } else { // Trigger mock success
                $voucherXml = '';
                if ($tariffTypeId === 7) {
                    $voucherXml = "\n     <voucherPIN>40692125281574</voucherPIN>\n     <voucherSerial>600000000001</voucherSerial>";
                }
                $mockResponse = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
 <soapenv:Body>
   <vendResponse>
     <destBalance>5000.0</destBalance>
     <destMsisdn>' . $destMsisdn . '</destMsisdn>
     <origBalance>95000.0</origBalance>
     <origMsisdn>' . $originator . '</origMsisdn>
     <responseCode>0</responseCode>
     <responseMessage>Successful</responseMessage>
     <sequence>' . $sequence . '</sequence>
     <statusId>0</statusId>
     <txRefId>ERS-MOCK-SUCCESS-' . uniqid() . '</txRefId>' . $voucherXml . '
   </vendResponse>
 </soapenv:Body>
</soapenv:Envelope>';
            }
        } elseif ($soapAction === 'urn:QyeryTx') {
            $mockResponse = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
 <soapenv:Body>
   <queryTxResponse>
     <message>SUCCESSFUL</message>
     <statusId>0</statusId>
   </queryTxResponse>
 </soapenv:Body>
</soapenv:Envelope>';
        } else {
            return [
                'status' => false,
                'message' => 'Unsupported mock SoapAction.'
            ];
        }

        $parsed = $this->parseResponse($mockResponse);
        if (!$parsed['status']) {
            return $parsed;
        }

        $data = $parsed['data'];
        return [
            'status' => ((int)$data['responseCode']) === 0,
            'message' => $data['responseMessage'] ?? 'Success',
            'data' => $data
        ];
    }
}

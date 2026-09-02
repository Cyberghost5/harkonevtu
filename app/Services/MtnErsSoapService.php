<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ApiLog;
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
     * Format MSISDN to start with 0 (expected for destination subscriber destMsisdn).
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
     * Format MSISDN to start with 234 (expected for originator/merchant origMsisdn).
     */
    public function formatMsisdn234(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 11) {
            return '234' . substr($cleaned, 1);
        }
        return $cleaned;
    }

    /**
     * Get the configured originator MSISDN.
     */
    public function getOriginatorMsisdn(): string
    {
        return $this->originatorMsisdn ?: '2349062058470';
    }

    /**
     * Parse SOAP XML response, stripping namespaces for clean array parsing.
     */
    public function parseResponse(string $xmlString): array
    {
        try {
            $trimXml = trim($xmlString);
            if (empty($trimXml)) {
                return ['status' => false, 'message' => 'Empty response received from MTN ERS server.'];
            }

            // Strip XML declarations and clean namespaces
            $cleanXml = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$3', $trimXml);
            
            // Try standard SimpleXML parsing
            $xml = @simplexml_load_string($cleanXml);

            // Fallback parsing via DOMDocument if simplexml failed (handles un-prefixed/malformed SOAP nodes)
            if (!$xml) {
                $doc = new \DOMDocument();
                @$doc->loadXML($trimXml, LIBXML_NOERROR | LIBXML_NOWARNING);
                if ($doc->documentElement) {
                    $cleanXml = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$3', $doc->saveXML());
                    $xml = @simplexml_load_string($cleanXml);
                }
            }

            // Fallback 2: Direct Regex Extraction for key fields if XML parser was strictly blocked
            if (!$xml) {
                $data = [];
                $fields = ['responseCode', 'responseMessage', 'sequence', 'statusId', 'txRefId', 'destBalance', 'origBalance', 'voucherPIN', 'voucherSerial'];
                foreach ($fields as $field) {
                    if (preg_match("/<.*?:?{$field}>(.*?)<\/.*?:?{$field}>/i", $trimXml, $m)) {
                        $data[$field] = trim($m[1]);
                    }
                }
                if (isset($data['responseCode']) || isset($data['txRefId'])) {
                    return [
                        'status' => true,
                        'data'   => $data
                    ];
                }
                return ['status' => false, 'message' => 'Malformed XML response: ' . substr(strip_tags($trimXml), 0, 200)];
            }

            // Determine if wrapped in Body or root directly
            $root = isset($xml->Body) ? $xml->Body : $xml;

            $nodeNames = ['vendResponse', 'queryTxResponse', 'LookupResponse', 'lookupResponse', 'transferResponse'];
            $responseNode = null;

            // Check if root itself is one of the response nodes
            if (in_array($root->getName(), $nodeNames, true)) {
                $responseNode = $root;
            } else {
                foreach ($nodeNames as $name) {
                    if (isset($root->$name)) {
                        $responseNode = $root->$name;
                        break;
                    }
                }
            }

            if (!$responseNode) {
                if (isset($root->Fault)) {
                    $faultString = (string) ($root->Fault->faultstring ?? 'SOAP Fault occurred.');
                    return ['status' => false, 'message' => $faultString];
                }

                // If node was not matched in array, parse all child tags of root
                $data = [];
                foreach ($root->children() as $child) {
                    $data[$child->getName()] = (string) $child;
                }

                if (!empty($data)) {
                    return [
                        'status' => true,
                        'data'   => $data
                    ];
                }

                return ['status' => false, 'message' => 'Unrecognized ERS response node: ' . $root->getName() . '. Response snippet: ' . substr(strip_tags($trimXml), 0, 200)];
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
     * Build SOAP Vend request XML matching the ERS 360 HOSTIF API document.
     */
    public function buildVendXml(string $origMsisdn, string $destMsisdn, float $amount, int $sequence, $tariffTypeId): string
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
     * Build SOAP QueryTx request XML matching the ERS 360 HOSTIF API document.
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

        $start = hrtime(true);
        $httpStatus = null;
        $responseHeaders = null;
        $data = [];
        $success = false;

        try {
            $basicAuth = base64_encode("{$this->username}:{$this->pin}");

            $caBundle = storage_path('certs/ca-bundle.pem');
            $host = parse_url($this->endpoint, PHP_URL_HOST);
            $isIpAddress = filter_var($host, FILTER_VALIDATE_IP) !== false;

            // Disable SSL verification for raw IP endpoints or missing/unreadable cert files to prevent cURL error 77
            $verifyOption = (!$isIpAddress && file_exists($caBundle) && is_readable($caBundle)) ? $caBundle : false;

            $response = Http::withHeaders([
                'Authorization' => "Basic {$basicAuth}",
                'Content-Type'  => 'text/xml; charset=utf-8',
                'SoapAction'    => $soapAction,
                'Expect'        => '',
            ])->withOptions([
                'verify'      => $verifyOption,
                'version'     => 1.0,
                'http_errors' => false,
            ])->connectTimeout(0)
              ->timeout(30)
              ->withBody($xmlPayload, 'text/xml; charset=utf-8')
              ->post($this->endpoint);

            $httpStatus = $response->status();
            $responseHeaders = $response->headers();

            if ($response->successful()) {
                $parsed = $this->parseResponse($response->body());
                if ($parsed['status']) {
                    $data = $parsed['data'];
                    $responseCode = (int) ($data['responseCode'] ?? -1);
                    $statusId = (int) ($data['statusId'] ?? 0);

                    // Handle out-of-sync sequence (Code 106 or statusId 106) with auto-retry
                    if (($responseCode === 106 || $statusId === 106) && $retryAction) {
                        $lastSeq = (int) ($data['lastseq'] ?? $data['sequence'] ?? 0);
                        if ($lastSeq > 0) {
                            MtnErsSequence::setNextSequence($originatorMsisdn, $lastSeq + 2);
                            Log::info("MTN ERS sequence auto-synced to " . ($lastSeq + 2));
                            return $retryAction($lastSeq + 1); // retry with new sequence
                        }
                    }

                    $respMsg = strtolower($data['responseMessage'] ?? '');
                    $hasErrorKeyword = str_contains($respMsg, 'invalid') || str_contains($respMsg, 'failed') || str_contains($respMsg, 'insufficient') || str_contains($respMsg, 'error');

                    $success = ($responseCode === 0 && ($statusId === 0 || !isset($data['statusId'])) && !$hasErrorKeyword);

                    if (!$success) {
                        $data['message'] = $data['responseMessage'] ?? ('MTN ERS failure statusId: ' . $statusId);
                    }
                } else {
                    $data = ['message' => $parsed['message']];
                }
            } else {
                $data = ['message' => 'MTN SOAP HTTP transaction failed with status ' . $response->status()];
            }

        } catch (\Exception $e) {
            $data = ['message' => 'Connection to MTN ERS timeout or failed: ' . $e->getMessage()];
            Log::error('MTN ERS SOAP Request Exception', ['sequence' => $sequenceAttempt, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            $service = 'airtime';
            if (preg_match('/<xsd:tariffTypeId>(.*?)<\/xsd:tariffTypeId>/i', $xmlPayload, $m)) {
                $tId = trim($m[1]);
                if ($tId === '7') {
                    $service = 'voucher';
                } elseif ($tId !== '1') {
                    $service = 'data';
                }
            }

            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => $service,
                'provider'         => 'mtn_ers',
                'reference'        => $reference ?? 'SEQ-' . $sequenceAttempt,
                'endpoint'         => $this->endpoint,
                'method'           => 'POST',
                'payload'          => ['xml' => $xmlPayload],
                'request_headers'  => ['Content-Type' => 'text/xml', 'SOAPAction' => $soapAction],
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return [
            'status'  => $success,
            'message' => $data['message'] ?? $data['responseMessage'] ?? 'Success',
            'data'    => $data
        ];
    }

    /**
     * Executes airtime/data/voucher disbursements using automatic sequence tracking.
     */
    public function vend(string $destMsisdn, float $amount, $productId): array
    {
        $originator = $this->formatMsisdn234($this->originatorMsisdn ?: '09062058470');
        $target = $this->formatMsisdn234($destMsisdn);

        // Map product to correct tariffTypeId
        $tariffTypeId = 1; // Airtime default
        if ($productId === 7 || $productId === '7') {
            $tariffTypeId = 7; // Voucher
        } elseif ($productId === 1 || $productId === '1') {
            $tariffTypeId = 1; // Airtime
        } else {
            $tariffTypeId = $productId; // Data Bundle Tariff ID
        }

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
     * Helper for airtime vends matching vendor signature.
     */
    public function vendAirtime(string $phone, float $amount, ?string $reference = null): array
    {
        $res = $this->vend($phone, $amount, 1);
        return [
            'success'        => $res['status'] ?? false,
            'transaction_id' => $res['data']['txRefId'] ?? $reference,
            'response'       => $res,
        ];
    }

    /**
     * Helper for data vends matching vendor signature.
     */
    public function vendData(string $phone, $planId, ?string $reference = null): array
    {
        $res = $this->vend($phone, 0, $planId);
        return [
            'success'        => $res['status'] ?? false,
            'transaction_id' => $res['data']['txRefId'] ?? $reference,
            'response'       => $res,
        ];
    }

    /**
     * Queries status of a specific transaction sequence.
     */
    public function queryTx(int $sequence): array
    {
        $xml = $this->buildQueryTxXml($sequence);
        $originator = $this->formatMsisdn234($this->originatorMsisdn ?: '09062058470');
        return $this->sendRequest('urn:QyeryTx', $xml, $sequence, $originator);
    }

    /**
     * Sandbox mock simulation matching Seamless ERS HOSTIF specifications.
     */
    protected function handleSandboxMock(string $soapAction, string $xmlPayload, int $sequence, string $originator): array
    {
        // Parse receiver/dest msisdn
        $destMsisdn = '09062058617';
        if (preg_match('/<xsd:destMsisdn>(.*?)<\/xsd:destMsisdn>/', $xmlPayload, $matches)) {
            $destMsisdn = $matches[1];
        }

        $tariffTypeId = 1;
        if (preg_match('/<xsd:tariffTypeId>(.*?)<\/xsd:tariffTypeId>/', $xmlPayload, $matches)) {
            $tariffTypeId = (int) $matches[1];
        }

        $amount = 100.0;
        if (preg_match('/<xsd:amount>(.*?)<\/xsd:amount>/', $xmlPayload, $matches)) {
            $amount = (float) $matches[1];
        }

        $success = !str_contains($destMsisdn, '9999');

        if ($success) {
            $voucherXml = '';
            if ($tariffTypeId === 7) {
                $voucherXml = "\n     <voucherPIN>" . str_pad((string) random_int(100000000000000, 999999999999999), 15, '0', STR_PAD_LEFT) . "</voucherPIN>\n     <voucherSerial>" . str_pad((string) random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT) . "</voucherSerial>";
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
        } else {
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
        }

        $parsed = $this->parseResponse($mockResponse);
        if (!$parsed['status']) {
            return $parsed;
        }

        $data = $parsed['data'];
        return [
            'status'  => ((int)$data['responseCode']) === 0,
            'message' => $data['responseMessage'] ?? 'Success',
            'data'    => $data
        ];
    }
}

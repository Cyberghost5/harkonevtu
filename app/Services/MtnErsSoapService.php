<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ApiLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
     * Format MSISDN to standard international format starting with 234.
     */
    public function formatMsisdn(string $phone): string
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
            $cleanXml = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$3', $xmlString);
            $xml = new \SimpleXMLElement($cleanXml);

            $body = $xml->Body;
            if (!$body) {
                return ['status' => false, 'message' => 'Invalid SOAP envelope response.'];
            }

            $responseNode = null;
            if (isset($body->requestTopupResponse)) {
                $responseNode = $body->requestTopupResponse;
            } elseif (isset($body->requestPurchaseResponse)) {
                $responseNode = $body->requestPurchaseResponse;
            }

            if (!$responseNode) {
                if (isset($body->Fault)) {
                    $faultString = (string) ($body->Fault->faultstring ?? 'SOAP Fault occurred.');
                    return ['status' => false, 'message' => $faultString];
                }
                return ['status' => false, 'message' => 'No matching response elements found inside body.'];
            }

            $returnNode = $responseNode->return ?? null;
            if (!$returnNode) {
                return ['status' => false, 'message' => 'Response structure is missing <return> element.'];
            }

            $data = [];
            foreach ($returnNode->children() as $child) {
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
     * Build SOAP requestTopup request XML.
     */
    public function buildTopupXml(string $reference, string $destMsisdn, float $amount, string $productId, string $accountTypeId): string
    {
        $properties = '';
        if ($accountTypeId === 'DATA_BUNDLE') {
            $properties = '
       <transactionProperties>
         <entry>
           <key>TRANSACTION_TYPE</key>
           <value>PRODUCT_RECHARGE</value>
         </entry>
       </transactionProperties>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ext="http://external.interfaces.ers.seamless.com/">
 <soapenv:Header/>
 <soapenv:Body>
   <ext:requestTopup>
     <context>
       <channel>WSClient</channel>
       <clientComment>Topup via PayPulse</clientComment>
       <clientId>ERS</clientId>
       <clientReference>' . htmlspecialchars($reference) . '</clientReference>
       <clientRequestTimeout>30000</clientRequestTimeout>
       <initiatorPrincipalId>
         <id>' . htmlspecialchars($this->username) . '</id>
         <type>RESELLERUSER</type>
         <userId>9900</userId>
       </initiatorPrincipalId>
       <password>' . htmlspecialchars($this->pin) . '</password>' . $properties . '
     </context>
     <senderPrincipalId>
       <id>' . htmlspecialchars($this->username) . '</id>
       <type>RESELLERUSER</type>
       <userId>9900</userId>
     </senderPrincipalId>
     <topupPrincipalId>
       <id>' . htmlspecialchars($destMsisdn) . '</id>
       <type>SUBSCRIBERMSISDN</type>
     </topupPrincipalId>
     <senderAccountSpecifier>
       <accountId>' . htmlspecialchars($this->username) . '</accountId>
       <accountTypeId>RESELLER</accountTypeId>
     </senderAccountSpecifier>
     <topupAccountSpecifier>
       <accountId>' . htmlspecialchars($destMsisdn) . '</accountId>
       <accountTypeId>' . htmlspecialchars($accountTypeId) . '</accountTypeId>
     </topupAccountSpecifier>
     <productId>' . htmlspecialchars($productId) . '</productId>
     <amount>
       <currency>NGN</currency>
       <value>' . htmlspecialchars($amount) . '</value>
     </amount>
   </ext:requestTopup>
 </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * Build SOAP requestPurchase request XML (For Voucher Printing).
     */
    public function buildPurchaseXml(string $reference, string $receiverMsisdn, float $amount, string $productSku): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ext="http://external.interfaces.ers.seamless.com/">
 <soapenv:Header/>
 <soapenv:Body>
   <ext:requestPurchase>
     <context>
       <channel>WSClient</channel>
       <prepareOnly>false</prepareOnly>
       <clientReference>' . htmlspecialchars($reference) . '</clientReference>
       <clientRequestTimeout>30000</clientRequestTimeout>
       <initiatorPrincipalId>
         <id>' . htmlspecialchars($this->username) . '</id>
         <type>RESELLERUSER</type>
         <userId>9900</userId>
       </initiatorPrincipalId>
       <password>' . htmlspecialchars($this->pin) . '</password>
       <transactionProperties>
         <entry>
           <key>preferredLanguage</key>
           <value>en</value>
         </entry>
         <entry>
           <key>productSKU</key>
           <value>' . htmlspecialchars($productSku) . '</value>
         </entry>
         <entry>
           <key>currency</key>
           <value>NGN</value>
         </entry>
         <entry>
           <key>purchaseAmount</key>
           <value>' . htmlspecialchars($amount) . '</value>
         </entry>
       </transactionProperties>
     </context>
     <senderPrincipalId>
       <id>' . htmlspecialchars($this->username) . '</id>
       <type>RESELLERUSER</type>
       <userId>9900</userId>
     </senderPrincipalId>
     <receiverPrincipalId>
       <id>' . htmlspecialchars($receiverMsisdn) . '</id>
       <type>SUBSCRIBERMSISDN</type>
     </receiverPrincipalId>
     <senderAccountSpecifier>
       <accountTypeId>RESELLER</accountTypeId>
     </senderAccountSpecifier>
     <purchaseOrder>
       <productSpecifier>
         <productId>' . htmlspecialchars($productSku) . '</productId>
         <productIdType>VOD</productIdType>
       </productSpecifier>
       <purchaseCount>1</purchaseCount>
     </purchaseOrder>
   </ext:requestPurchase>
 </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * Dispatch SOAP request. Supports sandbox mock modes.
     */
    public function sendRequest(string $soapAction, string $xmlPayload, string $reference): array
    {
        if ($this->mode === 'sandbox' || !$this->isConfigured()) {
            return $this->handleSandboxMock($soapAction, $xmlPayload, $reference);
        }

        $start = hrtime(true);
        $httpStatus = null;
        $responseHeaders = null;
        $data = [];
        $success = false;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction'   => $soapAction,
            ])->timeout(30)->send('POST', $this->endpoint, [
                'body' => $xmlPayload
            ]);

            $httpStatus = $response->status();
            $responseHeaders = $response->headers();

            $parsed = $this->parseResponse($response->body());

            if ($parsed['status']) {
                $data = $parsed['data'];
                $resultCode = (int) ($data['resultCode'] ?? -1);
                $success = ($resultCode === 0);
                if (!$success) {
                    $data['message'] = $data['resultDescription'] ?? 'MTN ERS returned failure code: ' . $resultCode;
                }
            } else {
                $data = ['message' => $parsed['message'] ?? 'MTN SOAP HTTP transaction failed with status ' . $response->status()];
            }

        } catch (\Exception $e) {
            $data = ['message' => 'Connection to MTN ERS timeout or failed: ' . $e->getMessage()];
            Log::error('MTN ERS SOAP Request Exception', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            $service = 'airtime';
            if (str_contains($xmlPayload, 'DATA_BUNDLE')) {
                $service = 'data';
            } elseif (str_contains($xmlPayload, 'requestPurchase')) {
                $service = 'voucher';
            }

            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => $service,
                'provider'         => 'mtn_ers',
                'reference'        => $reference,
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

        // Set txRefId so controller checks succeed
        $data['txRefId'] = $data['ersReference'] ?? null;

        // Parse voucher details if successful voucher purchase
        if ($success && $service === 'voucher' && isset($data['resultDescription'])) {
            $desc = $data['resultDescription'];
            if (preg_match('/Pin:\s*([0-9]+)/i', $desc, $matches)) {
                $data['voucherPIN'] = $matches[1];
            }
            if (preg_match('/Serial:\s*([0-9]+)/i', $desc, $matches)) {
                $data['voucherSerial'] = $matches[1];
            }
        }

        return [
            'status'  => $success,
            'message' => $data['message'] ?? $data['resultDescription'] ?? 'Success',
            'data'    => $data
        ];
    }

    /**
     * Executes airtime/data/voucher disbursements using automatic sequence tracking.
     */
    public function vend(string $destMsisdn, float $amount, $productId): array
    {
        $target = $this->formatMsisdn($destMsisdn);
        $reference = 'MTN' . date('YmdHis') . strtoupper(Str::random(8));

        // Map request types based on $productId
        if ($productId === 7 || $productId === '7') {
            // Voucher
            $xml = $this->buildPurchaseXml($reference, $target, $amount, 'VOT');
        } elseif ($productId === 1 || $productId === '1') {
            // Airtime
            $xml = $this->buildTopupXml($reference, $target, $amount, 'TOPUP', 'AIRTIME');
        } else {
            // Data SKU
            $xml = $this->buildTopupXml($reference, $target, $amount, $productId, 'DATA_BUNDLE');
        }

        return $this->sendRequest('', $xml, $reference);
    }

    /**
     * Sandbox mock simulation matching Seamless ERS specifications.
     */
    protected function handleSandboxMock(string $soapAction, string $xmlPayload, string $reference): array
    {
        // Parse receiver/dest msisdn
        $destMsisdn = '2349062058617';
        if (preg_match('/<id>(.*?)<\/id>/', $xmlPayload, $matches)) {
            $destMsisdn = $matches[count($matches) - 1];
        }

        $service = 'airtime';
        if (str_contains($xmlPayload, 'DATA_BUNDLE')) {
            $service = 'data';
        } elseif (str_contains($xmlPayload, 'requestPurchase')) {
            $service = 'voucher';
        }

        $success = !str_contains($destMsisdn, '9999');

        if ($success) {
            $resultDescription = 'SUCCESS';
            if ($service === 'voucher') {
                $pin = str_pad((string) random_int(100000000000000, 999999999999999), 15, '0', STR_PAD_LEFT);
                $serial = str_pad((string) random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
                $resultDescription = "You have sold one voucher:- Amount: {$amount} NGN Expiry Date: 2028-12-31 Ref.:{$reference} Balance: 95000 NGN. Pin: {$pin} Serial:{$serial}";
            }
            $data = [
                'resultCode'        => '0',
                'resultDescription' => $resultDescription,
                'ersReference'      => 'MTN-ERS-' . uniqid(),
                'txRefId'           => 'MTN-ERS-' . uniqid(),
            ];
        } else {
            $data = [
                'resultCode'        => '37',
                'resultDescription' => 'INITIATOR_PRINCIPAL_NOT_FOUND',
                'message'           => 'INITIATOR_PRINCIPAL_NOT_FOUND',
            ];
        }

        // Parse voucher details if successful voucher purchase
        if ($success && $service === 'voucher' && isset($data['resultDescription'])) {
            $desc = $data['resultDescription'];
            if (preg_match('/Pin:\s*([0-9]+)/i', $desc, $matches)) {
                $data['voucherPIN'] = $matches[1];
            }
            if (preg_match('/Serial:\s*([0-9]+)/i', $desc, $matches)) {
                $data['voucherSerial'] = $matches[1];
            }
        }

        return [
            'status'  => $success,
            'message' => $data['message'] ?? $data['resultDescription'] ?? 'Success',
            'data'    => $data
        ];
    }
}

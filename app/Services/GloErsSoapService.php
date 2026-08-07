<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ApiLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GloErsSoapService
{
    protected string $endpoint;
    protected ?string $username;
    protected ?string $password;
    protected string $clientId;
    protected ?string $distributorId;
    protected ?string $distributorUserId;
    protected string $mode;

    public function __construct()
    {
        $this->endpoint = AppSetting::get('glo_ers_endpoint', 'http://10.10.3.42:8914/topupservice/service');
        $this->username = AppSetting::get('glo_ers_username');
        $this->password = AppSetting::get('glo_ers_password');
        $this->clientId = AppSetting::get('glo_ers_client_id', 'ERS');
        $this->distributorId = AppSetting::get('glo_ers_distributor_id');
        $this->distributorUserId = AppSetting::get('glo_ers_distributor_userid');
        $this->mode = AppSetting::get('glo_ers_mode', 'sandbox');
    }

    /**
     * Checks if credentials are set.
     */
    public function isConfigured(): bool
    {
        return !empty($this->username) && !empty($this->password) && !empty($this->distributorId);
    }

    /**
     * Format recipient phone number.
     * Glo typically expects standard MSISDN without country code or with depending on account.
     * The example in doc shows "2342348010101" or subscriber MSISDN formats.
     */
    public function formatMsisdn(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        // If it starts with +234 or 234, keep it, else prefix 234 if starts with 0
        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 11) {
            return '234' . substr($cleaned, 1);
        }
        return $cleaned;
    }

    /**
     * Parse SOAP Envelope, stripping namespaces for PHP simplexml extraction.
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
                // Check if response contains direct soap fault
                if (isset($body->Fault)) {
                    $faultString = (string) ($body->Fault->faultstring ?? 'SOAP Fault occurred.');
                    return ['status' => false, 'message' => $faultString];
                }
                return ['status' => false, 'message' => 'No matching response elements inside envelope body.'];
            }

            // Extract return object
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
            Log::error('Glo ERS XML Parsing Error', ['message' => $e->getMessage(), 'xml' => $xmlString]);
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
        if ($accountTypeId === 'DATA_BUNDLE' || $accountTypeId === 'VOICE_BUNDLE') {
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
       <clientComment>Topup via New Millennium</clientComment>
       <clientId>' . htmlspecialchars($this->clientId) . '</clientId>
       <clientReference>' . htmlspecialchars($reference) . '</clientReference>
       <clientRequestTimeout>30000</clientRequestTimeout>
       <initiatorPrincipalId>
         <id>' . htmlspecialchars($this->distributorId) . '</id>
         <type>RESELLERUSER</type>
         <userId>' . htmlspecialchars($this->distributorUserId ?: '9900') . '</userId>
       </initiatorPrincipalId>
       <password>' . htmlspecialchars($this->password) . '</password>' . $properties . '
     </context>
     <senderPrincipalId>
       <id>' . htmlspecialchars($this->distributorId) . '</id>
       <type>RESELLERUSER</type>
       <userId>' . htmlspecialchars($this->distributorUserId ?: '9900') . '</userId>
     </senderPrincipalId>
     <topupPrincipalId>
       <id>' . htmlspecialchars($destMsisdn) . '</id>
       <type>SUBSCRIBERMSISDN</type>
     </topupPrincipalId>
     <senderAccountSpecifier>
       <accountId>' . htmlspecialchars($this->distributorId) . '</accountId>
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
     * Build SOAP requestPurchase request XML (For Option 3: Voucher Printing).
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
         <id>' . htmlspecialchars($this->distributorId) . '</id>
         <type>RESELLERUSER</type>
         <userId>' . htmlspecialchars($this->distributorUserId ?: '9900') . '</userId>
       </initiatorPrincipalId>
       <password>' . htmlspecialchars($this->password) . '</password>
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
       <id>' . htmlspecialchars($this->distributorId) . '</id>
       <type>RESELLERUSER</type>
       <userId>' . htmlspecialchars($this->distributorUserId ?: '9900') . '</userId>
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
     * Dispatch SOAP request over HTTP client.
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

            // Try to parse the SOAP response body first, as SOAP Faults always return HTTP status 500
            $parsed = $this->parseResponse($response->body());

            if ($parsed['status']) {
                $data = $parsed['data'];
                $resultCode = (int) ($data['resultCode'] ?? -1);
                $success = ($resultCode === 0);
                if (!$success) {
                    $data['message'] = $data['resultDescription'] ?? 'Glo ERS returned failure code: ' . $resultCode;
                }
            } else {
                // If XML parsing failed, check if it was a SOAP Fault message
                $data = ['message' => $parsed['message'] ?? 'Glo SOAP HTTP transaction failed with status ' . $response->status()];
            }
        } catch (\Exception $e) {
            $data = ['message' => 'Connection to Glo ERS timeout or failed: ' . $e->getMessage()];
            Log::error('Glo ERS SOAP Request Exception', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => str_contains($soapAction, 'Purchase') ? 'voucher' : 'airtime',
                'provider'         => 'glo_ers',
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

        return ['success' => $success, 'reference' => $data['ersReference'] ?? $reference, 'response' => $data];
    }

    /**
     * Dispatch Airtime Purchase (Option 1).
     */
    public function vendAirtime(string $phone, float $amount, string $reference): array
    {
        $target = $this->formatMsisdn($phone);
        // productId is typically TOPUP for airtime topups, and accountTypeId is AIRTIME
        $xml = $this->buildTopupXml($reference, $target, $amount, 'TOPUP', 'AIRTIME');
        return $this->sendRequest('urn:requestTopup', $xml, $reference);
    }

    /**
     * Dispatch Data Bundle Subscription (Option 2).
     */
    public function vendData(string $phone, string $productId, string $reference): array
    {
        $target = $this->formatMsisdn($phone);
        // productId is the Glo product SKU, and accountTypeId is DATA_BUNDLE for data
        $xml = $this->buildTopupXml($reference, $target, 0.0, $productId, 'DATA_BUNDLE');
        return $this->sendRequest('urn:requestTopup', $xml, $reference);
    }

    /**
     * Dispatch Voucher Generation (Option 3).
     */
    public function purchaseVoucher(string $receiverPhone, float $amount, string $productSku, string $reference): array
    {
        $target = $this->formatMsisdn($receiverPhone);
        $xml = $this->buildPurchaseXml($reference, $target, $amount, $productSku);
        $result = $this->sendRequest('urn:requestPurchase', $xml, $reference);

        // If success, parse the PIN and Serial from resultDescription using Regex matching the Glo doc
        if ($result['success'] && isset($result['response']['resultDescription'])) {
            $desc = $result['response']['resultDescription'];
            
            // Regex to extract Pin and Serial
            $pin = null;
            $serial = null;
            
            if (preg_match('/Pin:\s*([0-9]+)/i', $desc, $matches)) {
                $pin = $matches[1];
            }
            if (preg_match('/Serial:\s*([0-9]+)/i', $desc, $matches)) {
                $serial = $matches[1];
            }

            $result['pin'] = $pin;
            $result['serial'] = $serial;
        }

        return $result;
    }

    /**
     * Sandbox mock simulation matching Glo ERS specifications.
     */
    protected function handleSandboxMock(string $soapAction, string $xmlPayload, string $reference): array
    {
        // Parse receiver/dest msisdn
        $destMsisdn = '2348050000000';
        if (preg_match('/<id>(.*?)<\/id>/', $xmlPayload, $matches)) {
            // Take the last id which is typically the subscriber/receiver
            $destMsisdn = $matches[count($matches) - 1];
        }

        // Check if phone contains mock failure code 9999
        if (str_contains($destMsisdn, '9999')) {
            $resultCode = 104; // Insufficient credit error code
            $resultDesc = 'REJECTED_BUSINESS_LOGIC: Insufficient distributor credit.';
        } else {
            $resultCode = 0;
            $resultDesc = 'SUCCESS';
        }

        $ersRef = 'GLO-' . strtoupper(uniqid());

        if (str_contains($soapAction, 'Topup')) {
            $mockResponse = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
 <soapenv:Body>
   <ns2:requestTopupResponse xmlns:ns2="http://external.interfaces.ers.seamless.com/">
     <return>
       <ersReference>' . $ersRef . '</ersReference>
       <resultCode>' . $resultCode . '</resultCode>
       <resultDescription>' . $resultDesc . '</resultDescription>
       <requestedTopupAmount>
         <currency>NGN</currency>
         <value>100</value>
       </requestedTopupAmount>
       <topupAmount>
         <currency>NGN</currency>
         <value>100</value>
       </topupAmount>
     </return>
   </ns2:requestTopupResponse>
 </soapenv:Body>
</soapenv:Envelope>';
        } else { // requestPurchase
            if ($resultCode === 0) {
                // Mock voucher details inside resultDescription as per Glo doc page 34
                $mockPin = str_pad((string) random_int(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT);
                $mockSerial = str_pad((string) random_int(1000000000000, 9999999999999), 13, '0', STR_PAD_LEFT);
                $resultDesc = "You have sold one voucher:-\nAmount: 100.0 NGN\nPin: {$mockPin}\nExpiry Date: 31-12-2028\nRef.:{$ersRef}\nSerial:{$mockSerial}";
            }

            $mockResponse = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
 <soapenv:Body>
   <ns2:requestPurchaseResponse xmlns:ns2="http://external.interfaces.ers.seamless.com/">
     <return>
       <ersReference>' . $ersRef . '</ersReference>
       <resultCode>' . $resultCode . '</resultCode>
       <resultDescription>' . $resultDesc . '</resultDescription>
     </return>
   </ns2:requestPurchaseResponse>
 </soapenv:Body>
</soapenv:Envelope>';
        }

        $parsed = $this->parseResponse($mockResponse);
        if (!$parsed['status']) {
            return $parsed;
        }

        $data = $parsed['data'];
        $success = ($resultCode === 0);

        $result = [
            'success' => $success,
            'reference' => $ersRef,
            'response' => $data
        ];

        // For vouchers, parse details out
        if ($success && str_contains($soapAction, 'Purchase')) {
            $desc = $data['resultDescription'];
            if (preg_match('/Pin:\s*([0-9]+)/i', $desc, $matches)) {
                $result['pin'] = $matches[1];
            }
            if (preg_match('/Serial:\s*([0-9]+)/i', $desc, $matches)) {
                $result['serial'] = $matches[1];
            }
        }

        ApiLog::record([
            'user_id'          => auth()->id(),
            'service'          => str_contains($soapAction, 'Purchase') ? 'voucher' : 'airtime',
            'provider'         => 'glo_ers',
            'reference'        => $reference,
            'endpoint'         => $this->endpoint . ' (Sandbox Mock)',
            'method'           => 'POST',
            'payload'          => ['xml' => $xmlPayload],
            'request_headers'  => ['Content-Type' => 'text/xml', 'SOAPAction' => $soapAction],
            'response'         => $data,
            'http_status'      => 200,
            'response_headers' => ['Content-Type' => 'text/xml'],
            'duration_ms'      => 50,
            'success'          => $success,
        ]);

        return $result;
    }
}

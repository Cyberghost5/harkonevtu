<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ApiLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NineMobileEvcSoapService
{
    protected string $endpoint;
    protected ?string $username;
    protected ?string $password;
    protected ?string $key;
    protected ?string $token;
    protected string $sourceId;
    protected string $thirdPartyId;
    protected string $channelId;
    protected string $mode;

    public function __construct()
    {
        $this->endpoint     = AppSetting::get('nine_mobile_evc_endpoint', 'https://10.0.0.1:8080/EVC/SinglePointFulfilment/EVCPinlessInterfaceEndpoint');
        $this->username     = AppSetting::get('nine_mobile_evc_username');
        $this->password     = AppSetting::get('nine_mobile_evc_password');
        $this->key          = AppSetting::get('nine_mobile_evc_key');
        $this->token        = AppSetting::get('nine_mobile_evc_token');
        $this->sourceId     = AppSetting::get('nine_mobile_evc_source_id', '1001');
        $this->thirdPartyId = AppSetting::get('nine_mobile_evc_third_party_id', '1001');
        $this->channelId    = AppSetting::get('nine_mobile_evc_channel_id', 'WEB');
        $this->mode         = AppSetting::get('nine_mobile_evc_mode', 'sandbox');
    }

    /**
     * Checks if mandatory credentials are set.
     */
    public function isConfigured(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }

    /**
     * Format recipient phone number.
     * 9mobile EVC typically expects standard 11-digit MSISDN e.g. "08091234567"
     * or 234 prefix depending on trade partner profile.
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
     * Parse 9Mobile EVC SOAP XML response.
     */
    public function parseResponse(string $xmlString): array
    {
        try {
            // Strip XML namespaces for simplified element access
            $cleanXml = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$3', $xmlString);
            $xml = new \SimpleXMLElement($cleanXml);

            $body = $xml->Body;
            if (!$body) {
                return ['status' => false, 'message' => 'Invalid SOAP envelope response.'];
            }

            // Look for SDF_Data response node
            $sdfData = $body->SDF_Data ?? null;
            if (!$sdfData) {
                if (isset($body->Fault)) {
                    $faultString = (string) ($body->Fault->faultstring ?? 'SOAP Fault occurred.');
                    return ['status' => false, 'message' => $faultString];
                }
                return ['status' => false, 'message' => 'Missing SDF_Data response node in body.'];
            }

            $externalRef = (string) ($sdfData->externalReference ?? '');
            $processTypeId = (string) ($sdfData->processTypeID ?? '');

            $resultNode = $sdfData->result ?? null;
            if (!$resultNode) {
                return ['status' => false, 'message' => 'Response structure is missing <result> element.'];
            }

            $statusCode = (string) ($resultNode->statusCode ?? '-1');
            $errorCode = (string) ($resultNode->errorCode ?? '-1');
            $errorDesc = (string) ($resultNode->errorDescription ?? '');
            $instanceId = (string) ($resultNode->instanceId ?? '');

            $data = [
                'externalReference' => $externalRef,
                'processTypeID'     => $processTypeId,
                'statusCode'        => $statusCode,
                'errorCode'         => $errorCode,
                'errorDescription'  => $errorDesc,
                'instanceId'        => $instanceId,
            ];

            // Extract optional parameters array if returned
            if (isset($sdfData->parameters->parameter)) {
                foreach ($sdfData->parameters->parameter as $param) {
                    $pName = (string) ($param->name ?? '');
                    $pValue = (string) ($param->value ?? '');
                    if ($pName !== '') {
                        $data['params'][$pName] = $pValue;
                    }
                }
            }

            return [
                'status' => true,
                'data'   => $data,
            ];
        } catch (\Exception $e) {
            Log::error('9Mobile EVC XML Parsing Error', ['message' => $e->getMessage(), 'xml' => $xmlString]);
            return [
                'status'  => false,
                'message' => 'XML parsing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build SOAP XML payload for Direct Pinless Airtime Recharge.
     * ProcessTypeID: 8799
     * Amount is passed in Kobo (Naira * 100).
     */
    public function buildRechargeXml(string $reference, string $msisdn, float $amountInNaira): string
    {
        $amountInKobo = (int) round($amountInNaira * 100);

        return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://com.cellc.sdf.iway">
   <soapenv:Header/>
   <soapenv:Body>
      <com:SDF_Data>
         <externalReference>' . htmlspecialchars($reference) . '</externalReference>
         <processTypeID>8799</processTypeID>
         <sourceID>' . htmlspecialchars($this->sourceId) . '</sourceID>
         <username>' . htmlspecialchars($this->username ?? '') . '</username>
         <password>' . htmlspecialchars($this->password ?? '') . '</password>
         <processFlag>1</processFlag>
         <parameters>
            <parameter>
               <name>RechargeType</name>
               <value>001</value>
            </parameter>
            <parameter>
               <name>MSISDN</name>
               <value>' . htmlspecialchars($msisdn) . '</value>
            </parameter>
            <parameter>
               <name>Amount</name>
               <value>' . htmlspecialchars((string) $amountInKobo) . '</value>
            </parameter>
            <parameter>
               <name>Channel_ID</name>
               <value>' . htmlspecialchars($this->channelId) . '</value>
            </parameter>
         </parameters>
         <ThirdPartyID>' . htmlspecialchars($this->thirdPartyId) . '</ThirdPartyID>
      </com:SDF_Data>
   </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * Build SOAP XML payload for Status Enquiry.
     * ProcessTypeID: 7911
     */
    public function buildStatusEnquiryXml(string $reference, string $targetReference): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://com.cellc.sdf.iway">
   <soapenv:Header/>
   <soapenv:Body>
      <com:SDF_Data>
         <externalReference>' . htmlspecialchars($reference) . '</externalReference>
         <processTypeID>7911</processTypeID>
         <sourceID>' . htmlspecialchars($this->sourceId) . '</sourceID>
         <username>' . htmlspecialchars($this->username ?? '') . '</username>
         <password>' . htmlspecialchars($this->password ?? '') . '</password>
         <processFlag>1</processFlag>
         <parameters>
            <parameter>
               <name>TargetExternalReference</name>
               <value>' . htmlspecialchars($targetReference) . '</value>
            </parameter>
         </parameters>
         <ThirdPartyID>' . htmlspecialchars($this->thirdPartyId) . '</ThirdPartyID>
      </com:SDF_Data>
   </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * Dispatch SOAP HTTP Request with 9Mobile One-Way Auth headers.
     */
    public function sendRequest(string $xmlPayload, string $reference, string $service = 'airtime'): array
    {
        if ($this->mode === 'sandbox' || !$this->isConfigured()) {
            return $this->handleSandboxMock($xmlPayload, $reference);
        }

        $start = hrtime(true);
        $httpStatus = null;
        $responseHeaders = null;
        $data = [];
        $success = false;

        $headers = [
            'Content-Type' => 'text/xml;charset=UTF-8',
            'SOAPAction'   => 'http://sdf.cellc.net/process',
        ];

        if (!empty($this->key)) {
            $headers['key'] = $this->key;
        }
        if (!empty($this->token)) {
            $headers['token'] = $this->token;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->send('POST', $this->endpoint, [
                    'body' => $xmlPayload
                ]);

            $httpStatus = $response->status();
            $responseHeaders = $response->headers();

            $parsed = $this->parseResponse($response->body());

            if ($parsed['status']) {
                $data = $parsed['data'];
                $statusCode = $data['statusCode'] ?? '-1';
                $errorCode = $data['errorCode'] ?? '-1';

                $success = ($statusCode === '0' && ($errorCode === '0' || $errorCode === '00'));

                if (!$success) {
                    $data['message'] = !empty($data['errorDescription'])
                        ? $data['errorDescription']
                        : "9Mobile EVC returned failure (statusCode: {$statusCode}, errorCode: {$errorCode})";
                }
            } else {
                $data = ['message' => $parsed['message'] ?? '9Mobile EVC HTTP transaction failed with status ' . $response->status()];
            }
        } catch (\Exception $e) {
            $data = ['message' => 'Connection to 9Mobile EVC gateway timeout or failed: ' . $e->getMessage()];
            Log::error('9Mobile EVC SOAP Request Exception', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);

            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => $service,
                'provider'         => 'nine_mobile_evc',
                'reference'        => $reference,
                'endpoint'         => $this->endpoint,
                'method'           => 'POST',
                'payload'          => ['xml' => $xmlPayload],
                'request_headers'  => $headers,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return [
            'success'   => $success,
            'reference' => $data['instanceId'] ?? $reference,
            'response'  => $data
        ];
    }

    /**
     * Vend Airtime via 9Mobile Direct EVC.
     */
    public function vendAirtime(string $phone, float $amount, string $reference): array
    {
        $target = $this->formatMsisdn($phone);
        $xml = $this->buildRechargeXml($reference, $target, $amount);
        return $this->sendRequest($xml, $reference, 'airtime');
    }

    /**
     * Check transaction status via 9Mobile EVC Status Enquiry.
     */
    public function checkStatus(string $targetReference, string $reference = ''): array
    {
        $ref = $reference ?: 'ST-' . strtoupper(uniqid());
        $xml = $this->buildStatusEnquiryXml($ref, $targetReference);
        return $this->sendRequest($xml, $ref, 'status_enquiry');
    }

    /**
     * Sandbox Mock Handler for testing without live 9Mobile credentials.
     */
    protected function handleSandboxMock(string $xmlPayload, string $reference): array
    {
        $msisdn = '08090000000';
        if (preg_match('/<name>MSISDN<\/name>\s*<value>(.*?)<\/value>/', $xmlPayload, $matches)) {
            $msisdn = $matches[1];
        }

        // Check mock failure triggers
        if (str_contains($msisdn, '9999') || str_contains($reference, 'FAIL')) {
            $statusCode = '1';
            $errorCode = '3';
            $errorDesc = 'Insufficient fund in trade partner wallet';
        } else {
            $statusCode = '0';
            $errorCode = '0';
            $errorDesc = 'Successful';
        }

        $instanceId = '9MOB-' . mt_rand(10000000, 99999999);

        $mockResponse = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns0="http://com.cellc.sdf.iway">
   <soapenv:Body>
      <ns0:SDF_Data>
         <externalReference>' . htmlspecialchars($reference) . '</externalReference>
         <processTypeID>8799</processTypeID>
         <sourceID>' . htmlspecialchars($this->sourceId) . '</sourceID>
         <username>' . htmlspecialchars($this->username ?: 'sandbox_user') . '</username>
         <password>******</password>
         <processFlag>1</processFlag>
         <result>
            <statusCode>' . $statusCode . '</statusCode>
            <errorCode>' . $errorCode . '</errorCode>
            <errorDescription>' . $errorDesc . '</errorDescription>
            <instanceId>' . $instanceId . '</instanceId>
         </result>
         <ThirdPartyID>' . htmlspecialchars($this->thirdPartyId) . '</ThirdPartyID>
      </ns0:SDF_Data>
   </soapenv:Body>
</soapenv:Envelope>';

        $parsed = $this->parseResponse($mockResponse);
        $data = $parsed['data'];
        $success = ($statusCode === '0' && $errorCode === '0');

        if (!$success) {
            $data['message'] = $errorDesc;
        }

        ApiLog::record([
            'user_id'          => auth()->id(),
            'service'          => 'airtime',
            'provider'         => 'nine_mobile_evc',
            'reference'        => $reference,
            'endpoint'         => $this->endpoint . ' (Sandbox Mock)',
            'method'           => 'POST',
            'payload'          => ['xml' => $xmlPayload],
            'request_headers'  => ['Content-Type' => 'text/xml', 'SOAPAction' => 'http://sdf.cellc.net/process'],
            'response'         => $data,
            'http_status'      => 200,
            'response_headers' => ['Content-Type' => 'text/xml'],
            'duration_ms'      => 45,
            'success'          => $success,
        ]);

        return [
            'success'   => $success,
            'reference' => $instanceId,
            'response'  => $data
        ];
    }
}

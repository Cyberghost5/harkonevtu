<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\MtnErsSequence;
use App\Services\MtnErsSoapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MtnErsSoapServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MtnErsSoapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MtnErsSoapService();
    }

    public function test_xml_generation(): void
    {
        $xml = $this->service->buildVendXml('2349062058470', '09062058617', 100, 138, 1);

        $this->assertStringContainsString('<xsd:origMsisdn>2349062058470</xsd:origMsisdn>', $xml);
        $this->assertStringContainsString('<xsd:destMsisdn>09062058617</xsd:destMsisdn>', $xml);
        $this->assertStringContainsString('<xsd:amount>100</xsd:amount>', $xml);
        $this->assertStringContainsString('<xsd:sequence>138</xsd:sequence>', $xml);
        $this->assertStringContainsString('<xsd:tariffTypeId>1</xsd:tariffTypeId>', $xml);
        $this->assertStringContainsString('<xsd:serviceproviderId>1</xsd:serviceproviderId>', $xml);
    }

    public function test_xml_response_parsing(): void
    {
        $rawXml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
 <soapenv:Body>
   <vendResponse xmlns:xsd="http://hostif.vtm.prism.co.za/xsd">
     <destBalance>998.0</destBalance>
     <responseCode>0</responseCode>
     <responseMessage>Successful</responseMessage>
     <txRefId>ERS-12345</txRefId>
   </vendResponse>
 </soapenv:Body>
</soapenv:Envelope>';

        $parsed = $this->service->parseResponse($rawXml);

        $this->assertTrue($parsed['status']);
        $this->assertEquals('998.0', $parsed['data']['destBalance']);
        $this->assertEquals('0', $parsed['data']['responseCode']);
        $this->assertEquals('Successful', $parsed['data']['responseMessage']);
        $this->assertEquals('ERS-12345', $parsed['data']['txRefId']);
    }

    public function test_sandbox_success_mock(): void
    {
        AppSetting::set('mtn_ers_mode', 'sandbox');

        $result = $this->service->vend('08030001122', 200, 1);

        $this->assertTrue($result['status']);
        $this->assertEquals('Successful', $result['message']);
        $this->assertArrayHasKey('txRefId', $result['data']);
        $this->assertEquals('40692125281574', $result['data']['voucherPIN']);
    }

    public function test_sandbox_failure_mock(): void
    {
        AppSetting::set('mtn_ers_mode', 'sandbox');

        // Number containing '9999' triggers mock error
        $result = $this->service->vend('08039999122', 200, 1);

        $this->assertFalse($result['status']);
        $this->assertEquals('Insufficient Airtime', $result['message']);
        $this->assertEquals('301', $result['data']['responseCode']);
    }

    public function test_sequence_atomic_increments(): void
    {
        $seq1 = MtnErsSequence::getAndIncrement('test-user');
        $seq2 = MtnErsSequence::getAndIncrement('test-user');

        $this->assertEquals(1, $seq1);
        $this->assertEquals(2, $seq2);
    }

    public function test_sequence_recovery_and_auto_retry(): void
    {
        AppSetting::set('mtn_ers_username', 'partner_user');
        AppSetting::set('mtn_ers_pin', '1234');
        AppSetting::set('mtn_ers_mode', 'production');
        AppSetting::set('mtn_ers_endpoint', 'https://ers.seamless.se/test');

        // Seed sequence at 5
        MtnErsSequence::setNextSequence('partner_user', 5);

        // Fake first call returning 106 (sequence out of sync, ERS expects 15)
        // Fake second call returning 0 (successful retry)
        Http::fake([
            'https://ers.seamless.se/test' => Http::sequence()
                ->push('<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
 <soapenv:Body>
   <vendResponse>
     <responseCode>106</responseCode>
     <responseMessage>Sequence Number Check Failed</responseMessage>
     <lastseq>14</lastseq>
   </vendResponse>
 </soapenv:Body>
</soapenv:Envelope>', 200)
                ->push('<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
 <soapenv:Body>
   <vendResponse>
     <responseCode>0</responseCode>
     <responseMessage>Successful</responseMessage>
     <txRefId>ERS-RETRY-SUCCESS</txRefId>
   </vendResponse>
 </soapenv:Body>
</soapenv:Envelope>', 200)
        ]);

        $service = new MtnErsSoapService();
        $result = $service->vend('08031234567', 100, 1);

        $this->assertTrue($result['status']);
        $this->assertEquals('Successful', $result['message']);
        $this->assertEquals('ERS-RETRY-SUCCESS', $result['data']['txRefId']);

        // Check that sequence was auto-synced to 16 in DB (lastseq 14 + 1 used during retry, then incremented to 16)
        $this->assertEquals(16, MtnErsSequence::where('key', 'partner_user')->first()->next_sequence);
    }
}

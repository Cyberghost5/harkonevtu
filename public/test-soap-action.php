<?php

// Load Laravel Bootstrap
require __DIR__ . '/../bootstrap/app.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;

// Get credentials
$endpoint = AppSetting::get('glo_ers_endpoint', 'http://10.10.3.42:8914/topupservice/service');
$username = AppSetting::get('glo_ers_username');
$password = AppSetting::get('glo_ers_password');
$clientId = AppSetting::get('glo_ers_client_id', 'ERS');
$distributorId = AppSetting::get('glo_ers_distributor_id');
$distributorUserId = AppSetting::get('glo_ers_distributor_userid') ?: '9900';

if (!$username || !$password) {
    die("Error: Glo ERS credentials are not configured in settings.\n");
}

// Build standard payload
$xmlPayload = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ext="http://external.interfaces.ers.seamless.com/">
 <soapenv:Header/>
 <soapenv:Body>
   <ext:requestTopup>
     <context>
       <channel>WSClient</channel>
       <clientComment>Topup test</clientComment>
       <clientId>' . htmlspecialchars($clientId) . '</clientId>
       <clientReference>test_' . time() . '</clientReference>
       <clientRequestTimeout>30000</clientRequestTimeout>
       <initiatorPrincipalId>
         <id>' . htmlspecialchars($distributorId) . '</id>
         <type>RESELLERUSER</type>
         <userId>' . htmlspecialchars($distributorUserId) . '</userId>
       </initiatorPrincipalId>
       <password>' . htmlspecialchars($password) . '</password>
     </context>
     <senderPrincipalId>
       <id>' . htmlspecialchars($distributorId) . '</id>
       <type>RESELLERUSER</type>
       <userId>' . htmlspecialchars($distributorUserId) . '</userId>
     </senderPrincipalId>
     <topupPrincipalId>
       <id>2348050000000</id>
       <type>SUBSCRIBERMSISDN</type>
     </topupPrincipalId>
     <senderAccountSpecifier>
       <accountId>' . htmlspecialchars($distributorId) . '</accountId>
       <accountTypeId>RESELLER</accountTypeId>
     </senderAccountSpecifier>
     <topupAccountSpecifier>
       <accountId>2348050000000</accountId>
       <accountTypeId>AIRTIME</accountTypeId>
     </topupAccountSpecifier>
     <productId>TOPUP</productId>
     <amount>
       <currency>NGN</currency>
       <value>10</value>
     </amount>
   </ext:requestTopup>
 </soapenv:Body>
</soapenv:Envelope>';

// SOAPActions to test
$actions = [
    'Empty string' => '""',
    'Namespace URI + operation (CamelCase)' => '"http://external.interfaces.ers.seamless.com/requestTopUp"',
    'Namespace URI + operation (lowercase)' => '"http://external.interfaces.ers.seamless.com/requestTopup"',
    'Short name (CamelCase)' => '"requestTopUp"',
    'Short name (lowercase)' => '"requestTopup"',
    'No SOAPAction header' => null,
];

echo "<pre>Testing SOAPAction headers against: $endpoint\n\n";

foreach ($actions as $label => $actionValue) {
    echo "========================================================\n";
    echo "Testing Action: $label (value: " . var_export($actionValue, true) . ")\n";
    echo "========================================================\n";
    
    try {
        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
        ];
        if ($actionValue !== null) {
            $headers['SOAPAction'] = $actionValue;
        }
        
        $response = Http::withHeaders($headers)
            ->timeout(10)
            ->send('POST', $endpoint, [
                'body' => $xmlPayload
            ]);
            
        echo "HTTP Status: " . $response->status() . "\n";
        echo "Response Headers:\n";
        print_r($response->headers());
        echo "Response Body:\n";
        echo htmlspecialchars($response->body()) . "\n";
    } catch (\Exception $e) {
        echo "Request Failed with Exception: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
echo "</pre>";

<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://waghub.mekayastudio.com/api/v1/number-checks");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'recipient' => ['type' => 'phone', 'value' => '081234567890'],
    'route_key' => 'default'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer wgh_ofA0l6EgTTBOZwFzcqQwO9Mi5Ld1Eox7W6G0ZQLgMek6soKGjcaFB8nQI5SUxL7y',
    'Accept: application/json',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
echo $response;

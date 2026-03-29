<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';

$license_key = get_config('license_key', 'test_key');
echo "Testing with license key: $license_key\n";
echo "AUTH_SERVER_URL: " . AUTH_SERVER_URL . "\n";
echo "DOMAIN: " . $_SERVER['SERVER_NAME'] . "\n";

$post_data = [
    'license_key' => $license_key,
    'domain' => 'localhost', // ターミナルからだと空になる可能性があるので明示
    'api_token' => defined('SECRET_TOKEN') ? SECRET_TOKEN : ''
];

$ch = curl_init(AUTH_SERVER_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response_body = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "cURL Error: $curl_error\n";
echo "Response Body: $response_body\n";

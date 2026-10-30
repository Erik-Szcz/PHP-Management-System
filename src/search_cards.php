<?php

ini_set('display_errors', '0');   // keep warnings out of the JSON, but still log them
header('Content-Type: application/json');

$name = $_GET['name'] ?? '';

if (strlen($name) < 3) {
    echo json_encode(['data' => []]);
    exit;
}

$url = 'https://api.tcgdex.net/v2/de/cards?name=' . urlencode(ucwords($name));

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: pokemon-shop/1.0']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (!$response || $http_code !== 200) {
    error_log("TCGdex search failed: HTTP $http_code " . curl_error($ch));
    http_response_code(502);
    echo json_encode(['data' => []]);
    exit;
}

$results = json_decode($response, true) ?? [];

echo json_encode(['data' => $results]);

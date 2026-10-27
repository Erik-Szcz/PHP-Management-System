<?php
error_reporting(0);
header('Content-Type: application/json');

$name = $_GET['name'] ?? '';

if(strlen($name) < 3){
    echo json_encode([]);
    exit;
}

$url = 'https://api.tcgdex.net/v2/de/cards?name=' . urlencode(ucwords($name));

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: pokemon-shop/1.0']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if(!$response || $http_code !== 200){
    echo json_encode(['data' => []]);
}

$results = json_decode($response, true) ?? [];

echo json_encode(['data' => $results]);
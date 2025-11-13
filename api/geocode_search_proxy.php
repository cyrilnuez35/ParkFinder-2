<?php
require __DIR__ . '/util.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 1;
$countrycodes = isset($_GET['countrycodes']) ? trim($_GET['countrycodes']) : 'ph';
$addressdetails = isset($_GET['addressdetails']) ? intval($_GET['addressdetails']) : 1;

if ($q === '') {
    json_response(['message' => 'Missing query'], 400);
}

$query = http_build_query([
    'format' => 'json',
    'q' => $q,
    'limit' => $limit,
    'countrycodes' => $countrycodes,
    'addressdetails' => $addressdetails,
]);
$url = 'https://nominatim.openstreetmap.org/search?' . $query;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: ParkingFinder/1.0 (+http://localhost/PP/PP/)',
    'Accept: application/json'
]);
$resp = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($resp === false) {
    json_response(['message' => 'Upstream error', 'error' => $err], 502);
}

$data = json_decode($resp, true);
if ($data === null) {
    json_response(['message' => 'Invalid upstream response', 'code' => $code], 502);
}

json_response($data, 200);
?>
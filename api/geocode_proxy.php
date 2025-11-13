<?php
require __DIR__ . '/util.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
$zoom = isset($_GET['zoom']) ? intval($_GET['zoom']) : 18;
$addr = isset($_GET['addressdetails']) ? intval($_GET['addressdetails']) : 1;

if (!is_finite($lat) || !is_finite($lon)) {
    json_response(['message' => 'Invalid lat/lon'], 400);
}

$url = sprintf(
    'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=%F&lon=%F&zoom=%d&addressdetails=%d',
    $lat, $lon, $zoom, $addr
);

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
if (!$data) {
    json_response(['message' => 'Invalid upstream response', 'code' => $code], 502);
}

json_response($data, 200);
?>
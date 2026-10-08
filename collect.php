<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Csak POST kérés engedélyezett"]);
    exit();
}

// Rate limiting: Maximum 10 kérés percenként IP-címenként
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$timeWindow = 60; // 60 másodperc
$maxRequests = 10; // Max kérések száma

$rateFile = sys_get_temp_dir() . '/rate_' . md5($ip) . '.json';
$rateData = file_exists($rateFile) ? json_decode(file_get_contents($rateFile), true) : ['count' => 0, 'startTime' => time()];

if (time() - $rateData['startTime'] > $timeWindow) {
    // Új időablak indítása
    $rateData = ['count' => 1, 'startTime' => time()];
} else {
    $rateData['count']++;
}

file_put_contents($rateFile, json_encode($rateData));

if ($rateData['count'] > $maxRequests) {
    http_response_code(429); // Too Many Requests
    echo json_encode(["status" => "error", "message" => "Túl sok kérés! Próbáld újra később."]);
    exit();
}

$userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

// Ismert botok és szörfözők listája
$bots = ['bot', 'crawl', 'spider', 'slurp', 'curl', 'python', 'wget', 'headlesschrome'];

foreach ($bots as $bot) {
    if (strpos($userAgent, $bot) !== false) {
        // Csendben leállunk, nem mentjük el
        echo json_encode(["status" => "ignored", "message" => "Bot kérés figyelmen kívül hagyva"]);
        exit();
    }
}

$file = 'analytics.json';
$maxRecords = 20000; // Maximum 20 000 bejegyzést őrzünk meg

if (file_exists($file)) {
    $existingContent = file_get_contents($file);
    $currentData = json_decode($existingContent, true) ?? [];
} else {
    $currentData = [];
}

$currentData[] = $record;

// Ha túlléptük a maximális darabszámot, a legrégebbi elemeket levágjuk az elejéről
if (count($currentData) > $maxRecords) {
    $currentData = array_slice($currentData, -$maxRecords);
}

file_put_contents($file, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 1. Beérkező JSON adatok beolvasása
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Érvénytelen JSON"]);
    exit();
}

// 2. IP-cím kinyerése és anonimizálása (GDPR)
$rawIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (filter_var($rawIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    // IPv4 esetén az utolsó számcsoportot 0-ra cseréljük (pl. 80.99.151.146 -> 80.99.151.0)
    $anonymizedIp = preg_replace('/[0-9]+$/', '0', $rawIp);
} elseif (filter_var($rawIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    // IPv6 esetén az utolsó 80 bitet nullázzuk
    $anonymizedIp = inet_ntop(inet_pton($rawIp) & inet_pton("ffff:ffff:ffff::"));
} else {
    $anonymizedIp = 'unknown';
}

// 3. User Agent (Böngésző / OS) beolvasása
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Ismeretlen';

// 4. Munkamenet azonosító generálása (Napi hash az IP-ből és User Agent-ből)
// Ez lehetővé teszi az egyedi látogatók számolását cookie-k nélkül!
$visitorHash = md5($anonymizedIp . $userAgent . date('Y-m-d'));

// 5. Adatstruktúra összeállítása
$record = [
    "visitorId"   => substr($visitorHash, 0, 10), // Anonim látogató azonosító
    "pageUrl"     => $data['pageUrl'] ?? '/',
    "pageTitle"   => $data['pageTitle'] ?? '',
    "referrer"    => $data['referrer'] ?? '',
    "deviceType"  => $data['deviceType'] ?? 'Desktop',
    "screenWidth" => $data['screenWidth'] ?? 0,
    "language"    => $data['language'] ?? '',
    "timeZone"    => $data['timeZone'] ?? '',
    "ipAnonym"    => $anonymizedIp,
    "userAgent"   => $userAgent,
    "created_at"  => date('Y-m-d H:i:s')
];

// 6. Mentés az analytics.json fájlba
$file = 'analytics.json';
$currentData = [];

if (file_exists($file)) {
    $existingContent = file_get_contents($file);
    $currentData = json_decode($existingContent, true) ?? [];
}

$currentData[] = $record;

if (file_put_contents($file, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(["status" => "success"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Nem sikerült a fájlba írás"]);
}
<?php
// Engedélyezzük a CORS kéréseket, hogy a helpdesk oldalról átérjen az adat
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Adatbázis kapcsolat megnyitása (ha nincs analytics.sqlite fájl, automatikusan létrehozza)
$db = new PDO('sqlite:analytics.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Tábla létrehozása, ha még nem létezik
$db->exec("CREATE TABLE IF NOT EXISTS pageviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_url TEXT,
    referrer TEXT,
    screen_width INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Beérkező JSON adatok beolvasása
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $stmt = $db->prepare("INSERT INTO pageviews (page_url, referrer, screen_width) VALUES (:url, :referrer, :width)");
    $stmt->execute([
        ':url' => $data['pageUrl'] ?? '',
        ':referrer' => $data['referrer'] ?? '',
        ':width' => $data['screenWidth'] ?? 0
    ]);

    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nincs adat']);
}
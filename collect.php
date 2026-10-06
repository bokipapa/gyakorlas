<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Beérkező adatok beolvasása
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    // Látogatási adat kiegészítése az időponttal
    $data['created_at'] = date('Y-m-d H:i:s');

    // Meglévő adatok betöltése a fájlból
    $file = 'analytics.json';
    $currentData = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    
    if (!is_array($currentData)) {
        $currentData = [];
    }

    // Új rekord hozzáadása és mentés
    $currentData[] = $data;
    file_put_contents($file, json_encode($currentData, JSON_PRETTY_PRINT));

    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nincs adat']);
}
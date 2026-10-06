<?php
$file = 'analytics.json';
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

$totalViews = count($data);
$todayViews = 0;
$pages = [];

$today = date('Y-m-d');

foreach ($data as $row) {
    // Mai látogatások számolása
    if (isset($row['created_at']) && strpos($row['created_at'], $today) === 0) {
        $todayViews++;
    }
    // Oldalak szerinti csoportosítás
    $url = $row['pageUrl'] ?? 'Ismeretlen';
    $pages[$url] = ($pages[$url] ?? 0) + 1;
}

// Sorbarendezés megtekintés szerint
arsort($pages);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Analitika Műszerfal</title>
    <style>
        body { font-family: sans-serif; margin: 40px; background: #f4f4f9; }
        .card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Analitika Műszerfal</h1>

    <div class="card">
        <h2>Összesítés</h2>
        <p><strong>Összes oldalmegtekintés:</strong> <?= $totalViews ?></p>
        <p><strong>Mai megtekintések:</strong> <?= $todayViews ?></p>
    </div>

    <div class="card">
        <h2>Legnépszerűbb oldalak</h2>
        <table>
            <tr><th>Oldal (URL)</th><th>Megtekintések</th></tr>
            <?php foreach ($pages as $url => $count): ?>
                <tr>
                    <td><?= htmlspecialchars($url) ?></td>
                    <td><?= $count ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
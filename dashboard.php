<?php
$file = 'analytics.json';
$data = [];

if (file_exists($file)) {
    $content = file_get_contents($file);
    $data = json_decode($content, true) ?? [];
}

// 1. Összesítések kiszámítása
$totalPageviews = count($data);

$uniqueVisitors = [];
$deviceCounts = ['Desktop' => 0, 'Mobile' => 0];
$pageCounts = [];
$referrerCounts = [];

foreach ($data as $row) {
    // Egyedi látogatók számlálása (visitorId vagy ipAnonym alapján)
    $vId = $row['visitorId'] ?? ($row['ipAnonym'] ?? 'unknown');
    $uniqueVisitors[$vId] = true;

    // Eszközök megoszlása
    $device = $row['deviceType'] ?? 'Desktop';
    if (!isset($deviceCounts[$device])) {
        $deviceCounts[$device] = 0;
    }
    $deviceCounts[$device]++;

    // Legnépszerűbb oldalak
    $page = $row['pageUrl'] ?? '/';
    $pageCounts[$page] = ($pageCounts[$page] ?? 0) + 1;

    // Hivatkozási források (Referrers)
    $ref = !empty($row['referrer']) ? parse_url($row['referrer'], PHP_URL_HOST) : 'Közvetlen / Címsorból';
    if (!$ref) $ref = $row['referrer'];
    $referrerCounts[$ref] = ($referrerCounts[$ref] ?? 0) + 1;
}

$uniqueCount = count($uniqueVisitors);
arsort($pageCounts);
arsort($referrerCounts);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:description" content="Analitika a HELPDESK oldalhoz.">
    <title>Analitika Műszerfal</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        h1 { margin-bottom: 20px; color: #1e293b; }
        
        /* Összesítő kártyák */
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card h3 { margin: 0 0 10px 0; font-size: 14px; color: #64748b; text-transform: uppercase; }
        .card .number { font-size: 32px; font-weight: bold; color: #0f172a; }

        /* Grid az adattáblákhoz */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .box { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .box h2 { font-size: 18px; margin-top: 0; margin-bottom: 15px; color: #334155; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        th { color: #64748b; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        
        .badge { background: #e2e8f0; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; color: #475569; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Analitika Műszerfal (Synology NAS)</h1>

        <!-- KPI Kártyák -->
        <div class="cards">
            <div class="card">
                <h3>Összes megtekintés</h3>
                <div class="number"><?= $totalPageviews ?></div>
            </div>
            <div class="card">
                <h3>Egyedi látogatók</h3>
                <div class="number"><?= $uniqueCount ?></div>
            </div>
            <div class="card">
                <h3>Asztali gép (Desktop)</h3>
                <div class="number"><?= $deviceCounts['Desktop'] ?? 0 ?></div>
            </div>
            <div class="card">
                <h3>Mobileszköz (Mobile)</h3>
                <div class="number"><?= $deviceCounts['Mobile'] ?? 0 ?></div>
            </div>
        </div>

        <div class="grid">
            <!-- Legnépszerűbb oldalak -->
            <div class="box">
                <h2> Top Oldalak</h2>
                <table>
                    <thead>
                        <tr><th>Oldal URL</th><th style="text-align: right;">Megtekintés</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($pageCounts, 0, 10) as $page => $count): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($page) ?></code></td>
                                <td style="text-align: right;"><span class="badge"><?= $count ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Hivatkozó források -->
            <div class="box">
                <h2> Források (Referrers)</h2>
                <table>
                    <thead>
                        <tr><th>Forrás</th><th style="text-align: right;">Látogatók</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($referrerCounts, 0, 10) as $ref => $count): ?>
                            <tr>
                                <td><?= htmlspecialchars($ref) ?></td>
                                <td style="text-align: right;"><span class="badge"><?= $count ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Legutóbbi látogatások naplója -->
        <div class="box">
            <h2> Legutóbbi látogatások</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Időpont</th>
                            <th>Oldal</th>
                            <th>Eszköz</th>
                            <th>Nyelv</th>
                            <th>Anonim IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice(array_reverse($data), 0, 15) as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['created_at'] ?? '-') ?></td>
                                <td><code><?= htmlspecialchars($row['pageUrl'] ?? '/') ?></code></td>
                                <td><?= htmlspecialchars($row['deviceType'] ?? 'Desktop') ?></td>
                                <td><?= htmlspecialchars($row['language'] ?? '-') ?></td>
                                <td><code><?= htmlspecialchars($row['ipAnonym'] ?? '-') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
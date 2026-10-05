<?php
$db = new PDO('sqlite:analytics.sqlite');

// 1. Összes látogatás
$totalViews = $db->query("SELECT COUNT(*) FROM pageviews")->fetchColumn();

// 2. Mai látogatások
$todayViews = $db->query("SELECT COUNT(*) FROM pageviews WHERE DATE(created_at) = DATE('now')")->fetchColumn();

// 3. Legnépszerűbb oldalak Top 5
$topPages = $db->query("SELECT page_url, COUNT(*) as count FROM pageviews GROUP BY page_url ORDER BY count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Analitika Dashboard</title>
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
            <?php foreach ($topPages as $page): ?>
                <tr>
                    <td><?= htmlspecialchars($page['page_url']) ?></td>
                    <td><?= $page['count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
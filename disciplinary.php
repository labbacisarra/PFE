<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'parent') {
    header("Location: login.php"); exit;
}
$parent_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT * FROM children WHERE parent_id = ?");
$stmt->execute([$parent_id]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_child = $_GET['child_id'] ?? ($children[0]['id'] ?? null);

$records = [];
if ($selected_child) {
    $stmt = $pdo->prepare("SELECT * FROM disciplinary WHERE child_id = ? ORDER BY date_inc DESC");
    $stmt->execute([$selected_child]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplinary Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 2rem; color: #1a1a2e; }
        .page-header p { color: #666; margin-top: 5px; }

        .child-tabs { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .child-tab {
            padding: 8px 20px; border-radius: 20px; border: 2px solid #e0e0e0;
            background: #fff; cursor: pointer; font-family: 'Outfit', sans-serif;
            font-size: 14px; text-decoration: none; color: #555; transition: all 0.2s;
        }
        .child-tab.active, .child-tab:hover { background: #1a1a2e; color: #f5c842; border-color: #1a1a2e; }

        .record-card {
            background: #fff; border-radius: 12px; padding: 20px 24px;
            margin-bottom: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border-left: 5px solid #ccc; display: flex; gap: 20px; align-items: flex-start;
        }
        .record-card.high   { border-left-color: #dc3545; }
        .record-card.medium { border-left-color: #fd7e14; }
        .record-card.low    { border-left-color: #ffc107; }

        .record-icon { font-size: 2rem; min-width: 40px; text-align: center; }
        .record-body { flex: 1; }
        .record-date { font-size: 12px; color: #999; margin-bottom: 6px; }
        .record-desc { color: #333; font-size: 15px; line-height: 1.5; }

        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-top: 10px; display: inline-block; }
        .badge-high   { background: #f8d7da; color: #721c24; }
        .badge-medium { background: #ffe5d0; color: #7d3c0a; }
        .badge-low    { background: #fff3cd; color: #856404; }

        .empty-msg { text-align: center; padding: 60px; color: #999;
                     background: #fff; border-radius: 12px;
                     box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
        .empty-msg i { font-size: 3rem; margin-bottom: 15px; display: block; color: #28a745; }
    </style>
</head>
<body>
    <div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>
    <nav>
        <a href="HomePfe.html" class="logo"></a>
        <p style="color:rgb(131,131,131);font-size:10px;">Platforme Scolaire</p>
        <ul>
            <div class="parent" style="color:#fff; font-size:17px">
                <?= htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']) ?>
            </div><br>
            <p style="color:rgb(131,131,131);font-size:10px;">Suivi Scolaire:</p>
        <li><a href="dashboard.php">🏠 Dashboard</a></li>

            <li><a href="grades.php">📊 Grades & Reports</a></li>
            <li><a href="absences.php">📅 Absences</a></li>
            <li><a href="disciplinary.php" style="color:#f5c842;">⚠️ Disciplinary Records</a></li>
            <li><a href="parent.php">🕐 Timetable</a></li>
            <li><a href="children.php" >👦 My Children</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>

        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>⚠️ Disciplinary Records</h1>
            <p>Stay informed about your child's behavior at school.</p>
        </div>

        <?php if (count($children) > 1): ?>
        <div class="child-tabs">
            <?php foreach ($children as $child): ?>
                <a href="?child_id=<?= $child['id'] ?>"
                   class="child-tab <?= $child['id'] == $selected_child ? 'active' : '' ?>">
                    👦 <?= htmlspecialchars($child['child_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($records)): ?>
            <div class="empty-msg">
                <i class="fas fa-star"></i>
                No disciplinary records. Keep it up! 🌟
            </div>
        <?php else: ?>
            <?php foreach ($records as $r):
                $icon = $r['gravite'] === 'high' ? '🚨' : ($r['gravite'] === 'medium' ? '⚠️' : '📝');
                $label = ucfirst($r['gravite']);
            ?>
            <div class="record-card <?= $r['gravite'] ?>">
                <div class="record-icon"><?= $icon ?></div>
                <div class="record-body">
                    <div class="record-date">📅 <?= date('d/m/Y', strtotime($r['date_inc'])) ?></div>
                    <div class="record-desc"><?= htmlspecialchars($r['description']) ?></div>
                    <span class="badge badge-<?= $r['gravite'] ?>"><?= $label ?> severity</span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        const hamburger = document.getElementById('hamburger');
        const nav = document.querySelector('nav');
        hamburger.addEventListener('click', () => {
            nav.classList.toggle('active');
            const icon = hamburger.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
        document.querySelectorAll('nav ul li a').forEach(link => {
            link.addEventListener('click', () => {
                nav.classList.remove('active');
                const icon = hamburger.querySelector('i');
                icon.classList.add('fa-bars');
                icon.classList.remove('fa-times');
            });
        });
    </script>
</body>
</html>
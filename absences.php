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

$absences = [];
$total = $justified = $unjustified = 0;
if ($selected_child) {
    $stmt = $pdo->prepare("SELECT * FROM absences WHERE child_id = ? ORDER BY date_abs DESC");
    $stmt->execute([$selected_child]);
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($absences);
    $justified = count(array_filter($absences, fn($a) => $a['justifie'] == 1));
    $unjustified = $total - $justified;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absences</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 2rem; color: #1a1a2e; }
        .page-header p { color: #666; margin-top: 5px; }

        .stats-row { display: flex; gap: 16px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-card {
            flex: 1; min-width: 140px; background: #fff;
            border-radius: 12px; padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            text-align: center;
        }
        .stat-card .num { font-size: 2rem; font-weight: 700; }
        .stat-card .lbl { font-size: 13px; color: #888; margin-top: 4px; }
        .stat-total  .num { color: #1a1a2e; }
        .stat-just   .num { color: #28a745; }
        .stat-unjust .num { color: #dc3545; }

        .child-tabs { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .child-tab {
            padding: 8px 20px; border-radius: 20px; border: 2px solid #e0e0e0;
            background: #fff; cursor: pointer; font-family: 'Outfit', sans-serif;
            font-size: 14px; text-decoration: none; color: #555; transition: all 0.2s;
        }
        .child-tab.active, .child-tab:hover { background: #1a1a2e; color: #f5c842; border-color: #1a1a2e; }

        table { width: 100%; border-collapse: collapse; background: #fff;
                border-radius: 12px; overflow: hidden;
                box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
        th { background: #1a1a2e; color: #f5c842; padding: 14px 16px; text-align: left; font-weight: 500; }
        td { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; color: #333; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f9f9f9; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-just   { background: #d4edda; color: #155724; }
        .badge-unjust { background: #f8d7da; color: #721c24; }

        .empty-msg { text-align: center; padding: 60px; color: #999;
                     background: #fff; border-radius: 12px;
                     box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
        .empty-msg i { font-size: 3rem; margin-bottom: 15px; display: block; color: #28a745; }
    </style>
</head>
<body>
    <div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>
    <nav>
        <a href="index.html" class="logo"></a>
        <p style="color:rgb(131,131,131);font-size:10px;">School Platform</p>
        <ul>
            <div class="parent" style="color:#fff; font-size:17px">
                <?= htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']) ?>
            </div><br>
            <p style="color:rgb(131,131,131);font-size:10px;">Academic Monitoring:</p>
            <li><a href="dashboard.php" >🏠 Dashboard</a></li>
            <li><a href="grades.php">📊 Grades & Reports</a></li>
            <li><a href="absences.php" style="color:#f5c842;">📅 Absences</a></li>
            <li><a href="disciplinary.php">⚠️ Disciplinary Records</a></li>
            <li><a href="parent.php">🕐 Timetable</a></li>
            <li><a href="children.php">👦 My Children</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>

        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>📅 Absences</h1>
            <p>Monitor your child's attendance record.</p>
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

        <div class="stats-row">
            <div class="stat-card stat-total">
                <div class="num"><?= $total ?></div>
                <div class="lbl">Total Absences</div>
            </div>
            <div class="stat-card stat-just">
                <div class="num"><?= $justified ?></div>
                <div class="lbl">Justified</div>
            </div>
            <div class="stat-card stat-unjust">
                <div class="num"><?= $unjustified ?></div>
                <div class="lbl">Unjustified</div>
            </div>
        </div>

        <?php if (empty($absences)): ?>
            <div class="empty-msg">
                <i class="fas fa-check-circle"></i>
                No absences recorded. Great attendance! 🎉
            </div>
        <?php else: ?>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($absences as $a): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($a['date_abs'])) ?></td>
                    <td><?= htmlspecialchars($a['motif'] ?: '—') ?></td>
                    <td>
                        <span class="badge <?= $a['justifie'] ? 'badge-just' : 'badge-unjust' ?>">
                            <?= $a['justifie'] ? '✅ Justified' : '❌ Unjustified' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
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

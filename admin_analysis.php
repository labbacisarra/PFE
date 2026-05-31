<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$struggling = $pdo->query("
    SELECT c.child_name, c.classe, ROUND(AVG(g.note),2) as avg,
           COUNT(g.id) as total_grades
    FROM grades g
    JOIN children c ON c.id = g.child_id
    GROUP BY g.child_id
    HAVING avg < 5
    ORDER BY avg ASC
")->fetchAll(PDO::FETCH_ASSOC);

$top_students = $pdo->query("
    SELECT c.child_name, c.classe, ROUND(AVG(g.note),2) as avg,
           COUNT(g.id) as total_grades
    FROM grades g
    JOIN children c ON c.id = g.child_id
    GROUP BY g.child_id
    HAVING avg >= 7
    ORDER BY avg DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$all_avg = $pdo->query("
    SELECT c.child_name, c.classe, ROUND(AVG(g.note),2) as avg
    FROM grades g
    JOIN children c ON c.id = g.child_id
    GROUP BY g.child_id
    ORDER BY c.classe, avg DESC
")->fetchAll(PDO::FETCH_ASSOC);

$absent_alert = $pdo->query("
    SELECT c.child_name, c.classe, COUNT(a.id) as total
    FROM absences a
    JOIN children c ON c.id = a.child_id
    GROUP BY a.child_id
    HAVING total >= 5
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$avg_by_classe = $pdo->query("
    SELECT c.classe, ROUND(AVG(g.note),2) as avg
    FROM grades g
    JOIN children c ON c.id = g.child_id
    WHERE c.classe IS NOT NULL
    GROUP BY c.classe
    ORDER BY avg DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analysis — Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 2rem; color: #0f1f3d; }
        .page-header p { color: #666; margin-top: 5px; }

        .section-title {
            font-size: 1.1rem; font-weight: 600; color: #0f1f3d;
            margin: 28px 0 16px; display: flex; align-items: center; gap: 10px;
            padding-bottom: 10px; border-bottom: 2px solid #f5c842;
        }

        table { width: 100%; border-collapse: collapse; background: #fff;
                border-radius: 12px; overflow: hidden; margin-bottom: 28px; }
        th { background: #0f1f3d; color: #fff; padding: 12px; font-size: 13px; text-align: center; }
        td { padding: 10px; font-size: 13px; text-align: center;
             border: 1px solid #e2e8f0; color: #1a2540; }
        tr:hover td { background: #f8fafc; }

        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-good { background: #d4edda; color: #155724; }
        .badge-avg  { background: #fff3cd; color: #856404; }
        .badge-bad  { background: #f8d7da; color: #721c24; }

        .alert-card {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .alert-card i { font-size: 1.8rem; color: #856404; }
        .alert-card div strong { color: #0f1f3d; display: block; font-size: 15px; }
        .alert-card div span { font-size: 13px; color: #666; }

        .chart-card {
            background: #fff; border-radius: 16px; padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 28px;
        }
        .chart-card h3 {
            color: #0f1f3d; font-size: 1rem; margin-bottom: 18px;
            padding-bottom: 10px; border-bottom: 2px solid #f5c842;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px,1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .summary-card {
            background: #fff; border-radius: 14px; padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            text-align: center; border-top: 4px solid #f5c842;
        }
        .summary-card .num { font-size: 1.8rem; font-weight: 700; color: #0f1f3d; }
        .summary-card .lbl { font-size: 12px; color: #888; margin-top: 4px; }
    </style>
</head>
<body>
<div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>

<nav>
    <a href="index.html" class="logo"></a>
    <p style="color:rgb(131,131,131);font-size:10px;">School Platform</p>
    <ul>
        <div style="color:#fff;font-size:17px;">
            👤 <?= htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']) ?>
        </div><br>
        <p style="color:rgb(131,131,131);font-size:10px;">Administration:</p>
        <li><a href="admin.php">🏠 Dashboard</a></li>
        <li><a href="admin_users.php">👥 Users</a></li>
        <li><a href="admin_timetable.php">🕐 Timetable</a></li>
        <li><a href="admin_inscriptions.php">📋 Inscriptions</a></li>
        <li><a href="admin_statistics.php">📊 Statistics</a></li>
        <li><a href="admin_analysis.php" style="color:#f5c842;">🔍 Analysis</a></li>
        <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="page-header">
        <h1>🔍 Student Performance Analysis</h1>
        <p>Detailed analysis of grades, absences, and student performance.</p>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="num" style="color:#dc3545;"><?= count($struggling) ?></div>
            <div class="lbl">🔴 Students in Difficulty</div>
        </div>
        <div class="summary-card">
            <div class="num" style="color:#28a745;"><?= count($top_students) ?></div>
            <div class="lbl">🟢 Top Students</div>
        </div>
        <div class="summary-card">
            <div class="num" style="color:#856404;"><?= count($absent_alert) ?></div>
            <div class="lbl">🟡 Absence Alerts</div>
        </div>
        <div class="summary-card">
            <div class="num"><?= count($all_avg) ?></div>
            <div class="lbl">📊 Students with Grades</div>
        </div>
    </div>

    <?php if (!empty($struggling)): ?>
    <div class="alert-card">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>⚠️ <?= count($struggling) ?> student(s) in difficulty!</strong>
            <span>Average below 5/10 — immediate attention required.</span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($absent_alert)): ?>
    <div class="alert-card" style="background:#f8d7da;border-color:#dc3545;">
        <i class="fas fa-calendar-times" style="color:#721c24;"></i>
        <div>
            <strong>🔴 <?= count($absent_alert) ?> student(s) with excessive absences!</strong>
            <span>5 or more absences recorded.</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="chart-card">
        <h3>📊 Average Grade per Class</h3>
        <canvas id="avgClasseChart" height="80"></canvas>
    </div>

    <div class="section-title">🔴 Students in Difficulty (avg &lt; 5/10)</div>
    <?php if (empty($struggling)): ?>
        <p style="color:#28a745;font-weight:600;margin-bottom:20px;">✅ No students in difficulty!</p>
    <?php else: ?>
    <table>
        <tr><th>Student</th><th>Class</th><th>Average / 10</th><th>Grades</th><th>Status</th></tr>
        <?php foreach ($struggling as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['child_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['classe'] ?? '—') ?></td>
            <td><strong style="color:#dc3545;"><?= $r['avg'] ?></strong> / 10</td>
            <td><?= $r['total_grades'] ?></td>
            <td><span class="badge badge-bad">🔴 In Difficulty</span></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <div class="section-title">🟢 Top Students (avg ≥ 7/10)</div>
    <?php if (empty($top_students)): ?>
        <p style="color:#888;margin-bottom:20px;">No top students yet.</p>
    <?php else: ?>
    <table>
        <tr><th>#</th><th>Student</th><th>Class</th><th>Average / 10</th><th>Status</th></tr>
        <?php foreach ($top_students as $i => $r): ?>
        <tr>
            <td><?= $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : $i+1)) ?></td>
            <td><?= htmlspecialchars($r['child_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['classe'] ?? '—') ?></td>
            <td><strong style="color:#28a745;"><?= $r['avg'] ?></strong> / 10</td>
            <td><span class="badge badge-good">🟢 Excellent</span></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <div class="section-title">⚠️ Absence Alerts (≥ 5 absences)</div>
    <?php if (empty($absent_alert)): ?>
        <p style="color:#28a745;font-weight:600;margin-bottom:20px;">✅ No absence alerts!</p>
    <?php else: ?>
    <table>
        <tr><th>Student</th><th>Class</th><th>Absences</th><th>Alert Level</th></tr>
        <?php foreach ($absent_alert as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['child_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['classe'] ?? '—') ?></td>
            <td><strong><?= $r['total'] ?></strong></td>
            <td>
                <span class="badge <?= $r['total'] >= 10 ? 'badge-bad' : 'badge-avg' ?>">
                    <?= $r['total'] >= 10 ? '🔴 Critical' : '🟡 Warning' ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <div class="section-title">📋 All Students — Performance Overview</div>
    <table>
        <tr><th>Student</th><th>Class</th><th>Average / 10</th><th>Performance</th></tr>
        <?php foreach ($all_avg as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['child_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['classe'] ?? '—') ?></td>
            <td><strong><?= $r['avg'] ?></strong> / 10</td>
            <td>
                <span class="badge <?= $r['avg'] >= 7 ? 'badge-good' : ($r['avg'] >= 5 ? 'badge-avg' : 'badge-bad') ?>">
                    <?= $r['avg'] >= 7 ? '🟢 Good' : ($r['avg'] >= 5 ? '🟡 Average' : '🔴 Weak') ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>

const labels = <?= json_encode(array_column($avg_by_classe, 'classe')) ?>;
const data   = <?= json_encode(array_column($avg_by_classe, 'avg')) ?>;
const colors = data.map(v => v >= 7 ? '#28a745' : (v >= 5 ? '#ffc107' : '#dc3545'));

new Chart(document.getElementById('avgClasseChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Average / 10',
            data,
            backgroundColor: colors,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        scales: { y: { max: 10, beginAtZero: true } },
        plugins: { legend: { display: false } }
    }
});


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

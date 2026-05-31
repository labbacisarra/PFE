<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

// ── إحصائيات عامة ──
$total_users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_parents  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn();
$total_teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='enseignant'")->fetchColumn();
$total_children = $pdo->query("SELECT COUNT(*) FROM children WHERE status='active'")->fetchColumn();
$total_absences = $pdo->query("SELECT COUNT(*) FROM absences")->fetchColumn();
$total_grades   = $pdo->query("SELECT COUNT(*) FROM grades")->fetchColumn();
$pending        = $pdo->query("SELECT COUNT(*) FROM children WHERE status='pending'")->fetchColumn();

// ── طلاب حسب القسم ──
$by_classe = $pdo->query("
    SELECT classe, COUNT(*) as total 
    FROM children 
    WHERE status='active' AND classe IS NOT NULL AND classe != ''
    GROUP BY classe 
    ORDER BY classe
")->fetchAll(PDO::FETCH_ASSOC);

// ── غيابات حسب القسم ──
$absences_by_classe = $pdo->query("
    SELECT c.classe, COUNT(a.id) as total
    FROM absences a
    JOIN children c ON c.id = a.child_id
    WHERE c.classe IS NOT NULL
    GROUP BY c.classe
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ── أعلى 5 طلاب غياباً ──
$top_absences = $pdo->query("
    SELECT c.child_name, c.classe, COUNT(a.id) as total
    FROM absences a
    JOIN children c ON c.id = a.child_id
    GROUP BY a.child_id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ── معدل النقاط حسب المادة ──
$avg_by_matiere = $pdo->query("
    SELECT matiere, ROUND(AVG(note),2) as avg_note, COUNT(*) as total
    FROM grades
    GROUP BY matiere
    ORDER BY avg_note DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics — Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 2rem; color: #0f1f3d; }
        .page-header p { color: #666; margin-top: 5px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border-top: 4px solid #f5c842;
            text-align: center;
        }
        .stat-card i { font-size: 1.8rem; color: #0f1f3d; margin-bottom: 8px; display: block; }
        .stat-card .num { font-size: 2rem; font-weight: 700; color: #0f1f3d; }
        .stat-card .lbl { font-size: 12px; color: #888; margin-top: 4px; }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        .chart-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .chart-card h3 {
            color: #0f1f3d;
            font-size: 1rem;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f5c842;
        }

        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; }
        th { background: #0f1f3d; color: #fff; padding: 12px; font-size: 13px; text-align: center; }
        td { padding: 10px; font-size: 13px; text-align: center; border: 1px solid #e2e8f0; color: #1a2540; }
        tr:hover td { background: #f8fafc; }

        .section-title {
            font-size: 1.1rem; font-weight: 600; color: #0f1f3d;
            margin: 28px 0 16px; display: flex; align-items: center; gap: 10px;
            padding-bottom: 10px; border-bottom: 2px solid #f5c842;
        }

        .badge { padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-good { background: #d4edda; color: #155724; }
        .badge-avg  { background: #fff3cd; color: #856404; }
        .badge-bad  { background: #f8d7da; color: #721c24; }

        .progress-bar {
            background: #f0f0f0;
            border-radius: 10px;
            height: 8px;
            margin-top: 4px;
        }
        .progress-fill {
            height: 8px;
            border-radius: 10px;
            background: linear-gradient(to right, #f5c842, #0f1f3d);
        }
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
        <li><a href="admin_statistics.php" style="color:#f5c842;">📊 Statistics</a></li>
        <li><a href="admin_analysis.php">🔍 Analysis</a></li>
        <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="page-header">
        <h1>📊 Global Statistics</h1>
        <p>Overview of all school data and performance indicators.</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <div class="num"><?= $total_users ?></div>
            <div class="lbl">Total Users</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-user-friends"></i>
            <div class="num"><?= $total_parents ?></div>
            <div class="lbl">Parents</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-chalkboard-teacher"></i>
            <div class="num"><?= $total_teachers ?></div>
            <div class="lbl">Teachers</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-child"></i>
            <div class="num"><?= $total_children ?></div>
            <div class="lbl">Active Students</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-calendar-times"></i>
            <div class="num"><?= $total_absences ?></div>
            <div class="lbl">Total Absences</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-star"></i>
            <div class="num"><?= $total_grades ?></div>
            <div class="lbl">Grades Recorded</div>
        </div>
        <div class="stat-card" style="border-top-color:#ffc107;">
            <i class="fas fa-hourglass-half" style="color:#856404;"></i>
            <div class="num" style="color:#856404;"><?= $pending ?></div>
            <div class="lbl">Pending Inscriptions</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3>🏫 Students per Class</h3>
            <canvas id="classeChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>📅 Absences per Class</h3>
            <canvas id="absenceChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>📚 Average Grade per Subject</h3>
            <canvas id="matiereChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>👥 User Distribution</h3>
            <canvas id="userChart"></canvas>
        </div>
    </div>

    <!-- Top Absences Table -->
    <div class="section-title">⚠️ Top 5 Students with Most Absences</div>
    <table style="margin-bottom:32px;">
        <tr>
            <th>Student</th>
            <th>Class</th>
            <th>Absences</th>
            <th>Status</th>
        </tr>
        <?php foreach ($top_absences as $i => $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['child_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['classe'] ?? '—') ?></td>
            <td><strong><?= $row['total'] ?></strong></td>
            <td>
                <span class="badge <?= $row['total'] >= 10 ? 'badge-bad' : ($row['total'] >= 5 ? 'badge-avg' : 'badge-good') ?>">
                    <?= $row['total'] >= 10 ? '🔴 Critical' : ($row['total'] >= 5 ? '🟡 Warning' : '🟢 OK') ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Average by Subject Table -->
    <div class="section-title">📚 Average Grade per Subject</div>
    <table>
        <tr>
            <th>Subject</th>
            <th>Average / 10</th>
            <th>Total Grades</th>
            <th>Progress</th>
            <th>Status</th>
        </tr>
        <?php foreach ($avg_by_matiere as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['matiere']) ?></td>
            <td><strong><?= $row['avg_note'] ?></strong> / 10</td>
            <td><?= $row['total'] ?></td>
            <td style="min-width:120px;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= ($row['avg_note']/10)*100 ?>%"></div>
                </div>
            </td>
            <td>
                <span class="badge <?= $row['avg_note'] >= 7 ? 'badge-good' : ($row['avg_note'] >= 5 ? 'badge-avg' : 'badge-bad') ?>">
                    <?= $row['avg_note'] >= 7 ? 'Good' : ($row['avg_note'] >= 5 ? 'Average' : 'Weak') ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
// Students per Class
const classeLabels = <?= json_encode(array_column($by_classe, 'classe')) ?>;
const classeData   = <?= json_encode(array_column($by_classe, 'total')) ?>;
new Chart(document.getElementById('classeChart'), {
    type: 'bar',
    data: {
        labels: classeLabels,
        datasets: [{ label: 'Students', data: classeData,
            backgroundColor: '#0f1f3d', borderRadius: 8 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// Absences per Class
const absLabels = <?= json_encode(array_column($absences_by_classe, 'classe')) ?>;
const absData   = <?= json_encode(array_column($absences_by_classe, 'total')) ?>;
new Chart(document.getElementById('absenceChart'), {
    type: 'bar',
    data: {
        labels: absLabels,
        datasets: [{ label: 'Absences', data: absData,
            backgroundColor: '#f5c842', borderRadius: 8 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// Average per Subject
const matLabels = <?= json_encode(array_column($avg_by_matiere, 'matiere')) ?>;
const matData   = <?= json_encode(array_column($avg_by_matiere, 'avg_note')) ?>;
new Chart(document.getElementById('matiereChart'), {
    type: 'horizontalBar',
    data: {
        labels: matLabels,
        datasets: [{ label: 'Average', data: matData,
            backgroundColor: '#3b5bdb', borderRadius: 6 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } },
        scales: { x: { max: 10 } } }
});

// User Distribution
new Chart(document.getElementById('userChart'), {
    type: 'doughnut',
    data: {
        labels: ['Parents', 'Teachers', 'Admins'],
        datasets: [{ data: [<?= $total_parents ?>, <?= $total_teachers ?>, <?= $total_users - $total_parents - $total_teachers ?>],
            backgroundColor: ['#0f1f3d', '#f5c842', '#3b5bdb'] }]
    },
    options: { responsive: true }
});

// Hamburger
const hamburger = document.getElementById('hamburger');
const nav = document.querySelector('nav');
hamburger.addEventListener('click', () => {
    nav.classList.toggle('active');
    const icon = hamburger.querySelector('i');
    icon.classList.toggle('fa-bars');
    icon.classList.toggle('fa-times');
});
</script>
</body>
</html>
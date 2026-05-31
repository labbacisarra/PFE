<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'enseignant') {
    header('Location: login.php');
    exit;
}

$teacher      = $_SESSION['user'];
$teacher_name = $teacher['first_name'] . ' ' . $teacher['last_name'];

// ── classes depuis emploi_temps ──
$stmt    = $pdo->query("SELECT DISTINCT classe FROM emploi_temps ORDER BY classe");
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ── total élèves actifs ──
$totalStudents = 0;
if (!empty($classes)) {
    $ph   = implode(',', array_fill(0, count($classes), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM children WHERE classe IN ($ph) AND status='active'");
    $stmt->execute($classes);
    $totalStudents = $stmt->fetchColumn();
}

// ── absences aujourd'hui ──
$today       = date('Y-m-d');
$absentToday = 0;
if (!empty($classes)) {
    $ph   = implode(',', array_fill(0, count($classes), '?'));
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT a.child_id)
        FROM absences a
        JOIN children c ON a.child_id = c.id
        WHERE a.date_abs = ? AND c.classe IN ($ph) AND c.status='active'
    ");
    $stmt->execute(array_merge([$today], $classes));
    $absentToday = $stmt->fetchColumn();
}
$presentToday = $totalStudents - $absentToday;

// ── moyenne générale ──
$classAvg = '--';
if (!empty($classes)) {
    $ph   = implode(',', array_fill(0, count($classes), '?'));
    $stmt = $pdo->prepare("
        SELECT ROUND(AVG(g.note), 1)
        FROM grades g
        JOIN children c ON g.child_id = c.id
        WHERE c.classe IN ($ph) AND c.status='active'
    ");
    $stmt->execute($classes);
    $avg = $stmt->fetchColumn();
    if ($avg) $classAvg = $avg;
}

// ── total absences ──
$totalAbsences = 0;
if (!empty($classes)) {
    $ph   = implode(',', array_fill(0, count($classes), '?'));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM absences a
        JOIN children c ON a.child_id = c.id
        WHERE c.classe IN ($ph) AND c.status='active'
    ");
    $stmt->execute($classes);
    $totalAbsences = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard – ECOLNA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border-top: 4px solid #f5c842;
            text-align: center;
        }
        .stat-card i   { font-size: 2rem; color: #0f1f3d; margin-bottom: 10px; }
        .stat-card .num { font-size: 2rem; font-weight: 700; color: #0f1f3d; }
        .stat-card .lbl { font-size: 13px; color: #888; margin-top: 4px; }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        .quick-card {
            background: #0f1f3d;
            border-radius: 16px;
            padding: 28px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.2s, opacity 0.2s;
        }
        .quick-card:hover { transform: translateY(-4px); opacity: 0.9; }
        .quick-card i    { font-size: 2.5rem; color: #f5c842; margin-bottom: 12px; display: block; }
        .quick-card span { color: #fff; font-size: 16px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>
    <nav>
        <a href="index.html" class="logo"></a>
        <p style="color:rgb(131,131,131);font-size:10px;">School Platform</p>
        <ul>
            <div style="color:#fff; font-size:17px;">👨‍🏫 <?= htmlspecialchars($teacher_name) ?></div><br>
            <p style="color:rgb(131,131,131);font-size:10px;">Classroom:</p>
            <li><a href="enseignant.php" style="color:#f5c842;">🏠 Dashboard</a></li>
            <li><a href="teacher_attendance.php">📅 Mark Attendance</a></li>
            <li><a href="teacher_students.php" >👥 Student List</a></li>
            <li><a href="teacher_grades.php" >⭐ Manage Grades</a></li>
            <li><a href="teacher_disciplinary.php" >⚠️ Disciplinary</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>🏫 Teacher Dashboard</h1>
        <p>Welcome back <?= htmlspecialchars($teacher['first_name']) ?>, here's your summary for today — <?= date('d/m/Y') ?></p>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="num"><?= $totalStudents ?></div>
                <div class="lbl">Total Students</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <div class="num"><?= $presentToday ?></div>
                <div class="lbl">Present Today</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-times"></i>
                <div class="num"><?= $absentToday ?></div>
                <div class="lbl">Absent Today</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-star"></i>
                <div class="num"><?= $classAvg ?></div>
                <div class="lbl">Avg. Grade /20</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-times"></i>
                <div class="num"><?= $totalAbsences ?></div>
                <div class="lbl">Total Absences</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 style="color:#0f1f3d;margin-bottom:16px;">Quick Actions</h2>
        <div class="quick-links">
            <a href="teacher_attendance.php" class="quick-card">
                <i class="fas fa-calendar-check"></i>
                <span>Mark Attendance</span>
            </a>
            <a href="teacher_students.php" class="quick-card">
                <i class="fas fa-users"></i>
                <span>Student List</span>
            </a>
            <a href="teacher_grades.php" class="quick-card">
                <i class="fas fa-star"></i>
                <span>Manage Grades</span>
            </a>
            <a href="teacher_disciplinary.php" class="quick-card">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Disciplinary</span>
            </a>
        </div>
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

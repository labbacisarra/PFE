<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

// إحصائيات
$total_users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_parents  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn();
$total_teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='enseignant'")->fetchColumn();
$total_children = $pdo->query("SELECT COUNT(*) FROM children")->fetchColumn();
$total_absences = $pdo->query("SELECT COUNT(*) FROM absences")->fetchColumn();
$pending        = $pdo->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        .stat-card i { font-size: 2rem; color: #0f1f3d; margin-bottom: 10px; }
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
        .quick-card i { font-size: 2.5rem; color: #f5c842; margin-bottom: 12px; display: block; }
        .quick-card span { color: #fff; font-size: 16px; font-weight: 600; }

        .admin-name { color: #fff; font-size: 17px; }
    </style>
</head>
<body>
    <div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>
    <nav>
        <a href="HomePfe.html" class="logo"></a>
        <p style="color:rgb(131,131,131);font-size:10px;">Platforme Scolaire</p>
        <ul>
            <div class="admin-name">
                👤 <?= htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']) ?>
            </div><br>
            <p style="color:rgb(131,131,131);font-size:10px;">Administration:</p>
            <li><a href="admin.php" style="color:#f5c842;">🏠 Dashboard</a></li>
            <li><a href="admin_users.php">👥 Users</a></li>
            <li><a href="admin_timetable.php">🕐 Timetable</a></li>
            <li><a href="admin_inscriptions.php">📋 Inscriptions</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>🛡️ Admin Dashboard</h1>
        <p>Welcome back, manage everything from here.</p>

        <!-- إحصائيات -->
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
                <div class="lbl">Children</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-times"></i>
                <div class="num"><?= $total_absences ?></div>
                <div class="lbl">Absences</div>
            </div>
        </div>

        <!-- روابط سريعة -->
        <h2 style="color:#0f1f3d;margin-bottom:16px;">Quick Actions</h2>
        <div class="quick-links">
            <a href="admin_users.php" class="quick-card">
                <i class="fas fa-users-cog"></i>
                <span>Manage Users</span>
            </a>
            <a href="admin_timetable.php" class="quick-card">
                <i class="fas fa-calendar-alt"></i>
                <span>Manage Timetable</span>
            </a>
            <a href="admin_inscriptions.php" class="quick-card">
                <i class="fas fa-clipboard-list"></i>
                <span>Manage Inscriptions</span>
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
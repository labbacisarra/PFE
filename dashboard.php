<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'parent') {
    header("Location: login.php"); exit;
}

$parent_id   = $_SESSION['user']['id'];
$first_name  = $_SESSION['user']['first_name'];
$last_name   = $_SESSION['user']['last_name'];

$stmt = $pdo->prepare("SELECT * FROM children WHERE parent_id = ? ORDER BY id");
$stmt->execute([$parent_id]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total    = count($children);
$active   = count(array_filter($children, fn($c) => $c['status'] === 'active'));
$pending  = count(array_filter($children, fn($c) => $c['status'] === 'pending'));
$rejected = count(array_filter($children, fn($c) => $c['status'] === 'rejected'));

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard — ECOLNA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>

        .welcome-banner {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            border-radius: 20px;
            padding: 32px 36px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .welcome-left h1 {
            font-size: 1.8rem;
            color: #fff;
            margin-bottom: 6px;
        }
        .welcome-left h1 span { color: #f5c842; }
        .welcome-left p { color: #aab4c8; font-size: 14px; }
        .welcome-avatar {
            width: 72px; height: 72px;
            background: rgba(245,200,66,0.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem;
            border: 2px solid rgba(245,200,66,0.4);
        }


        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            display: flex; align-items: center; gap: 12px;
        }
        .stat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .stat-icon.total    { background: #e8f4fd; }
        .stat-icon.active   { background: #d4edda; }
        .stat-icon.pending  { background: #fff3cd; }
        .stat-icon.rejected { background: #f8d7da; }
        .stat-info strong { font-size: 1.4rem; color: #1a1a2e; display: block; line-height: 1.1; }
        .stat-info span   { font-size: 11px; color: #888; }


        .section-title {
            font-size: 1.05rem; font-weight: 600; color: #0f1f3d;
            margin: 0 0 16px;
            display: flex; align-items: center; gap: 8px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f5c842;
        }


        .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 32px;
        }
        .quick-card {
            background: #fff;
            border-radius: 16px;
            padding: 22px 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            text-align: center;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            border-bottom: 3px solid transparent;
        }
        .quick-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border-bottom-color: #f5c842;
        }
        .quick-card .icon {
            font-size: 2rem; margin-bottom: 10px; display: block;
        }
        .quick-card .label {
            font-size: 13px; font-weight: 600; color: #1a1a2e;
        }
        .quick-card .desc {
            font-size: 11px; color: #aaa; margin-top: 4px;
        }


        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 10px;
        }
        .child-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border-left: 5px solid #ffc107;
            display: flex; align-items: center; gap: 16px;
            transition: transform 0.2s;
        }
        .child-card.active   { border-left-color: #28a745; }
        .child-card.rejected { border-left-color: #dc3545; }
        .child-card.pending  { border-left-color: #ffc107; }
        .child-card:hover { transform: translateY(-2px); }

        .child-avatar {
            width: 54px; height: 54px;
            background: #fff8e1;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.7rem;
            border: 2px solid #f5c842;
            flex-shrink: 0;
        }
        .child-info { flex: 1; }
        .child-name { font-size: 1rem; font-weight: 700; color: #1a1a2e; }
        .child-niveau { font-size: 12px; color: #888; margin-top: 2px; }

        .status-badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600; margin-top: 6px;
        }
        .badge-pending  { background: #fff3cd; color: #856404; }
        .badge-active   { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }

        .child-action {
            font-size: 11px; color: #0369a1;
            text-decoration: none; margin-top: 6px; display: inline-block;
        }
        .child-action:hover { text-decoration: underline; }


        .empty-msg {
            text-align: center; padding: 40px; color: #aaa;
            background: #fff; border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .empty-msg i { font-size: 2.5rem; display: block; margin-bottom: 10px; color: #f5c842; }
        .empty-msg a {
            display: inline-block; margin-top: 12px;
            padding: 10px 22px; background: #1a1a2e; color: #f5c842;
            border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 600;
        }
    </style>
</head>
<body>

<div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>
<nav>
        <a href="index.html" class="logo"></a>
    <p style="color:rgb(131,131,131);font-size:10px;">School Platform</p>
    <ul>
        <div class="parent" style="color:#fff;font-size:17px;">
            <?= htmlspecialchars($first_name . ' ' . $last_name) ?>
        </div><br>
        <p style="color:rgb(131,131,131);font-size:10px;">Academic Monitoring::</p>
        <li><a href="dashboard.php" style="color:#f5c842;">🏠 Dashboard</a></li>
        <li><a href="grades.php">📊 Grades & Reports</a></li>
        <li><a href="absences.php">📅 Absences</a></li>
        <li><a href="disciplinary.php">⚠️ Disciplinary Records</a></li>
        <li><a href="parent.php">🕐 Timetable</a></li>
        <li><a href="children.php">👦 My Children</a></li>
        <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">


    <div class="welcome-banner">
        <div class="welcome-left">
            <h1><?= $greeting ?>, <span><?= htmlspecialchars($first_name) ?>!</span> 👋</h1>
            <p>Welcome to ECOLNA — Stay close to your child's journey.</p>
            <p style="margin-top:6px;color:#6b7fa3;font-size:13px;">
                <i class="fas fa-calendar-alt"></i>
                <?= date('l, d F Y') ?>
            </p>
        </div>
        <div class="welcome-avatar">👨‍👩‍👧</div>
    </div>


    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon total">👦</div>
            <div class="stat-info">
                <strong><?= $total ?></strong>
                <span>Total Children</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon active">✅</div>
            <div class="stat-info">
                <strong><?= $active ?></strong>
                <span>Approved</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">⏳</div>
            <div class="stat-info">
                <strong><?= $pending ?></strong>
                <span>Pending</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rejected">❌</div>
            <div class="stat-info">
                <strong><?= $rejected ?></strong>
                <span>Rejected</span>
            </div>
        </div>
    </div>


    <div class="section-title">
        <i class="fas fa-bolt"></i> Quick Access
    </div>
    <div class="quick-grid">
        <a href="grades.php" class="quick-card">
            <span class="icon">📊</span>
            <div class="label">Grades & Reports</div>
            <div class="desc">View your child's grades</div>
        </a>
        <a href="absences.php" class="quick-card">
            <span class="icon">📅</span>
            <div class="label">Absences</div>
            <div class="desc">Track attendance</div>
        </a>
        <a href="disciplinary.php" class="quick-card">
            <span class="icon">⚠️</span>
            <div class="label">Disciplinary</div>
            <div class="desc">Disciplinary records</div>
        </a>
        <a href="parent.php" class="quick-card">
            <span class="icon">🕐</span>
            <div class="label">Timetable</div>
            <div class="desc">Weekly schedule</div>
        </a>
        <a href="children.php" class="quick-card">
            <span class="icon">👦</span>
            <div class="label">My Children</div>
            <div class="desc">Manage records</div>
        </a>
    </div>


    <div class="section-title">
        <i class="fas fa-users"></i> My Children
    </div>

    <?php if (empty($children)): ?>
        <div class="empty-msg">
            <i class="fas fa-child"></i>
            <p>No children added yet.</p>
            <a href="children.php"><i class="fas fa-plus"></i> Add a Child</a>
        </div>
    <?php else: ?>
        <div class="children-grid">
            <?php foreach ($children as $child):
                $status = $child['status'] ?? 'pending';
                $badge  = $status === 'active' ? '✅ Approved' : ($status === 'rejected' ? '❌ Rejected' : '⏳ Pending');
                $cls    = 'badge-' . $status;
            ?>
            <div class="child-card <?= $status ?>">
                <div class="child-avatar">👦</div>
                <div class="child-info">
                    <div class="child-name">
                        <?= htmlspecialchars($child['child_name'] . ' ' . ($child['prenom'] ?? '')) ?>
                    </div>
                    <div class="child-niveau">
                        <?= htmlspecialchars($child['niveau'] ?? '—') ?>
                    </div>
                    <span class="status-badge <?= $cls ?>"><?= $badge ?></span><br>
                    <a href="children.php" class="child-action">
                        <i class="fas fa-edit"></i> Manage
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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

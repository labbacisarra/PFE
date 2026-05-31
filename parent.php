<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'parent') {
    header("Location: login.php"); exit;
}

$parent_id = $_SESSION['user']['id'];
$parent_name = htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']);

$jours  = ['Sunday','Monday','Tuesday','Wednesday','Thursday'];
$heures = [
    ['08:00','09:00'],['09:00','10:00'],['10:00','11:00'],
    ['11:00','12:00'],['14:00','15:00'],['15:00','16:00']
];

$stmt = $pdo->prepare("
    SELECT * FROM children 
    WHERE parent_id = ? AND status = 'active' 
    ORDER BY child_name
");
$stmt->execute([$parent_id]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_child_id = $_GET['child_id'] ?? ($children[0]['id'] ?? null);
$selected_child    = null;
foreach ($children as $c) {
    if ($c['id'] == $selected_child_id) {
        $selected_child = $c;
        break;
    }
}

$timetable = [];
if ($selected_child && !empty($selected_child['classe'])) {
    $stmt = $pdo->prepare("
        SELECT * FROM emploi_temps 
        WHERE classe = ? 
        ORDER BY heure_debut
    ");
    $stmt->execute([trim($selected_child['classe'])]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $timetable[$r['heure_debut']][$r['jour']] = $r['matiere'];
    }
}

$colors = [
    'Mathematics'     => '#dbeafe',
    'Arabic'          => '#fce7f3',
    'French'          => '#dcfce7',
    'English'         => '#fef9c3',
    'Physics'         => '#ede9fe',
    'Chemistry'       => '#ffedd5',
    'History'         => '#f0fdf4',
    'Geography'       => '#ecfeff',
    'Philosophy'      => '#fdf4ff',
    'Informatique'    => '#e0f2fe',
    'Islamic Studies' => '#fefce8',
    'Sport'           => '#fff1f2',
];
function getCouleur($matiere, $colors) {
    foreach ($colors as $key => $color) {
        if (stripos($matiere, $key) !== false) return $color;
    }
    return '#f1f5f9';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable — Parent</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        .page-header { margin-bottom: 26px; }
        .page-header h1 { font-size: 2rem; color: #1a1a2e; }
        .page-header p  { color: #666; margin-top: 5px; font-size: 14px; }

        .child-tabs { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
        .child-tab {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 20px;
            border: 2px solid #e0e0e0; background: #fff;
            text-decoration: none; color: #555;
            font-family: 'Outfit', sans-serif; font-size: 14px;
            transition: all 0.2s;
        }
        .child-tab.active, .child-tab:hover {
            background: #1a1a2e; color: #f5c842; border-color: #1a1a2e;
        }

        .info-card {
            background: linear-gradient(135deg, #1a1a2e, #2d2d5e);
            border-radius: 16px; padding: 20px 24px;
            margin-bottom: 24px; color: #fff;
            display: flex; align-items: center; gap: 18px;
            box-shadow: 0 4px 16px rgba(26,26,46,0.2);
        }
        .info-card .avatar {
            width: 56px; height: 56px;
            background: rgba(245,200,66,0.2);
            border-radius: 50%; display: flex;
            align-items: center; justify-content: center;
            font-size: 1.8rem; border: 2px solid #f5c842;
            flex-shrink: 0;
        }
        .info-card h3 { font-size: 1.1rem; margin: 0 0 4px; }
        .info-card p  { font-size: 13px; color: rgba(255,255,255,0.7); margin: 0; }
        .classe-badge {
            margin-left: auto;
            background: #f5c842; color: #1a1a2e;
            border-radius: 10px; padding: 6px 16px;
            font-weight: 700; font-size: 15px;
        }

        .table-wrapper { overflow-x: auto; border-radius: 14px; box-shadow: 0 2px 14px rgba(0,0,0,0.08); }
        table {
            width: 100%; border-collapse: collapse;
            background: #fff; min-width: 600px;
        }
        thead th {
            background: #1a1a2e; color: #f5c842;
            padding: 14px 10px; font-size: 13px;
            font-weight: 600; text-align: center;
        }
        thead th:first-child { border-radius: 14px 0 0 0; }
        thead th:last-child  { border-radius: 0 14px 0 0; }

        tbody td {
            padding: 12px 10px; text-align: center;
            border: 1px solid #e2e8f0; font-size: 13px;
            color: #1a1a2e; vertical-align: middle;
        }
        .time-cell {
            background: #f8fafc; font-weight: 600;
            color: #1a1a2e; white-space: nowrap;
            font-size: 12px;
        }
        .subject-cell {
            border-radius: 8px; padding: 8px 6px !important;
            font-weight: 500; font-size: 13px;
        }
        .empty-cell { color: #cbd5e1; font-size: 18px; }
        tbody tr:hover td { background: #f8fafc; }
        tbody tr:hover td.subject-cell { filter: brightness(0.96); }

        .empty-msg {
            text-align: center; padding: 60px 40px;
            background: #fff; border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07); color: #999;
        }
        .empty-msg i { font-size: 3.5rem; display: block; margin-bottom: 16px; color: #f5c842; }
        .empty-msg h3 { color: #1a1a2e; margin-bottom: 8px; }

        .no-children {
            text-align: center; padding: 80px 40px;
            background: #fff; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .no-children i { font-size: 4rem; display: block; margin-bottom: 18px; color: #f5c842; }
    </style>
</head>
<body>

<div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>

<nav>
    <a href="index.html" class="logo"></a>
    <p style="color:rgb(131,131,131);font-size:10px;">School Platform</p>
    <ul>
        <div class="parent" style="color:#fff;font-size:17px;">
            <?= $parent_name ?>
        </div><br>
        <p style="color:rgb(131,131,131);font-size:10px;">Academic Monitoring:</p>
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="grades.php">📊 Grades & Reports</a></li>
        <li><a href="absences.php">📅 Absences</a></li>
        <li><a href="disciplinary.php">⚠️ Disciplinary Records</a></li>
        <li><a href="parent.php" style="color:#f5c842;">🕐 Timetable</a></li>
        <li><a href="children.php">👦 My Children</a></li>
        <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <div class="page-header">
        <h1>🕐 Timetable</h1>
        <p>Check your child’s timetable.</p>
    </div>

    <?php if (empty($children)): ?>
        <div class="no-children">
            <i class="fas fa-child"></i>
            <h3>Aucun enfant enregistré</h3>
            <p>Ajoutez un enfant depuis <a href="children.php" style="color:#1a1a2e;font-weight:600;">My Children</a> pour voir son emploi du temps.</p>
        </div>

    <?php else: ?>

        <?php if (count($children) > 1): ?>
        <div class="child-tabs">
            <?php foreach ($children as $child): ?>
                <a href="?child_id=<?= $child['id'] ?>"
                   class="child-tab <?= $child['id'] == $selected_child_id ? 'active' : '' ?>">
                    👦 <?= htmlspecialchars($child['child_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($selected_child): ?>

        <div class="info-card">
            <div class="avatar">👦</div>
            <div>
                <h3><?= htmlspecialchars($selected_child['child_name'] . ' ' . ($selected_child['prenom'] ?? '')) ?></h3>
                <p>
                    <?php if (!empty($selected_child['date_naissance'])): ?>
                        🎂 <?= date('d/m/Y', strtotime($selected_child['date_naissance'])) ?>
                    <?php endif; ?>
                    <?php if (!empty($selected_child['niveau'])): ?>
                        &nbsp;·&nbsp; 🎓 <?= htmlspecialchars($selected_child['niveau']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="classe-badge">
                🏫 <?= htmlspecialchars($selected_child['classe'] ?? 'N/A') ?>
            </div>
        </div>

        <?php if (empty($timetable)): ?>
            <div class="empty-msg">
                <i class="fas fa-calendar-times"></i>
                <h3>No timetable available</h3>
                <p>he administration has not yet set up the timetable for the class <strong><?= htmlspecialchars($selected_child['classe'] ?? '') ?></strong>.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>⏰ Horaire</th>
                            <?php foreach ($jours as $j): ?>
                                <th><?= $j ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($heures as $h): ?>
                        <tr>
                            <td class="time-cell">
                                <?= $h[0] ?><br>
                                <span style="color:#aaa;font-weight:400;">↓</span><br>
                                <?= $h[1] ?>
                            </td>
                            <?php foreach ($jours as $j): ?>
                            <td>
                                <?php if (isset($timetable[$h[0]][$j])): 
                                    $mat   = $timetable[$h[0]][$j];
                                    $color = getCouleur($mat, $colors);
                                ?>
                                    <div class="subject-cell" style="background:<?= $color ?>;">
                                        <?= htmlspecialchars($mat) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="empty-cell">—</span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php endif; ?>
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

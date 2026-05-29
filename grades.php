<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'parent') {
    header("Location: login.php"); exit;
}
$parent_id = $_SESSION['user']['id'];

// جلب أولاد الولي
$stmt = $pdo->prepare("SELECT * FROM children WHERE parent_id = ?");
$stmt->execute([$parent_id]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// الولد المختار
$selected_child = $_GET['child_id'] ?? ($children[0]['id'] ?? null);

// جلب النقاط
$grades = [];
if ($selected_child) {
    $stmt = $pdo->prepare("SELECT * FROM grades WHERE child_id = ? ORDER BY trimestre, matiere");
    $stmt->execute([$selected_child]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades & Reports</title>
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
            font-size: 14px; text-decoration: none; color: #555;
            transition: all 0.2s;
        }
        .child-tab.active, .child-tab:hover {
            background: #1a1a2e; color: #f5c842; border-color: #1a1a2e;
        }

        .trimestre-section { margin-bottom: 30px; }
        .trimestre-title {
            font-size: 1rem; font-weight: 600; color: #1a1a2e;
            margin-bottom: 12px; padding: 8px 16px;
            background: #f0f4ff; border-radius: 8px;
            border-left: 4px solid #f5c842;
        }

        table { width: 100%; border-collapse: collapse; background: #fff;
                border-radius: 12px; overflow: hidden;
                box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
        th { background: #1a1a2e; color: #f5c842; padding: 14px 16px; text-align: left; font-weight: 500; }
        td { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; color: #333; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f9f9f9; }

        .badge {
            padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .badge-good  { background: #d4edda; color: #155724; }
        .badge-avg   { background: #fff3cd; color: #856404; }
        .badge-bad   { background: #f8d7da; color: #721c24; }

        .avg-row td { background: #f0f4ff; font-weight: 600; }

        .empty-msg {
            text-align: center; padding: 60px; color: #999;
            background: #fff; border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .empty-msg i { font-size: 3rem; margin-bottom: 15px; display: block; }
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

            <li><a href="grades.php" style="color:#f5c842;">📊 Grades & Reports</a></li>
            <li><a href="absences.php">📅 Absences</a></li>
            <li><a href="disciplinary.php">⚠️ Disciplinary Records</a></li>
            <li><a href="parent.php">🕐 Timetable</a></li>
            <li><a href="children.php">👦 My Children</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>

        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>📊 Grades & Reports</h1>
            <p>Track your child's academic performance by subject and trimester.</p>
        </div>

        <!-- اختيار الولد -->
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

        <?php if (empty($grades)): ?>
            <div class="empty-msg">
                <i class="fas fa-chart-bar"></i>
                No grades recorded yet.
            </div>
        <?php else:
            // تقسيم حسب الفصل
            $byTrimestre = [];
            foreach ($grades as $g) {
                $byTrimestre[$g['trimestre']][] = $g;
            }
            foreach ($byTrimestre as $trimestre => $rows):
                $avg = array_sum(array_column($rows, 'note')) / count($rows);
        ?>
            <div class="trimestre-section">
                <div class="trimestre-title">📅 <?= htmlspecialchars($trimestre) ?></div>
                <table>
                    <tr>
                        <th>Subject</th>
                        <th>Grade / 20</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($rows as $g):
                        $note = $g['note'];
                        $badge = $note >= 14 ? 'badge-good' : ($note >= 10 ? 'badge-avg' : 'badge-bad');
                        $label = $note >= 14 ? 'Good' : ($note >= 10 ? 'Average' : 'Needs Improvement');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($g['matiere']) ?></td>
                        <td><strong><?= number_format($note, 2) ?></strong> / 20</td>
                        <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="avg-row">
                        <td>📌 Trimester Average</td>
                        <td><strong><?= number_format($avg, 2) ?></strong> / 20</td>
                        <td>
                            <span class="badge <?= $avg >= 14 ? 'badge-good' : ($avg >= 10 ? 'badge-avg' : 'badge-bad') ?>">
                                <?= $avg >= 14 ? 'Excellent' : ($avg >= 10 ? 'Passing' : 'Failing') ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endforeach; endif; ?>
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
<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'parent') {
    header("Location: login.php"); exit;
}
$parent_id = $_SESSION['user']['id'];

// جلب أولاد الولي المفعّلين فقط
$stmt = $pdo->prepare("SELECT * FROM children WHERE parent_id = ? AND status = 'active'");
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
    <title>Grades & Reports — ECOLNA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 2rem; color: #1a1a2e; }
        .page-header p { color: #666; margin-top: 5px; }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
        }
        .stat-icon.blue   { background: #dbeafe; }
        .stat-icon.green  { background: #dcfce7; }
        .stat-icon.yellow { background: #fef9c3; }
        .stat-icon.red    { background: #fee2e2; }
        .stat-info strong { font-size: 1.4rem; color: #1a1a2e; display: block; line-height: 1.1; }
        .stat-info span   { font-size: 12px; color: #888; }

        /* Child tabs */
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

        /* Trimestre */
        .trimestre-section { margin-bottom: 30px; }
        .trimestre-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 12px 16px;
            background: linear-gradient(135deg, #1a1a2e, #2d2d5e);
            border-radius: 10px;
            color: #fff;
        }
        .trimestre-header h3 { font-size: 1rem; font-weight: 600; margin: 0; }
        .trimestre-avg {
            background: #f5c842;
            color: #1a1a2e;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 13px;
            font-weight: 700;
        }

        /* Table */
        table {
            width: 100%; border-collapse: collapse; background: #fff;
            border-radius: 12px; overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        th {
            background: #1a1a2e; color: #f5c842;
            padding: 14px 16px; text-align: left; font-weight: 500;
            font-size: 13px;
        }
        td { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; color: #333; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f9f9f9; }

        /* Progress bar */
        .progress-wrap { display: flex; align-items: center; gap: 10px; }
        .progress-bar {
            flex: 1; height: 8px; background: #e9ecef;
            border-radius: 10px; overflow: hidden;
        }
        .progress-fill { height: 100%; border-radius: 10px; transition: width 0.4s; }
        .fill-good   { background: linear-gradient(90deg, #28a745, #51cf66); }
        .fill-avg    { background: linear-gradient(90deg, #ffc107, #ffda6a); }
        .fill-bad    { background: linear-gradient(90deg, #dc3545, #ff6b6b); }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-good  { background: #d4edda; color: #155724; }
        .badge-avg   { background: #fff3cd; color: #856404; }
        .badge-bad   { background: #f8d7da; color: #721c24; }

        .avg-row td { background: #f0f4ff; font-weight: 600; }

        /* Empty */
        .empty-msg {
            text-align: center; padding: 60px; color: #999;
            background: #fff; border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .empty-msg i { font-size: 3rem; margin-bottom: 15px; display: block; color: #f5c842; }
        .empty-msg h3 { color: #1a1a2e; margin-bottom: 8px; }

        /* No children */
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
            <div class="parent" style="color:#fff; font-size:17px">
                <?= htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']) ?>
            </div><br>
            <p style="color:rgb(131,131,131);font-size:10px;">Academic Monitoring:</p>
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

        <?php if (empty($children)): ?>
            <div class="no-children">
                <i class="fas fa-child"></i>
                <h3>No children registered</h3>
                <p>Add a child from <a href="children.php" style="color:#1a1a2e;font-weight:600;">My Children</a> to view grades.</p>
            </div>

        <?php else: ?>

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
                    <h3>No grades recorded yet</h3>
                    <p>Grades will appear here once the teacher adds them.</p>
                </div>

            <?php else:
                // إحصائيات عامة
                $all_notes   = array_column($grades, 'note');
                $global_avg  = array_sum($all_notes) / count($all_notes);
                $best        = max($all_notes);
                $worst       = min($all_notes);
                $passed      = count(array_filter($all_notes, fn($n) => $n >= 5));
            ?>

            <!-- Stats cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon blue">📊</div>
                    <div class="stat-info">
                        <strong><?= number_format($global_avg, 2) ?>/10</strong>
                        <span>Overall Average</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">⭐</div>
                    <div class="stat-info">
                        <strong><?= number_format($best, 2) ?>/10</strong>
                        <span>Best Grade</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow">📚</div>
                    <div class="stat-info">
                        <strong><?= count($all_notes) ?></strong>
                        <span>Total Grades</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon <?= $worst >= 5 ? 'green' : 'red' ?>">
                        <?= $worst >= 5 ? '✅' : '⚠️' ?>
                    </div>
                    <div class="stat-info">
                        <strong><?= number_format($worst, 2) ?>/10</strong>
                        <span>Lowest Grade</span>
                    </div>
                </div>
            </div>

            <?php
                // تقسيم حسب الفصل
                $byTrimestre = [];
                foreach ($grades as $g) {
                    $byTrimestre[$g['trimestre']][] = $g;
                }
                foreach ($byTrimestre as $trimestre => $rows):
                    $avg = array_sum(array_column($rows, 'note')) / count($rows);
                    $avg_badge = $avg >= 7 ? 'badge-good' : ($avg >= 5 ? 'badge-avg' : 'badge-bad');
                    $avg_label = $avg >= 7 ? 'Excellent' : ($avg >= 5 ? 'Passing' : 'Failing');
            ?>
                <div class="trimestre-section">

                    <!-- Header الفصل -->
                    <div class="trimestre-header">
                        <h3>📅 <?= htmlspecialchars($trimestre) ?></h3>
                        <span class="trimestre-avg">
                            Average: <?= number_format($avg, 2) ?>/10
                        </span>
                    </div>

                    <table>
                        <tr>
                            <th>Subject</th>
                            <th>Grade / 10</th>
                            <th>Progress</th>
                            <th>Status</th>
                        </tr>
                        <?php foreach ($rows as $g):
                            $note       = $g['note'];
                            $badge      = $note >= 7 ? 'badge-good' : ($note >= 5 ? 'badge-avg' : 'badge-bad');
                            $fill       = $note >= 7 ? 'fill-good'  : ($note >= 5 ? 'fill-avg'  : 'fill-bad');
                            $label      = $note >= 7 ? 'Good'        : ($note >= 5 ? 'Average'   : 'Needs Improvement');
                            $percent    = ($note / 10) * 100;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($g['matiere']) ?></strong></td>
                            <td><strong><?= number_format($note, 2) ?></strong> / 10</td>
                            <td>
                                <div class="progress-wrap">
                                    <div class="progress-bar">
                                        <div class="progress-fill <?= $fill ?>"
                                             style="width:<?= $percent ?>%"></div>
                                    </div>
                                    <span style="font-size:12px;color:#888;white-space:nowrap;">
                                        <?= round($percent) ?>%
                                    </span>
                                </div>
                            </td>
                            <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- صف المعدل -->
                        <tr class="avg-row">
                            <td>📌 Trimester Average</td>
                            <td><strong><?= number_format($avg, 2) ?></strong> / 10</td>
                            <td>
                                <div class="progress-wrap">
                                    <div class="progress-bar">
                                        <div class="progress-fill <?= $avg >= 7 ? 'fill-good' : ($avg >= 5 ? 'fill-avg' : 'fill-bad') ?>"
                                             style="width:<?= ($avg/10)*100 ?>%"></div>
                                    </div>
                                    <span style="font-size:12px;color:#888;">
                                        <?= round(($avg/10)*100) ?>%
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $avg_badge ?>">
                                    <?= $avg_label ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endforeach; ?>

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

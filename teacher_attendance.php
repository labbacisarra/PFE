<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'enseignant') {
    header('Location: login.php'); exit;
}
$teacher_name = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'];
$success = $error = '';

// ── حفظ الغيابات ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $classe   = trim($_POST['classe']);
    $date_abs = trim($_POST['date_abs']);
    $statuses = $_POST['status'] ?? [];
    $motifs   = $_POST['motif']  ?? [];

    foreach ($statuses as $child_id => $status) {
        $child_id = (int)$child_id;
        $pdo->prepare("DELETE FROM absences WHERE child_id=? AND date_abs=?")->execute([$child_id, $date_abs]);
        if ($status === 'absent' || $status === 'justified') {
            $justifie = $status === 'justified' ? 1 : 0;
            $motif    = trim($motifs[$child_id] ?? '');
            $pdo->prepare("INSERT INTO absences (child_id, date_abs, motif, justifie) VALUES (?,?,?,?)")
                ->execute([$child_id, $date_abs, $motif, $justifie]);
        }
    }
    $success = '✅ Attendance saved!';
}

// ── الأقسام ──
$classes = $pdo->query("SELECT DISTINCT TRIM(classe) as classe FROM children WHERE status='active' AND classe IS NOT NULL AND classe!='' ORDER BY classe")->fetchAll(PDO::FETCH_COLUMN);

$selectedClass = trim($_GET['classe'] ?? $_POST['classe'] ?? '');
$selectedDate  = $_GET['date'] ?? $_POST['date_abs'] ?? date('Y-m-d');

// ── الطلاب ──
$students    = [];
$absencesMap = [];
if ($selectedClass) {
    $stmt = $pdo->prepare("SELECT * FROM children WHERE TRIM(classe)=? AND status='active' ORDER BY child_name");
    $stmt->execute([$selectedClass]);
    $students = $stmt->fetchAll();

    if (!empty($students)) {
        $ids = array_column($students, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT child_id, justifie, motif FROM absences WHERE date_abs=? AND child_id IN ($ph)");
        $stmt->execute(array_merge([$selectedDate], $ids));
        foreach ($stmt->fetchAll() as $r) {
            $absencesMap[$r['child_id']] = $r;
        }
    }
}

function getStatus($id, $map) {
    if (!isset($map[$id])) return 'present';
    return $map[$id]['justifie'] == 1 ? 'justified' : 'absent';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance – ECOLNA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 2rem; color: #1a1a2e; }
        .page-header p  { color: #666; margin-top: 5px; }

        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert-success { background: rgba(40,167,69,0.15); color: #155724; border: 1px solid rgba(40,167,69,0.3); }
        .alert-error   { background: rgba(220,53,69,0.15); color: #721c24; border: 1px solid rgba(220,53,69,0.3); }

        /* Controls */
        .controls {
            display: flex; gap: 14px; flex-wrap: wrap;
            align-items: flex-end; margin-bottom: 24px;
        }
        .ctrl-group { display: flex; flex-direction: column; gap: 6px; }
        .ctrl-group label { font-size: 12px; font-weight: 600; color: #888; text-transform: uppercase; }
        .ctrl-group select, .ctrl-group input[type="date"] {
            padding: 10px 14px; border: 1.5px solid #e0e0e0;
            border-radius: 10px; font-family: 'Outfit', sans-serif;
            font-size: 14px; outline: none; min-width: 160px;
        }
        .ctrl-group select:focus, .ctrl-group input:focus { border-color: #f5c842; }
        .btn-load {
            padding: 11px 22px; background: #1a1a2e; color: #f5c842;
            border: none; border-radius: 10px; font-family: 'Outfit', sans-serif;
            font-size: 14px; font-weight: 600; cursor: pointer;
        }
        .btn-load:hover { opacity: 0.85; }

        /* Stats */
        .stats-row { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        .stat-pill {
            display: flex; align-items: center; gap: 10px;
            background: #fff; border-radius: 12px;
            padding: 12px 18px; flex: 1; min-width: 120px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .stat-pill strong { font-size: 1.4rem; color: #1a1a2e; display: block; }
        .stat-pill span   { font-size: 12px; color: #888; }

        /* Panel */
        .panel {
            background: #fff; border-radius: 16px; padding: 24px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.07); margin-bottom: 24px;
        }
        .panel h3 { font-size: 16px; color: #1a1a2e; margin-bottom: 20px; }

        /* Mark all */
        .mark-all {
            display: flex; gap: 8px; align-items: center;
            margin-bottom: 16px; padding-bottom: 16px;
            border-bottom: 1px solid #f0f0f0;
        }
        .mark-all span { font-size: 13px; color: #888; font-weight: 600; }
        .btn-ma {
            padding: 6px 14px; border-radius: 20px; border: 1.5px solid #e0e0e0;
            background: #fff; font-family: 'Outfit', sans-serif;
            font-size: 12px; font-weight: 600; cursor: pointer;
        }
        .btn-ma:hover { border-color: #1a1a2e; color: #1a1a2e; }

        /* Student row */
        .s-row {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 8px; border-bottom: 1px solid #f5f5f5;
        }
        .s-row:last-child { border-bottom: none; }
        .s-num { font-size: 13px; color: #aaa; min-width: 20px; text-align: center; }
        .s-av {
            width: 40px; height: 40px; border-radius: 50%;
            background: #fef9c3; color: #1a1a2e;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; flex-shrink: 0;
            border: 1.5px solid #f5c842;
        }
        .s-name { font-size: 14px; font-weight: 600; color: #1a1a2e; flex: 1; }

        /* Toggle buttons */
        .toggle { display: flex; gap: 6px; }
        .tog {
            padding: 7px 14px; border-radius: 20px;
            border: 1.5px solid #e0e0e0; background: #fff;
            font-family: 'Outfit', sans-serif; font-size: 12px;
            font-weight: 600; cursor: pointer; transition: all 0.2s;
            color: #888;
        }
        .tog.present.active   { background: #d4edda; border-color: #28a745; color: #155724; }
        .tog.absent.active    { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .tog.justified.active { background: #fff3cd; border-color: #ffc107; color: #856404; }

        .s-motif {
            padding: 7px 12px; border: 1.5px solid #e0e0e0;
            border-radius: 8px; font-family: 'Outfit', sans-serif;
            font-size: 12px; outline: none; width: 150px;
        }
        .s-motif:focus { border-color: #f5c842; }

        /* Empty */
        .empty {
            text-align: center; padding: 50px; color: #aaa;
        }
        .empty i { font-size: 3rem; display: block; margin-bottom: 12px; }

        /* Save row */
        .save-row { display: flex; justify-content: flex-end; gap: 12px; }
        .btn-save {
            padding: 12px 32px; background: #f5c842; color: #1a1a2e;
            border: none; border-radius: 10px; font-family: 'Outfit', sans-serif;
            font-size: 15px; font-weight: 700; cursor: pointer;
        }
        .btn-save:hover { opacity: 0.9; }
        .btn-reset {
            padding: 12px 22px; background: transparent; color: #888;
            border: 1.5px solid #e0e0e0; border-radius: 10px;
            font-family: 'Outfit', sans-serif; font-size: 14px;
            font-weight: 600; cursor: pointer;
        }
        .btn-reset:hover { border-color: #dc3545; color: #dc3545; }
    </style>
</head>
<body>

<div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>
<nav>
    <a href="index.html" class="logo"></a>
    <p style="color:rgb(131,131,131);font-size:10px;">School Platform</p>
    <ul>
        <div style="color:#fff;font-size:17px;">👨‍🏫 <?= htmlspecialchars($teacher_name) ?></div><br>
        <p style="color:rgb(131,131,131);font-size:10px;">Classroom:</p>
        <li><a href="enseignant.php">🏠 Dashboard</a></li>
        <li><a href="teacher_attendance.php" style="color:#f5c842;">📅 Mark Attendance</a></li>
        <li><a href="teacher_students.php">👥 Student List</a></li>
        <li><a href="teacher_grades.php">⭐ Manage Grades</a></li>
        <li><a href="teacher_disciplinary.php">⚠️ Disciplinary</a></li>
        <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <div class="page-header">
        <h1>📅 Mark Attendance</h1>
        <p>Select a class and date, then mark each student.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <!-- اختيار القسم والتاريخ -->
    <form method="GET">
        <div class="controls">
            <div class="ctrl-group">
                <label>Class</label>
                <select name="classe">
                    <option value="">-- Select --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $c === $selectedClass ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ctrl-group">
                <label>Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
            </div>
            <button type="submit" class="btn-load">
                <i class="fas fa-search"></i> Load
            </button>
        </div>
    </form>

    <?php if (!empty($students)):
        $total     = count($students);
        $absent    = 0; $justified = 0;
        foreach ($students as $s) {
            $st = getStatus($s['id'], $absencesMap);
            if ($st === 'absent') $absent++;
            if ($st === 'justified') $justified++;
        }
        $present = $total - $absent - $justified;
    ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-pill">
            <div>👥<strong id="c-total"><?= $total ?></strong><span>Total</span></div>
        </div>
        <div class="stat-pill">
            <div>✅<strong id="c-present"><?= $present ?></strong><span>Present</span></div>
        </div>
        <div class="stat-pill">
            <div>❌<strong id="c-absent"><?= $absent ?></strong><span>Absent</span></div>
        </div>
        <div class="stat-pill">
            <div>📄<strong id="c-justified"><?= $justified ?></strong><span>Justified</span></div>
        </div>
    </div>

    <!-- فورم الغيابات -->
    <form method="POST">
        <input type="hidden" name="save" value="1">
        <input type="hidden" name="classe"   value="<?= htmlspecialchars($selectedClass) ?>">
        <input type="hidden" name="date_abs" value="<?= htmlspecialchars($selectedDate) ?>">

        <div class="panel">
            <h3>📋 Class <?= htmlspecialchars($selectedClass) ?> — <?= htmlspecialchars($selectedDate) ?></h3>

            <div class="mark-all">
                <span>Mark all:</span>
                <button type="button" class="btn-ma" onclick="markAll('present')">✅ Present</button>
                <button type="button" class="btn-ma" onclick="markAll('absent')">❌ Absent</button>
            </div>

            <?php foreach ($students as $i => $s):
                $sid    = $s['id'];
                $status = getStatus($sid, $absencesMap);
                $motif  = $absencesMap[$sid]['motif'] ?? '';
                $init   = strtoupper(substr($s['child_name'], 0, 1));
            ?>
            <div class="s-row">
                <div class="s-num"><?= $i+1 ?></div>
                <div class="s-av"><?= $init ?></div>
                <div class="s-name"><?= htmlspecialchars($s['child_name'] . ' ' . ($s['prenom'] ?? '')) ?></div>
                <input type="hidden" name="status[<?= $sid ?>]" id="st_<?= $sid ?>" value="<?= $status ?>">
                <div class="toggle">
                    <button type="button" class="tog present   <?= $status==='present'   ? 'active':'' ?>" onclick="setSt(<?= $sid ?>,'present',this)">✅</button>
                    <button type="button" class="tog absent    <?= $status==='absent'    ? 'active':'' ?>" onclick="setSt(<?= $sid ?>,'absent',this)">❌</button>
                    <button type="button" class="tog justified <?= $status==='justified' ? 'active':'' ?>" onclick="setSt(<?= $sid ?>,'justified',this)">📄</button>
                </div>
                <input class="s-motif" type="text" name="motif[<?= $sid ?>]"
                       placeholder="Reason..." value="<?= htmlspecialchars($motif) ?>">
            </div>
            <?php endforeach; ?>
        </div>

        <div class="save-row">
            <button type="button" class="btn-reset" onclick="resetAll()">↺ Reset</button>
            <button type="submit" class="btn-save">💾 Save</button>
        </div>
    </form>

    <?php elseif ($selectedClass): ?>
        <div class="empty"><i class="fas fa-users"></i> No students in this class.</div>
    <?php else: ?>
        <div class="empty"><i class="fas fa-hand-point-up"></i> Select a class to start.</div>
    <?php endif; ?>

</div>

<script>
function setSt(sid, status, btn) {
    document.getElementById('st_' + sid).value = status;
    btn.closest('.toggle').querySelectorAll('.tog').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    updateCounts();
}
function markAll(status) {
    document.querySelectorAll('[id^="st_"]').forEach(input => {
        input.value = status;
        const row = input.closest('.s-row');
        row.querySelectorAll('.tog').forEach(b => b.classList.remove('active'));
        row.querySelector('.tog.' + status).classList.add('active');
    });
    updateCounts();
}
function resetAll() {
    markAll('present');
    document.querySelectorAll('.s-motif').forEach(i => i.value = '');
}
function updateCounts() {
    let p=0, a=0, j=0;
    document.querySelectorAll('[id^="st_"]').forEach(i => {
        if (i.value==='present')   p++;
        if (i.value==='absent')    a++;
        if (i.value==='justified') j++;
    });
    document.getElementById('c-present').textContent   = p;
    document.getElementById('c-absent').textContent    = a;
    document.getElementById('c-justified').textContent = j;
}
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

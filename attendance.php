<?php
session_start();
require 'db.php';

// ── PROTECTION ──
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'enseignant') {
    header('Location: login.php');
    exit;
}

$teacher      = $_SESSION['user'];
$teacher_name = $teacher['first_name'] . ' ' . $teacher['last_name'];

$success = '';
$error   = '';

// ── SAVE ATTENDANCE (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $classe   = trim($_POST['classe']);
    $date_abs = trim($_POST['date_abs']);
    $statuses = $_POST['status'] ?? [];
    $motifs   = $_POST['motif']  ?? [];

    if ($classe && $date_abs && !empty($statuses)) {
        try {
            foreach ($statuses as $child_id => $status) {
                $child_id = (int)$child_id;

                $del = $pdo->prepare("DELETE FROM absences WHERE child_id = ? AND date_abs = ?");
                $del->execute([$child_id, $date_abs]);

                if ($status === 'absent' || $status === 'justified') {
                    $justifie = ($status === 'justified') ? 1 : 0;
                    $motif    = isset($motifs[$child_id]) ? trim($motifs[$child_id]) : '';
                    $ins = $pdo->prepare("
                        INSERT INTO absences (child_id, date_abs, motif, justifie)
                        VALUES (?, ?, ?, ?)
                    ");
                    $ins->execute([$child_id, $date_abs, $motif, $justifie]);
                }
            }
            $success = '✅ Attendance saved successfully for class ' . htmlspecialchars($classe) . ' on ' . $date_abs;
        } catch (Exception $e) {
            $error = '❌ Error: ' . $e->getMessage();
        }
    } else {
        $error = '❌ Please select a class, a date and mark all students.';
    }
}

// ── LOAD CLASSES from emploi_temps ──
$stmt = $pdo->query("SELECT DISTINCT classe FROM emploi_temps ORDER BY classe");
$classes = $stmt->fetchAll();

// ── LOAD STUDENTS if class selected ──
$selectedClass = $_GET['classe'] ?? $_POST['classe'] ?? '';
$selectedDate  = $_GET['date']   ?? $_POST['date_abs'] ?? date('Y-m-d');
$students      = [];
$absencesMap   = [];

if ($selectedClass) {
    $stmt = $pdo->prepare("
        SELECT id, child_name, prenom, classe
        FROM children
        WHERE classe = ?
        ORDER BY child_name ASC
    ");
    $stmt->execute([$selectedClass]);
    $students = $stmt->fetchAll();

    if (!empty($students)) {
        $ids = array_column($students, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT child_id, justifie, motif
            FROM absences
            WHERE date_abs = ? AND child_id IN ($placeholders)
        ");
        $stmt->execute(array_merge([$selectedDate], $ids));
        foreach ($stmt->fetchAll() as $row) {
            $absencesMap[$row['child_id']] = $row;
        }
    }
}

// ── SUMMARY COUNTS ──
$totalCount     = count($students);
$absentCount    = 0;
$justifiedCount = 0;
foreach ($students as $s) {
    if (isset($absencesMap[$s['id']])) {
        if ($absencesMap[$s['id']]['justifie'] == 1) $justifiedCount++;
        else $absentCount++;
    }
}
$presentCount = $totalCount - $absentCount - $justifiedCount;

function getStatus($child_id, $absencesMap) {
    if (!isset($absencesMap[$child_id])) return 'present';
    return $absencesMap[$child_id]['justifie'] == 1 ? 'justified' : 'absent';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <title>Mark Attendance – ECOLNA</title>
    <style>
        .container h1 { color:#0f1f3d; font-family:'Playfair Display',serif; font-size:28px; margin-bottom:6px; }
        .container h1 span { color:#f5c842; }
        .sub { color:#6b7a99; font-size:14px; margin-bottom:28px; }

        .alert { padding:12px 16px; border-radius:10px; font-size:13px; font-weight:500; margin-bottom:20px; }
        .alert-success { background:rgba(102,187,106,0.15); border:1px solid #66bb6a; color:#2e7d32; }
        .alert-error   { background:rgba(239,83,80,0.12);   border:1px solid #ef5350; color:#c62828; }

        .controls { display:flex; align-items:flex-end; gap:14px; margin-bottom:24px; flex-wrap:wrap; }
        .ctrl-group { display:flex; flex-direction:column; gap:4px; }
        .ctrl-group label { font-size:10px; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; color:#6b7a99; }
        .ctrl-group select,
        .ctrl-group input[type="date"] {
            padding:9px 14px; border:1px solid rgba(15,31,61,0.15); border-radius:10px;
            font-family:'Outfit',sans-serif; font-size:13px; color:#0f1f3d;
            background:#fff; outline:none; transition:border-color 0.2s;
        }
        .ctrl-group select:focus, .ctrl-group input:focus { border-color:#f5c842; }
        .btn-load { padding:10px 22px; background:#0f1f3d; color:#f5c842; border:none; border-radius:10px; font-family:'Outfit',sans-serif; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:8px; text-decoration:none; }
        .btn-load:hover { background:#1a3060; transform:translateY(-2px); }

        .summary-row { display:flex; gap:14px; margin-bottom:24px; flex-wrap:wrap; }
        .sum-pill { display:flex; align-items:center; gap:10px; background:#fff; border:1px solid rgba(15,31,61,0.1); border-radius:12px; padding:12px 20px; box-shadow:0 2px 10px rgba(15,31,61,0.06); min-width:130px; }
        .sp-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; }
        .sp-val { font-size:22px; font-weight:700; color:#0f1f3d; font-family:'Playfair Display',serif; }
        .sp-lbl { font-size:11px; color:#6b7a99; }

        .panel { background:#fff; border-radius:14px; padding:22px; border:1px solid rgba(15,31,61,0.1); box-shadow:0 2px 12px rgba(15,31,61,0.07); margin-bottom:24px; animation:fadeUp 0.4s ease both; }
        .panel-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
        .panel-head h3 { font-size:15px; font-weight:600; color:#0f1f3d; }

        .search-box { display:flex; align-items:center; gap:8px; background:rgba(15,31,61,0.05); border:1px solid rgba(15,31,61,0.12); border-radius:10px; padding:7px 14px; }
        .search-box input { border:none; background:transparent; font-family:'Outfit',sans-serif; font-size:13px; color:#0f1f3d; outline:none; width:180px; }
        .search-box i { color:#6b7a99; font-size:13px; }

        .mark-all-row { display:flex; align-items:center; gap:10px; padding:10px 10px 16px; border-bottom:2px solid rgba(15,31,61,0.08); margin-bottom:6px; }
        .mark-all-label { font-size:12px; font-weight:600; color:#6b7a99; }
        .btn-mark-all { padding:6px 14px; border-radius:20px; border:1px solid rgba(15,31,61,0.15); font-family:'Outfit',sans-serif; font-size:11px; font-weight:600; cursor:pointer; transition:all 0.2s; background:transparent; color:#6b7a99; }
        .btn-mark-all:hover        { border-color:#66bb6a; color:#2e7d32; background:rgba(102,187,106,0.08); }
        .btn-mark-all.absent:hover { border-color:#ef5350; color:#c62828; background:rgba(239,83,80,0.08); }

        .student-row { display:flex; align-items:center; gap:14px; padding:13px 10px; border-bottom:1px solid rgba(15,31,61,0.07); border-radius:8px; transition:background 0.15s; }
        .student-row:last-child { border-bottom:none; }
        .student-row:hover { background:rgba(245,200,66,0.04); }
        .s-num { font-size:12px; color:#6b7a99; font-weight:500; min-width:24px; text-align:center; }
        .s-avatar { width:38px; height:38px; border-radius:50%; background:rgba(15,31,61,0.08); color:#0f1f3d; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; }
        .s-name { font-size:14px; font-weight:600; color:#0f1f3d; }
        .s-id   { font-size:11px; color:#6b7a99; margin-top:2px; }

        .attendance-toggle { margin-left:auto; display:flex; gap:6px; }
        .tog-btn { padding:7px 14px; border-radius:20px; border:1px solid rgba(15,31,61,0.15); font-family:'Outfit',sans-serif; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.2s; background:transparent; color:#6b7a99; }
        .tog-btn.present.active   { background:rgba(102,187,106,0.15); border-color:#66bb6a; color:#2e7d32; }
        .tog-btn.absent.active    { background:rgba(239,83,80,0.12);   border-color:#ef5350; color:#c62828; }
        .tog-btn.justified.active { background:rgba(255,152,0,0.12);   border-color:#ffa726; color:#e65100; }
        .tog-btn:not(.active):hover { border-color:#0f1f3d; color:#0f1f3d; }

        .s-note { padding:6px 10px; border:1px solid rgba(15,31,61,0.12); border-radius:8px; font-family:'Outfit',sans-serif; font-size:12px; color:#0f1f3d; outline:none; width:150px; transition:border-color 0.2s; }
        .s-note:focus { border-color:#f5c842; }

        .empty-state { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:50px 20px; gap:12px; }
        .empty-state i { font-size:40px; color:#d1d9e6; }
        .empty-state p { font-size:13px; color:#6b7a99; text-align:center; line-height:1.7; }

        .save-row { display:flex; justify-content:flex-end; gap:12px; }
        .btn-save { padding:12px 32px; background:#f5c842; color:#0f1f3d; border:none; border-radius:10px; font-family:'Outfit',sans-serif; font-size:14px; font-weight:700; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:8px; }
        .btn-save:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(245,200,66,0.4); }
        .btn-reset { padding:12px 24px; background:transparent; color:#6b7a99; border:1px solid rgba(15,31,61,0.15); border-radius:10px; font-family:'Outfit',sans-serif; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; }
        .btn-reset:hover { border-color:#ef5350; color:#ef5350; }

        @keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }

        @media (max-width:610px) {
            .controls { flex-direction:column; align-items:flex-start; }
            .attendance-toggle { flex-wrap:wrap; margin-left:0; width:100%; }
            .student-row { flex-wrap:wrap; }
            .s-note { width:100%; }
        }
    </style>
</head>
<body>

<div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>

<!-- ── SIDEBAR ── -->
<nav>
    <a href="HomePfe.html" class="logo"></a>
    <p style="color:rgb(131,131,131);font-size:10px;">Platforme Scolaire</p>
    <ul>
        <div style="color:#fff; font-size:17px;">
            👤 <?= htmlspecialchars($teacher_name) ?>
        </div><br>
        <p style="color:rgb(131,131,131);font-size:10px;">Classroom:</p>
        <li><a href="enseignant.php">🏠 Dashboard</a></li>
        <li><a href="attendance.php" style="color:#f5c842;">📅 Mark Attendance</a></li>
        <li><a href="teacher_students.php">👥 Student List</a></li>
        <li><a href="teacher_grades.php">⭐ Manage Grades</a></li>
        <li><a href="teacher_disciplinary.php">⚠️ Disciplinary</a></li>
        <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
    </ul>
</nav>

<!-- ── MAIN ── -->
<div class="container">

    <h1>Mark <span>Attendance</span></h1>
    <p class="sub">Select a class and date, then mark each student's status.</p>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <!-- Controls (GET form to load students) -->
    <form method="GET" action="attendance.php">
        <div class="controls">
            <div class="ctrl-group">
                <label>Class</label>
                <select name="classe" id="classSelect">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= htmlspecialchars($c['classe']) ?>"
                            <?= $selectedClass === $c['classe'] ? 'selected' : '' ?>>
                            Class <?= htmlspecialchars($c['classe']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ctrl-group">
                <label>Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
            </div>
            <button type="submit" class="btn-load">
                <i class="fas fa-sync-alt"></i> Load Class
            </button>
        </div>
    </form>

    <!-- Summary pills -->
    <div class="summary-row">
        <div class="sum-pill">
            <div class="sp-icon" style="background:rgba(15,31,61,0.07);color:#0f1f3d;"><i class="fas fa-users"></i></div>
            <div><div class="sp-val" id="totalCount"><?= $totalCount ?></div><div class="sp-lbl">Total</div></div>
        </div>
        <div class="sum-pill">
            <div class="sp-icon" style="background:rgba(102,187,106,0.12);color:#388e3c;"><i class="fas fa-check-circle"></i></div>
            <div><div class="sp-val" id="presentCount"><?= $presentCount ?></div><div class="sp-lbl">Present</div></div>
        </div>
        <div class="sum-pill">
            <div class="sp-icon" style="background:rgba(239,83,80,0.12);color:#c62828;"><i class="fas fa-times-circle"></i></div>
            <div><div class="sp-val" id="absentCount"><?= $absentCount ?></div><div class="sp-lbl">Absent</div></div>
        </div>
        <div class="sum-pill">
            <div class="sp-icon" style="background:rgba(255,152,0,0.12);color:#e65100;"><i class="fas fa-file-alt"></i></div>
            <div><div class="sp-val" id="justifiedCount"><?= $justifiedCount ?></div><div class="sp-lbl">Justified</div></div>
        </div>
    </div>

    <!-- POST form to save attendance -->
    <form method="POST" action="attendance.php" id="attendanceForm">
        <input type="hidden" name="save_attendance" value="1">
        <input type="hidden" name="classe"   value="<?= htmlspecialchars($selectedClass) ?>">
        <input type="hidden" name="date_abs" value="<?= htmlspecialchars($selectedDate) ?>">

        <div class="panel">
            <div class="panel-head">
                <h3>📋 Student List
                    <?php if ($selectedClass): ?>
                        — Class <?= htmlspecialchars($selectedClass) ?>
                        · <?= htmlspecialchars($selectedDate) ?>
                    <?php endif; ?>
                </h3>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search student..." oninput="filterStudents()">
                </div>
            </div>

            <?php if (empty($students)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>
                        No class loaded yet.<br>
                        Select a class and date above, then click <b>Load Class</b>.
                    </p>
                </div>
            <?php else: ?>

                <!-- Mark all -->
                <div class="mark-all-row">
                    <span class="mark-all-label">Mark all as:</span>
                    <button type="button" class="btn-mark-all"        onclick="markAll('present')">✅ All Present</button>
                    <button type="button" class="btn-mark-all absent" onclick="markAll('absent')">❌ All Absent</button>
                </div>

                <!-- Student rows -->
                <?php foreach ($students as $i => $student):
                    $sid      = $student['id'];
                    $fullName = htmlspecialchars($student['prenom'] . ' ' . $student['child_name']);
                    $initAv   = strtoupper(substr($student['prenom'],0,1) . substr($student['child_name'],0,1));
                    $status   = getStatus($sid, $absencesMap);
                    $motifVal = $absencesMap[$sid]['motif'] ?? '';
                ?>
                <div class="student-row" data-name="<?= strtolower($fullName) ?>">
                    <div class="s-num"><?= $i+1 ?></div>
                    <div class="s-avatar"><?= $initAv ?></div>
                    <div>
                        <div class="s-name"><?= $fullName ?></div>
                        <div class="s-id">ID: <?= $sid ?></div>
                    </div>

                    <input type="hidden" name="status[<?= $sid ?>]"
                           id="status_<?= $sid ?>"
                           value="<?= $status ?>">

                    <div class="attendance-toggle">
                        <button type="button"
                            class="tog-btn present <?= $status==='present'   ? 'active' : '' ?>"
                            onclick="setStatus(<?= $sid ?>,'present',this)">
                            ✅ Present
                        </button>
                        <button type="button"
                            class="tog-btn absent <?= $status==='absent'    ? 'active' : '' ?>"
                            onclick="setStatus(<?= $sid ?>,'absent',this)">
                            ❌ Absent
                        </button>
                        <button type="button"
                            class="tog-btn justified <?= $status==='justified' ? 'active' : '' ?>"
                            onclick="setStatus(<?= $sid ?>,'justified',this)">
                            📄 Justified
                        </button>
                    </div>

                    <input class="s-note" type="text"
                           name="motif[<?= $sid ?>]"
                           placeholder="Note / Motif..."
                           value="<?= htmlspecialchars($motifVal) ?>">
                </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>

        <?php if (!empty($students)): ?>
        <div class="save-row">
            <button type="button" class="btn-reset" onclick="resetAll()">
                <i class="fas fa-undo"></i> Reset
            </button>
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Save Attendance
            </button>
        </div>
        <?php endif; ?>

    </form>

</div><!-- /.container -->

<script>
    // ── SET STATUS ──
    function setStatus(sid, status, btn) {
        document.getElementById('status_' + sid).value = status;
        btn.closest('.attendance-toggle').querySelectorAll('.tog-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        updateSummary();
    }

    // ── MARK ALL ──
    function markAll(status) {
        document.querySelectorAll('.student-row').forEach(row => {
            if (row.style.display === 'none') return;
            const sid = row.querySelector('input[type="hidden"]').name.match(/\d+/)[0];
            document.getElementById('status_' + sid).value = status;
            row.querySelectorAll('.tog-btn').forEach(b => b.classList.remove('active'));
            const target = row.querySelector('.tog-btn.' + status);
            if (target) target.classList.add('active');
        });
        updateSummary();
    }

    // ── UPDATE SUMMARY ──
    function updateSummary() {
        let present = 0, absent = 0, justified = 0, total = 0;
        document.querySelectorAll('input[name^="status["]').forEach(input => {
            total++;
            if (input.value === 'present')   present++;
            if (input.value === 'absent')    absent++;
            if (input.value === 'justified') justified++;
        });
        document.getElementById('totalCount').textContent     = total;
        document.getElementById('presentCount').textContent   = present;
        document.getElementById('absentCount').textContent    = absent;
        document.getElementById('justifiedCount').textContent = justified;
    }

    // ── SEARCH FILTER ──
    function filterStudents() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.student-row').forEach(row => {
            row.style.display = row.dataset.name.includes(q) ? 'flex' : 'none';
        });
    }

    // ── RESET ──
    function resetAll() {
        document.querySelectorAll('input[name^="status["]').forEach(input => {
            input.value = 'present';
        });
        document.querySelectorAll('.tog-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tog-btn.present').forEach(b => b.classList.add('active'));
        document.querySelectorAll('.s-note').forEach(n => n.value = '');
        updateSummary();
    }

    // ── HAMBURGER ──
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
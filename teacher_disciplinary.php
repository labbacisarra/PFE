<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'enseignant') {
    header("Location: login.php"); exit;
}

$teacher_name = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'];
$success = $error = "";

// ── إضافة سجل ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $child_id    = (int)$_POST['child_id'];
    $date_inc    = $_POST['date_inc'];
    $description = trim($_POST['description']);
    $gravite     = $_POST['gravite'];

    if (empty($description)) {
        $error = "❌ Veuillez remplir la description.";
    } else {
        $pdo->prepare("INSERT INTO disciplinary (child_id, date_inc, description, gravite) VALUES (?,?,?,?)")
            ->execute([$child_id, $date_inc, $description, $gravite]);
        $success = "✅ Enregistrement ajouté avec succès!";
    }
}

// ── تعديل سجل ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id          = (int)$_POST['record_id'];
    $date_inc    = $_POST['date_inc'];
    $description = trim($_POST['description']);
    $gravite     = $_POST['gravite'];

    $pdo->prepare("UPDATE disciplinary SET date_inc=?, description=?, gravite=? WHERE id=?")
        ->execute([$date_inc, $description, $gravite, $id]);
    $success = "✅ Enregistrement modifié!";
}

// ── حذف سجل ──
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM disciplinary WHERE id=?")->execute([(int)$_GET['delete']]);
    $success = "✅ Enregistrement supprimé.";
}

// ── جلب الأقسام من children مباشرة مع TRIM ──
$stmt = $pdo->query("
    SELECT DISTINCT TRIM(classe) as classe 
    FROM children 
    WHERE status = 'active' 
    AND classe IS NOT NULL 
    AND classe != ''
    ORDER BY classe
");
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ── القسم والطالب المختاران ──
$selected_classe = isset($_GET['classe']) ? trim($_GET['classe']) : ($classes[0] ?? '');
$selected_child  = $_GET['child_id'] ?? null;

// ── جلب الطلاب حسب القسم ──
$students = [];
if ($selected_classe) {
    $stmt = $pdo->prepare("
        SELECT * FROM children 
        WHERE TRIM(classe) = ? 
        AND status = 'active' 
        ORDER BY child_name
    ");
    $stmt->execute([trim($selected_classe)]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$selected_child && !empty($students)) {
        $selected_child = $students[0]['id'];
    }
}

// ── جلب السجلات ──
$records = [];
if ($selected_child) {
    $stmt = $pdo->prepare("SELECT * FROM disciplinary WHERE child_id=? ORDER BY date_inc DESC");
    $stmt->execute([$selected_child]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplinary – ECOLNA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 2rem; color: #1a1a2e; }
        .page-header p  { color: #666; margin-top: 5px; }

        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert-success { background: rgba(40,167,69,0.15); color: #155724; border: 1px solid rgba(40,167,69,0.3); }
        .alert-error   { background: rgba(220,53,69,0.15); color: #721c24; border: 1px solid rgba(220,53,69,0.3); }

        .tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab-btn {
            padding: 8px 20px; border-radius: 20px; border: 2px solid #e0e0e0;
            background: #fff; cursor: pointer; font-family: 'Outfit', sans-serif;
            font-size: 14px; text-decoration: none; color: #555; transition: all 0.2s;
        }
        .tab-btn.active, .tab-btn:hover { background: #0f1f3d; color: #f5c842; border-color: #0f1f3d; }

        .add-card {
            background: #fff; border-radius: 16px; padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 28px;
            border-top: 4px solid #f5c842;
        }
        .add-card h3 { color: #0f1f3d; margin-bottom: 18px; font-size: 1.1rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap: 14px; }
        .form-field { display: flex; flex-direction: column; gap: 6px; }
        .form-field label { font-size: 13px; color: #555; font-weight: 500; }
        .form-field input, .form-field select, .form-field textarea {
            padding: 10px 14px; border: 1.5px solid #e0e0e0; border-radius: 10px;
            font-family: 'Outfit', sans-serif; font-size: 14px; outline: none; transition: border 0.2s;
        }
        .form-field input:focus, .form-field select:focus, .form-field textarea:focus { border-color: #f5c842; }
        .form-field textarea { resize: vertical; min-height: 80px; }
        .form-full { grid-column: 1 / -1; }
        .btn-add {
            padding: 11px 28px; background: #0f1f3d; color: #f5c842;
            border: none; border-radius: 10px; font-family: 'Outfit', sans-serif;
            font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 10px;
            transition: opacity 0.2s;
        }
        .btn-add:hover { opacity: 0.85; }

        .record-card {
            background: #fff; border-radius: 12px; padding: 20px 24px;
            margin-bottom: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border-left: 5px solid #ccc; display: flex; gap: 20px; align-items: flex-start;
        }
        .record-card.high   { border-left-color: #dc3545; }
        .record-card.medium { border-left-color: #fd7e14; }
        .record-card.low    { border-left-color: #ffc107; }

        .record-icon { font-size: 2rem; min-width: 40px; text-align: center; }
        .record-body { flex: 1; }
        .record-date { font-size: 12px; color: #999; margin-bottom: 6px; }
        .record-desc { color: #333; font-size: 15px; line-height: 1.5; }

        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-top: 10px; display: inline-block; }
        .badge-high   { background: #f8d7da; color: #721c24; }
        .badge-medium { background: #ffe5d0; color: #7d3c0a; }
        .badge-low    { background: #fff3cd; color: #856404; }

        .record-actions { display: flex; gap: 8px; margin-top: 10px; }
        .btn-sm { padding: 5px 12px; border-radius: 8px; border: none; font-family: 'Outfit', sans-serif; font-size: 12px; cursor: pointer; font-weight: 600; }
        .btn-edit-sm { background: #fff3cd; color: #856404; }
        .btn-del-sm  { background: #f8d7da; color: #721c24; }
        .btn-sm:hover { opacity: 0.8; }

        .empty-msg {
            text-align: center; padding: 60px; color: #999;
            background: #fff; border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .empty-msg i { font-size: 3rem; margin-bottom: 15px; display: block; color: #28a745; }

        .modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; }
        .modal-bg.show { display: flex; }
        .modal { background: #fff; border-radius: 16px; padding: 30px; width: 90%; max-width: 500px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .modal h3 { color: #0f1f3d; margin-bottom: 20px; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .btn-cancel { background: #f0f0f0; color: #333; padding: 10px 20px; border-radius: 10px; border: none; cursor: pointer; font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body>
    <div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>
    <nav>
        <a href="HomePfe.html" class="logo"></a>
        <p style="color:rgb(131,131,131);font-size:10px;">Platforme Scolaire</p>
        <ul>
            <div style="color:#fff; font-size:17px;">👤 <?= htmlspecialchars($teacher_name) ?></div><br>
            <p style="color:rgb(131,131,131);font-size:10px;">Classroom:</p>
            <li><a href="enseignant.php">🏠 Dashboard</a></li>
            <li><a href="teacher_attendance.php">📅 Mark Attendance</a></li>
            <li><a href="teacher_students.php">👥 Student List</a></li>
            <li><a href="teacher_grades.php">⭐ Manage Grades</a></li>
            <li><a href="teacher_disciplinary.php" style="color:#f5c842;">⚠️ Disciplinary</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>⚠️ Disciplinary Records</h1>
            <p>Add or manage disciplinary records for your students.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- اختيار القسم -->
        <p style="font-size:13px;color:#555;margin-bottom:8px;font-weight:500;">Select Class:</p>
        <div class="tabs">
            <?php foreach ($classes as $c): ?>
                <a href="?classe=<?= urlencode($c) ?>"
                   class="tab-btn <?= trim($c) === trim($selected_classe) ? 'active' : '' ?>">
                    <?= htmlspecialchars($c) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- اختيار الطالب -->
        <?php if (!empty($students)): ?>
        <p style="font-size:13px;color:#555;margin-bottom:8px;font-weight:500;">Select Student:</p>
        <div class="tabs" style="margin-bottom:24px;">
            <?php foreach ($students as $s): ?>
                <a href="?classe=<?= urlencode($selected_classe) ?>&child_id=<?= $s['id'] ?>"
                   class="tab-btn <?= $s['id'] == $selected_child ? 'active' : '' ?>">
                    👦 <?= htmlspecialchars($s['child_name'] . ' ' . ($s['prenom'] ?? '')) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($selected_child): ?>

        <!-- فورم إضافة سجل -->
        <div class="add-card">
            <h3>➕ Ajouter un enregistrement</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="child_id" value="<?= $selected_child ?>">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Date</label>
                        <input type="date" name="date_inc" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-field">
                        <label>Gravité</label>
                        <select name="gravite">
                            <option value="low">📝 Low</option>
                            <option value="medium">⚠️ Medium</option>
                            <option value="high">🚨 High</option>
                        </select>
                    </div>
                    <div class="form-field form-full">
                        <label>Description</label>
                        <textarea name="description" placeholder="Décrivez l'incident..." required></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-add">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </form>
        </div>

        <!-- عرض السجلات -->
        <?php if (empty($records)): ?>
            <div class="empty-msg">
                <i class="fas fa-star"></i>
                No disciplinary records. Keep it up! 🌟
            </div>
        <?php else: ?>
            <?php foreach ($records as $r):
                $icon  = $r['gravite'] === 'high' ? '🚨' : ($r['gravite'] === 'medium' ? '⚠️' : '📝');
                $label = ucfirst($r['gravite']);
            ?>
            <div class="record-card <?= $r['gravite'] ?>">
                <div class="record-icon"><?= $icon ?></div>
                <div class="record-body">
                    <div class="record-date">📅 <?= date('d/m/Y', strtotime($r['date_inc'])) ?></div>
                    <div class="record-desc"><?= htmlspecialchars($r['description']) ?></div>
                    <span class="badge badge-<?= $r['gravite'] ?>"><?= $label ?> severity</span>
                    <div class="record-actions">
                        <button class="btn-sm btn-edit-sm" onclick="openEdit(
                            <?= $r['id'] ?>,
                            '<?= $r['date_inc'] ?>',
                            '<?= addslashes($r['description']) ?>',
                            '<?= $r['gravite'] ?>'
                        )"><i class="fas fa-edit"></i> Modifier</button>
                        <a href="?classe=<?= urlencode($selected_classe) ?>&child_id=<?= $selected_child ?>&delete=<?= $r['id'] ?>"
                           class="btn-sm btn-del-sm"
                           onclick="return confirm('Supprimer cet enregistrement?')"
                           style="text-decoration:none;display:inline-block;">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- Modal Edit -->
    <div class="modal-bg" id="editModal">
        <div class="modal">
            <h3>✏️ Modifier l'enregistrement</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="record_id" id="e_id">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Date</label>
                        <input type="date" name="date_inc" id="e_date" required>
                    </div>
                    <div class="form-field">
                        <label>Gravité</label>
                        <select name="gravite" id="e_gravite">
                            <option value="low">📝 Low</option>
                            <option value="medium">⚠️ Medium</option>
                            <option value="high">🚨 High</option>
                        </select>
                    </div>
                    <div class="form-field form-full">
                        <label>Description</label>
                        <textarea name="description" id="e_desc" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn-add" style="margin-top:0;">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEdit(id, date, desc, gravite) {
            document.getElementById('e_id').value      = id;
            document.getElementById('e_date').value    = date;
            document.getElementById('e_desc').value    = desc;
            document.getElementById('e_gravite').value = gravite;
            document.getElementById('editModal').classList.add('show');
        }
        function closeModal() {
            document.getElementById('editModal').classList.remove('show');
        }
        document.getElementById('editModal').addEventListener('click', e => {
            if (e.target === document.getElementById('editModal')) closeModal();
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
<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'enseignant') {
    header("Location: login.php"); exit;
}

$teacher_name = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'];
$success = $error = "";

// ── إضافة نقطة ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $child_id  = (int)$_POST['child_id'];
    $matiere   = trim($_POST['matiere']);
    $note      = (float)$_POST['note'];
    $trimestre = trim($_POST['trimestre']);

    if ($note < 0 || $note > 20) {
        $error = "❌ La note doit être entre 0 et 20.";
    } elseif (empty($matiere) || empty($trimestre)) {
        $error = "❌ Veuillez remplir tous les champs.";
    } else {
        // تحقق إذا النقطة موجودة مسبقاً لنفس الطالب ونفس المادة ونفس الفصل
        $check = $pdo->prepare("SELECT id FROM grades WHERE child_id=? AND matiere=? AND trimestre=?");
        $check->execute([$child_id, $matiere, $trimestre]);
        if ($check->fetch()) {
            $error = "❌ Cette note existe déjà pour cet élève. Modifiez-la à la place.";
        } else {
            $pdo->prepare("INSERT INTO grades (child_id, matiere, note, trimestre) VALUES (?,?,?,?)")
                ->execute([$child_id, $matiere, $note, $trimestre]);
            $success = "✅ Note ajoutée avec succès!";
        }
    }
}

// ── تعديل نقطة ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $grade_id  = (int)$_POST['grade_id'];
    $matiere   = trim($_POST['matiere']);
    $note      = (float)$_POST['note'];
    $trimestre = trim($_POST['trimestre']);

    if ($note < 0 || $note > 20) {
        $error = "❌ La note doit être entre 0 et 20.";
    } else {
        $pdo->prepare("UPDATE grades SET matiere=?, note=?, trimestre=? WHERE id=?")
            ->execute([$matiere, $note, $trimestre, $grade_id]);
        $success = "✅ Note modifiée avec succès!";
    }
}

// ── حذف نقطة ──
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM grades WHERE id=?")->execute([(int)$_GET['delete']]);
    $success = "✅ Note supprimée.";
}

// ── جلب الأقسام ──

$stmt    = $pdo->query("SELECT DISTINCT classe FROM children WHERE status='active' AND classe IS NOT NULL ORDER BY classe");
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);


// ── القسم والطالب المختاران ──
$selected_classe = $_GET['classe'] ?? ($classes[0] ?? '');
$selected_child  = $_GET['child_id'] ?? null;

// ── جلب الطلاب حسب القسم ──
$students = [];
if ($selected_classe) {
    $stmt = $pdo->prepare("SELECT * FROM children WHERE classe=? AND status='active' ORDER BY child_name");
    $stmt->execute([$selected_classe]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$selected_child && !empty($students)) {
        $selected_child = $students[0]['id'];
    }
}

// ── جلب النقاط ──
$grades = [];
if ($selected_child) {
    $stmt = $pdo->prepare("SELECT * FROM grades WHERE child_id=? ORDER BY trimestre, matiere");
    $stmt->execute([$selected_child]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$matieres   = ['Mathematics','Physics','Chemistry','Arabic','French','English','History','Geography','Philosophy','Informatique','Islamic Studies','Sport'];
$trimesters = ['Trimestre 1','Trimestre 2','Trimestre 3'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades – ECOLNA</title>
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

        /* tabs */
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab-btn {
            padding: 8px 20px; border-radius: 20px; border: 2px solid #e0e0e0;
            background: #fff; cursor: pointer; font-family: 'Outfit', sans-serif;
            font-size: 14px; text-decoration: none; color: #555; transition: all 0.2s;
        }
        .tab-btn.active, .tab-btn:hover { background: #0f1f3d; color: #f5c842; border-color: #0f1f3d; }

        /* add card */
        .add-card {
            background: #fff; border-radius: 16px; padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 28px;
            border-top: 4px solid #f5c842;
        }
        .add-card h3 { color: #0f1f3d; margin-bottom: 18px; font-size: 1.1rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap: 14px; }
        .form-field { display: flex; flex-direction: column; gap: 6px; }
        .form-field label { font-size: 13px; color: #555; font-weight: 500; }
        .form-field input, .form-field select {
            padding: 10px 14px; border: 1.5px solid #e0e0e0; border-radius: 10px;
            font-family: 'Outfit', sans-serif; font-size: 14px; outline: none; transition: border 0.2s;
        }
        .form-field input:focus, .form-field select:focus { border-color: #f5c842; }
        .btn-add {
            padding: 11px 28px; background: #0f1f3d; color: #f5c842;
            border: none; border-radius: 10px; font-family: 'Outfit', sans-serif;
            font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 10px;
            transition: opacity 0.2s;
        }
        .btn-add:hover { opacity: 0.85; }

        /* trimestre section */
        .trimestre-section { margin-bottom: 28px; }
        .trimestre-title {
            font-size: 1rem; font-weight: 600; color: #0f1f3d;
            margin-bottom: 12px; padding: 8px 16px;
            background: #f0f4ff; border-radius: 8px;
            border-left: 4px solid #f5c842;
        }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-good { background: #d4edda; color: #155724; }
        .badge-avg  { background: #fff3cd; color: #856404; }
        .badge-bad  { background: #f8d7da; color: #721c24; }
        .avg-row td { background: #f0f4ff; font-weight: 600; }

        .btn-sm {
            padding: 5px 12px; border-radius: 8px; border: none;
            font-family: 'Outfit', sans-serif; font-size: 12px; cursor: pointer; font-weight: 600;
        }
        .btn-edit-sm { background: #fff3cd; color: #856404; }
        .btn-del-sm  { background: #f8d7da; color: #721c24; }
        .btn-sm:hover { opacity: 0.8; }

        .empty-msg { text-align: center; padding: 50px; color: #999; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
        .empty-msg i { font-size: 3rem; margin-bottom: 15px; display: block; }

        /* Modal */
        .modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; }
        .modal-bg.show { display: flex; }
        .modal { background: #fff; border-radius: 16px; padding: 30px; width: 90%; max-width: 460px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
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
            <li><a href="teacher_students.php" >👥 Student List</a></li>
            <li><a href="teacher_grades.php" style="color:#f5c842;">⭐ Manage Grades</a></li>
            <li><a href="teacher_disciplinary.php">⚠️ Disciplinary</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>⭐ Manage Grades</h1>
            <p>Select a class and student to add or edit grades.</p>
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
                   class="tab-btn <?= $c === $selected_classe ? 'active' : '' ?>">
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

        <!-- فورم إضافة نقطة -->
        <div class="add-card">
            <h3>➕ Ajouter une note</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="child_id" value="<?= $selected_child ?>">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Matière</label>
                        <select name="matiere">
                            <?php foreach ($matieres as $m): ?>
                                <option value="<?= $m ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Note / 20</label>
                        <input type="number" name="note" min="0" max="20" step="0.25" placeholder="Ex: 14.5" required>
                    </div>
                    <div class="form-field">
                        <label>Trimestre</label>
                        <select name="trimestre">
                            <?php foreach ($trimesters as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-add">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </form>
        </div>

        <!-- عرض النقاط -->
        <?php if (empty($grades)): ?>
            <div class="empty-msg">
                <i class="fas fa-star"></i>
                Aucune note enregistrée pour cet élève.
            </div>
        <?php else:
            $byTrimestre = [];
            foreach ($grades as $g) {
                $byTrimestre[$g['trimestre']][] = $g;
            }
            foreach ($byTrimestre as $trimestre => $rows):
                $avg = array_sum(array_column($rows, 'note')) / count($rows);
        ?>
            <div class="trimestre-section">
                <div class="trimestre-title">📅 <?= htmlspecialchars($trimestre) ?> — Moyenne: <?= number_format($avg, 2) ?>/20</div>
                <table>
                    <tr>
                        <th>Matière</th>
                        <th>Note / 20</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($rows as $g):
                        $note  = $g['note'];
                        $badge = $note >= 14 ? 'badge-good' : ($note >= 10 ? 'badge-avg' : 'badge-bad');
                        $label = $note >= 14 ? 'Bien' : ($note >= 10 ? 'Moyen' : 'Insuffisant');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($g['matiere']) ?></td>
                        <td><strong><?= number_format($note, 2) ?></strong> / 20</td>
                        <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                        <td>
                            <button class="btn-sm btn-edit-sm" onclick="openEdit(
                                <?= $g['id'] ?>,
                                '<?= addslashes($g['matiere']) ?>',
                                <?= $g['note'] ?>,
                                '<?= addslashes($g['trimestre']) ?>'
                            )"><i class="fas fa-edit"></i> Modifier</button>
                            <a href="?classe=<?= urlencode($selected_classe) ?>&child_id=<?= $selected_child ?>&delete=<?= $g['id'] ?>"
                               class="btn-sm btn-del-sm"
                               onclick="return confirm('Supprimer cette note?')"
                               style="text-decoration:none;display:inline-block;">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="avg-row">
                        <td>📌 Moyenne du trimestre</td>
                        <td><strong><?= number_format($avg, 2) ?></strong> / 20</td>
                        <td>
                            <span class="badge <?= $avg >= 14 ? 'badge-good' : ($avg >= 10 ? 'badge-avg' : 'badge-bad') ?>">
                                <?= $avg >= 14 ? 'Excellent' : ($avg >= 10 ? 'Admis' : 'Échec') ?>
                            </span>
                        </td>
                        <td>—</td>
                    </tr>
                </table>
            </div>
        <?php endforeach; endif; ?>

        <?php endif; ?>
    </div>

    <!-- Modal Edit -->
    <div class="modal-bg" id="editModal">
        <div class="modal">
            <h3>✏️ Modifier la note</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="grade_id" id="e_id">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Matière</label>
                        <select name="matiere" id="e_matiere">
                            <?php foreach ($matieres as $m): ?>
                                <option value="<?= $m ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Note / 20</label>
                        <input type="number" name="note" id="e_note" min="0" max="20" step="0.25" required>
                    </div>
                    <div class="form-field">
                        <label>Trimestre</label>
                        <select name="trimestre" id="e_trimestre">
                            <?php foreach ($trimesters as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
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
        function openEdit(id, matiere, note, trimestre) {
            document.getElementById('e_id').value       = id;
            document.getElementById('e_matiere').value  = matiere;
            document.getElementById('e_note').value     = note;
            document.getElementById('e_trimestre').value = trimestre;
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

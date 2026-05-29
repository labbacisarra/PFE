<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'enseignant') {
    header("Location: login.php");
    exit;
}

$teacher_name = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'];

$success = "";
$error   = "";

/* ───────── ADD ABSENCE ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {

    $child_id = (int)$_POST['child_id'];
    $motif    = trim($_POST['motif']);
    $justifie = (int)$_POST['justifie'];
    $date_abs = $_POST['date_abs'];

    if (empty($motif) || empty($date_abs)) {
        $error = "❌ Please fill in all required fields.";
    } else {

        $check = $pdo->prepare("
            SELECT id
            FROM absences
            WHERE child_id=? AND date_abs=?
        ");

        $check->execute([$child_id, $date_abs]);

        if ($check->fetch()) {
            $error = "❌ An absence record already exists for this date.";
        } else {

            $stmt = $pdo->prepare("
                INSERT INTO absences
                (child_id, motif, justifie, date_abs)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $child_id,
                $motif,
                $justifie,
                $date_abs
            ]);

            $success = "✅ Absence added successfully!";
        }
    }
}

/* ───────── EDIT ABSENCE ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    $absence_id = (int)$_POST['absence_id'];
    $motif      = trim($_POST['motif']);
    $justifie   = (int)$_POST['justifie'];
    $date_abs   = $_POST['date_abs'];

    if (empty($motif) || empty($date_abs)) {
        $error = "❌ Please fill in all required fields.";
    } else {

        $stmt = $pdo->prepare("
            UPDATE absences
            SET motif=?,
                justifie=?,
                date_abs=?
            WHERE id=?
        ");

        $stmt->execute([
            $motif,
            $justifie,
            $date_abs,
            $absence_id
        ]);

        $success = "✅ Absence updated successfully!";
    }
}

/* ───────── DELETE ABSENCE ───────── */
if (isset($_GET['delete'])) {

    $stmt = $pdo->prepare("
        DELETE FROM absences
        WHERE id=?
    ");

    $stmt->execute([
        (int)$_GET['delete']
    ]);

    $success = "✅ Absence deleted successfully.";
}

/* ───────── CLASSES ───────── */
$stmt = $pdo->query("
    SELECT DISTINCT classe
    FROM children
    WHERE status='active'
    ORDER BY classe
");

$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* ───────── SELECTED CLASS ───────── */
$selected_classe = $_GET['classe'] ?? ($classes[0] ?? '');
$selected_child  = $_GET['child_id'] ?? null;

/* ───────── STUDENTS ───────── */
$students = [];

if ($selected_classe) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM children
        WHERE classe=?
        AND status='active'
        ORDER BY child_name
    ");

    $stmt->execute([$selected_classe]);

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$selected_child && !empty($students)) {
        $selected_child = $students[0]['id'];
    }
}

/* ───────── ABSENCES ───────── */
$absences = [];

if ($selected_child) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM absences
        WHERE child_id=?
        ORDER BY date_abs DESC
    ");

    $stmt->execute([$selected_child]);

    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Absences – ECOLNA</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="parent.css">

    <style>

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #1a1a2e;
        }

        .page-header p {
            color: #666;
            margin-top: 5px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .alert-success {
            background: rgba(40,167,69,0.15);
            color: #155724;
            border: 1px solid rgba(40,167,69,0.3);
        }

        .alert-error {
            background: rgba(220,53,69,0.15);
            color: #721c24;
            border: 1px solid rgba(220,53,69,0.3);
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 8px 20px;
            border-radius: 20px;
            border: 2px solid #e0e0e0;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            color: #555;
        }

        .tab-btn.active,
        .tab-btn:hover {
            background: #0f1f3d;
            color: #f5c842;
            border-color: #0f1f3d;
        }

        .add-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            margin-bottom: 28px;
            border-top: 4px solid #f5c842;
        }

        .add-card h3 {
            color: #0f1f3d;
            margin-bottom: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill,minmax(180px,1fr));
            gap: 14px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-field label {
            font-size: 13px;
            color: #555;
            font-weight: 500;
        }

        .form-field input,
        .form-field select {
            padding: 10px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            outline: none;
            font-family: 'Outfit', sans-serif;
        }

        .form-field input:focus,
        .form-field select:focus {
            border-color: #f5c842;
        }

        .btn-add {
            padding: 11px 28px;
            background: #0f1f3d;
            color: #f5c842;
            border: none;
            border-radius: 10px;
            margin-top: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-add:hover {
            opacity: .85;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-good {
            background: #d4edda;
            color: #155724;
        }

        .badge-bad {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-sm {
            padding: 5px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-edit-sm {
            background: #fff3cd;
            color: #856404;
        }

        .btn-del-sm {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-msg {
            text-align: center;
            padding: 50px;
            color: #999;
            background: #fff;
            border-radius: 12px;
        }
.modal-bg {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 200;
    align-items: center;
    justify-content: center;
}

.modal-bg.show {
    display: flex;
}

.modal {
    background: white;
    border-radius: 16px;
    padding: 30px;
    width: 90%;
    max-width: 460px;
    box-shadow: 0 8px 32px rgba(0,0,0,.2);
}

.modal h3 {
    color: #0f1f3d;
    margin-bottom: 20px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.btn-cancel {
    background: #f0f0f0;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
}
    </style>
</head>

<body>

<div class="hamburger" id="hamburger">
    <i class="fa fa-bars"></i>
</div>

<nav>

    <a href="HomePfe.html" class="logo"></a>

    <p style="color:rgb(131,131,131);font-size:10px;">
        Platforme Scolaire
    </p>

    <ul>

        <div style="color:#fff;font-size:17px;">
            👤 <?= htmlspecialchars($teacher_name) ?>
        </div>

        <br>

        <li><a href="enseignant.php">🏠 Dashboard</a></li>

        <li>
            <a href="teacher_attendance.php"
               style="color:#f5c842;">
                📅 Manage Absences
            </a>
        </li>

        <li><a href="teacher_students.php">👥 Student List</a></li>

        <li><a href="teacher_grades.php">⭐ Manage Grades</a></li>

        <li><a href="teacher_disciplinary.php">⚠️ Disciplinary</a></li>

        <li>
            <a href="login.php"
               style="color:#ff6b6b;">
               🚪 Logout
            </a>
        </li>

    </ul>

</nav>

<div class="container">

    <div class="page-header">

        <h1>📅 Manage Absences</h1>

        <p>
            Select a class and student to manage absences.
        </p>

    </div>

    <?php if($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <p style="font-size:13px;color:#555;margin-bottom:8px;font-weight:500;">
        Select Class:
    </p>

    <div class="tabs">

        <?php foreach($classes as $c): ?>

            <a href="?classe=<?= urlencode($c) ?>"
               class="tab-btn <?= $c === $selected_classe ? 'active' : '' ?>">

                <?= htmlspecialchars($c) ?>

            </a>

        <?php endforeach; ?>

    </div>

    <?php if(!empty($students)): ?>

    <p style="font-size:13px;color:#555;margin-bottom:8px;font-weight:500;">
        Select Student:
    </p>

    <div class="tabs">

        <?php foreach($students as $s): ?>

            <a href="?classe=<?= urlencode($selected_classe) ?>&child_id=<?= $s['id'] ?>"
               class="tab-btn <?= $s['id'] == $selected_child ? 'active' : '' ?>">

               👦 <?= htmlspecialchars($s['child_name'].' '.($s['prenom'] ?? '')) ?>

            </a>

        <?php endforeach; ?>

    </div>

    <?php endif; ?>

    <?php if($selected_child): ?>

    <div class="add-card">

        <h3>➕ Add Absence</h3>

        <form method="POST">

            <input type="hidden"
                   name="action"
                   value="add">

            <input type="hidden"
                   name="child_id"
                   value="<?= $selected_child ?>">

            <div class="form-grid">

                <div class="form-field">

                    <label>Date</label>

                    <input type="date"
                           name="date_abs"
                           required>

                </div>

                <div class="form-field">

                    <label>Reason</label>

                    <input type="text"
                           name="motif"
                           placeholder="Medical appointment"
                           required>

                </div>

                <div class="form-field">

                    <label>Status</label>

                    <select name="justifie">

                        <option value="1">
                            Justified
                        </option>

                        <option value="0">
                            Unjustified
                        </option>

                    </select>

                </div>

            </div>

            <button type="submit" class="btn-add">
                <i class="fas fa-plus"></i>
                Add Absence
            </button>

        </form>

    </div>

    <?php if(empty($absences)): ?>

<div class="empty-msg">
    <i class="fas fa-calendar-times"></i>
    No absence records found for this student.
</div>

<?php else: ?>

<table>

    <tr>
        <th>Date</th>
        <th>Reason</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <?php foreach($absences as $a): ?>

    <tr>

        <td>
            <?= htmlspecialchars($a['date_abs']) ?>
        </td>

        <td>
            <?= htmlspecialchars($a['motif']) ?>
        </td>

        <td>

            <?php if($a['justifie']): ?>

                <span class="badge badge-good">
                    Justified
                </span>

            <?php else: ?>

                <span class="badge badge-bad">
                    Unjustified
                </span>

            <?php endif; ?>

        </td>

        <td>

            <button
                class="btn-sm btn-edit-sm"
                onclick="openEdit(
                    <?= $a['id'] ?>,
                    '<?= addslashes($a['motif']) ?>',
                    <?= $a['justifie'] ?>,
                    '<?= $a['date_abs'] ?>'
                )">

                <i class="fas fa-edit"></i>
                Edit

            </button>

            <a
                href="?classe=<?= urlencode($selected_classe) ?>&child_id=<?= $selected_child ?>&delete=<?= $a['id'] ?>"
                class="btn-sm btn-del-sm"
                style="text-decoration:none;display:inline-block;"
                onclick="return confirm('Delete this absence record?')">

                <i class="fas fa-trash"></i>
                Delete

            </a>

        </td>

    </tr>

    <?php endforeach; ?>

</table>

<?php endif; ?>

<?php endif; ?>

</div>


<!-- EDIT MODAL -->

<div class="modal-bg" id="editModal">

    <div class="modal">

        <h3>✏️ Edit Absence</h3>

        <form method="POST">

            <input type="hidden"
                   name="action"
                   value="edit">

            <input type="hidden"
                   name="absence_id"
                   id="e_id">

            <div class="form-grid">

                <div class="form-field">

                    <label>Date</label>

                    <input type="date"
                           name="date_abs"
                           id="e_date"
                           required>

                </div>

                <div class="form-field">

                    <label>Reason</label>

                    <input type="text"
                           name="motif"
                           id="e_motif"
                           required>

                </div>

                <div class="form-field">

                    <label>Status</label>

                    <select name="justifie"
                            id="e_justifie">

                        <option value="1">
                            Justified
                        </option>

                        <option value="0">
                            Unjustified
                        </option>

                    </select>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn-cancel"
                        onclick="closeModal()">

                    Cancel

                </button>

                <button type="submit"
                        class="btn-add"
                        style="margin-top:0;">

                    <i class="fas fa-save"></i>
                    Save

                </button>

            </div>

        </form>

    </div>

</div>


<script>

function openEdit(id, motif, justifie, date_abs)
{
    document.getElementById('e_id').value = id;
    document.getElementById('e_motif').value = motif;
    document.getElementById('e_justifie').value = justifie;
    document.getElementById('e_date').value = date_abs;

    document
        .getElementById('editModal')
        .classList.add('show');
}

function closeModal()
{
    document
        .getElementById('editModal')
        .classList.remove('show');
}

document
.getElementById('editModal')
.addEventListener('click', function(e)
{
    if(e.target === this)
    {
        closeModal();
    }
});


const hamburger =
document.getElementById('hamburger');

const nav =
document.querySelector('nav');

hamburger.addEventListener('click', () =>
{
    nav.classList.toggle('active');

    const icon =
    hamburger.querySelector('i');

    icon.classList.toggle('fa-bars');
    icon.classList.toggle('fa-times');
});

document
.querySelectorAll('nav ul li a')
.forEach(link =>
{
    link.addEventListener('click', () =>
    {
        nav.classList.remove('active');

        const icon =
        hamburger.querySelector('i');

        icon.classList.add('fa-bars');
        icon.classList.remove('fa-times');
    });
});

</script>

</body>
</html>
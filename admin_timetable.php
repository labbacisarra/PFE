<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$success = $error = "";
$classes = ['1AS1','1AS2','2AS1','2AS2','3AS1','3AS2','4AS1','4AS2','5AS1','5AS2'];
$jours   = ['Sunday','Monday','Tuesday','Wednesday','Thursday'];
$heures  = [
    ['08:00','09:00'],['09:00','10:00'],['10:00','11:00'],
    ['11:00','12:00'],['14:00','15:00'],['15:00','16:00']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $classe     = $_POST['classe'];
    $jour       = $_POST['jour'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin  = $_POST['heure_fin'];
    $matiere    = trim($_POST['matiere']);
    $enseignant = trim($_POST['enseignant'] ?? '');

    $check = $pdo->prepare("SELECT id FROM emploi_temps WHERE classe=? AND jour=? AND heure_debut=?");
    $check->execute([$classe, $jour, $heure_debut]);
    if ($check->fetch()) {
        $error = "❌ This slot is already taken!";
    } else {
        $pdo->prepare("INSERT INTO emploi_temps (classe, jour, heure_debut, heure_fin, matiere) VALUES (?,?,?,?,?)")
            ->execute([$classe, $jour, $heure_debut, $heure_fin, $matiere]);
        $success = "✅ Slot added successfully!";
    }
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM emploi_temps WHERE id=?")->execute([(int)$_GET['delete']]);
    $success = "✅ Slot deleted.";
}

$selected_classe = $_GET['classe'] ?? $classes[0];
$stmt = $pdo->prepare("SELECT * FROM emploi_temps WHERE classe = ?");
$stmt->execute([$selected_classe]);
$data = [];
$rows_with_id = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $data[$r['heure_debut']][$r['jour']] = $r['matiere'];
    $rows_with_id[$r['heure_debut']][$r['jour']] = $r['id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Timetable</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 2rem; color: #0f1f3d; }
        .page-header p { color: #666; margin-top: 5px; }

        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert-success { background: rgba(40,167,69,0.15); color: #155724; border: 1px solid rgba(40,167,69,0.3); }
        .alert-error   { background: rgba(220,53,69,0.15); color: #721c24; border: 1px solid rgba(220,53,69,0.3); }

        .class-tabs { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
        .class-tab {
            padding: 8px 20px; border-radius: 20px; border: 2px solid #e0e0e0;
            background: #fff; text-decoration: none; color: #555; font-size: 14px;
            font-family: 'Outfit', sans-serif; transition: all 0.2s;
        }
        .class-tab.active, .class-tab:hover { background: #0f1f3d; color: #f5c842; border-color: #0f1f3d; }

        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 30px;}
        th { background: #0f1f3d; color: #f5c842; padding: 12px; font-size: 13px; }
        td { padding: 10px; font-size: 13px; text-align: center; border: 1px solid #e2e8f0; color: #1a2540; }
        tr:hover td { background: #f8fafc; }

        .cell-content { display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .delete-btn {
            background: #ff4444; color: #fff; border: none; border-radius: 6px;
            padding: 3px 8px; font-size: 11px; cursor: pointer; display: none;
        }
        td:hover .delete-btn { display: inline-block; }

        .add-card {
            background: #fff; border-radius: 16px; padding: 28px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 30px;
        }
        .add-card h3 { color: #0f1f3d; margin-bottom: 18px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
        .form-field { display: flex; flex-direction: column; gap: 6px; }
        .form-field label { font-size: 13px; color: #555; font-weight: 500; }
        .form-field input, .form-field select {
            padding: 10px 14px; border: 1.5px solid #e0e0e0; border-radius: 10px;
            font-family: 'Outfit', sans-serif; font-size: 14px; outline: none;
        }
        .form-field input:focus, .form-field select:focus { border-color: #f5c842; }
        .btn { padding: 11px 28px; background: #0f1f3d; color: #f5c842; border: none; border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>
    <nav>
        <a href="index.html" class="logo"></a>
        <p style="color:rgb(131,131,131);font-size:10px;">School Platform</p>
        <ul>
            <div style="color:#fff;font-size:17px;">
                👤 <?= htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']) ?>
            </div><br>
            <p style="color:rgb(131,131,131);font-size:10px;">Administration:</p>
            <li><a href="admin.php">🏠 Dashboard</a></li>
            <li><a href="admin_users.php">👥 Users</a></li>
            <li><a href="admin_timetable.php" style="color:#f5c842;">🕐 Timetable</a></li>
            <li><a href="admin_inscriptions.php">📋 Inscriptions</a></li>
            <li><a href="admin_statistics.php">📊 Statistics</a></li>
            <li><a href="admin_analysis.php">🔍 Analysis</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>🕐 Manage Timetable</h1>
            <p>Add or remove slots for each class.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <div class="class-tabs">
            <?php foreach ($classes as $c): ?>
                <a href="?classe=<?= $c ?>"
                   class="class-tab <?= $c === $selected_classe ? 'active' : '' ?>">
                    <?= $c ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="overflow-x:auto; width:100%;">

        <table style="min-width:490px;" >
            <tr>
                <th>Time</th>
                <?php foreach ($jours as $j): ?><th><?= $j ?></th><?php endforeach; ?>
            </tr>
            <?php foreach ($heures as $h): ?>
            <tr>
                <td><strong><?= $h[0] ?>–<?= $h[1] ?></strong></td>
                <?php foreach ($jours as $j): ?>
                <td>
                    <?php if (isset($data[$h[0]][$j])): ?>
                        <div class="cell-content">
                            <span><?= htmlspecialchars($data[$h[0]][$j]) ?></span>
                            <a href="?classe=<?= $selected_classe ?>&delete=<?= $rows_with_id[$h[0]][$j] ?>"
                               onclick="return confirm('Delete this slot?')"
                               class="delete-btn">✕ Delete</a>
                        </div>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            
        </table>
        </div>

        <div class="add-card">
            <h3>➕ Add New Slot</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Class</label>
                        <select name="classe">
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c ?>" <?= $c === $selected_classe ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Day</label>
                        <select name="jour">
                            <?php foreach ($jours as $j): ?>
                                <option value="<?= $j ?>"><?= $j ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Start Time</label>
                        <select name="heure_debut">
                            <?php foreach ($heures as $h): ?>
                                <option value="<?= $h[0] ?>"><?= $h[0] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>End Time</label>
                        <select name="heure_fin">
                            <?php foreach ($heures as $h): ?>
                                <option value="<?= $h[1] ?>"><?= $h[1] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Subject</label>
                        <input type="text" name="matiere" placeholder="Ex: Mathematics" required>
                    </div>
                </div>
                <button type="submit" class="btn"><i class="fas fa-plus"></i> Add Slot</button>
            </form>
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

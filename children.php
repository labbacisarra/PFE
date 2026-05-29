<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'parent') {
    header("Location: login.php"); exit;
}
$parent_id = $_SESSION['user']['id'];
$success = $error = "";

// ── إضافة ولد ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $child_name        = trim($_POST['child_name']);
        $prenom            = trim($_POST['prenom']);
        $date_naissance    = $_POST['date_naissance'];
        $lieu_naissance    = trim($_POST['lieu_naissance']);
        $situation         = $_POST['situation_familiale'];
        $niveau            = trim($_POST['niveau']);

        if (empty($child_name) || empty($prenom)) {
            $error = "❌ Veuillez remplir le nom et prénom.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO children 
                (parent_id, child_name, prenom, date_naissance, lieu_naissance, situation_familiale, niveau, classe, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$parent_id, $child_name, $prenom, $date_naissance, $lieu_naissance, $situation, $niveau, $niveau]);
            $success = "✅ Dossier envoyé! En attente de validation par l'administrateur.";
        }
    }

    if ($_POST['action'] === 'delete') {
        $child_id = (int)$_POST['child_id'];
        $pdo->prepare("DELETE FROM children WHERE id = ? AND parent_id = ?")->execute([$child_id, $parent_id]);
        $success = "✅ Enfant supprimé.";
    }

    if ($_POST['action'] === 'edit') {
        $child_id       = (int)$_POST['child_id'];
        $child_name     = trim($_POST['child_name']);
        $prenom         = trim($_POST['prenom']);
        $date_naissance = $_POST['date_naissance'];
        $lieu_naissance = trim($_POST['lieu_naissance']);
        $situation      = $_POST['situation_familiale'];
        $niveau         = trim($_POST['niveau']);

        $pdo->prepare("UPDATE children SET 
            child_name=?, prenom=?, date_naissance=?, lieu_naissance=?, 
            situation_familiale=?, niveau=?, status='pending'
            WHERE id=? AND parent_id=?")
            ->execute([$child_name, $prenom, $date_naissance, $lieu_naissance, $situation, $niveau, $child_id, $parent_id]);
        $success = "✅ Dossier mis à jour et renvoyé pour validation.";
    }
}

// جلب الأولاد
$stmt = $pdo->prepare("SELECT * FROM children WHERE parent_id = ? ORDER BY id");
$stmt->execute([$parent_id]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Children</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 2rem; color: #1a1a2e; }
        .page-header p  { color: #666; margin-top: 5px; }

        .add-card {
            background: #fff; border-radius: 16px; padding: 28px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 30px;
        }
        .add-card h3 { color: #1a1a2e; margin-bottom: 18px; font-size: 1.1rem; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
        .form-field { display: flex; flex-direction: column; gap: 6px; }
        .form-field label { font-size: 13px; color: #555; font-weight: 500; }
        .form-field input, .form-field select, .form-field textarea {
            padding: 10px 14px; border: 1.5px solid #e0e0e0; border-radius: 10px;
            font-family: 'Outfit', sans-serif; font-size: 14px; outline: none;
            transition: border 0.2s;
        }
        .form-field input:focus, .form-field select:focus { border-color: #f5c842; }

        .btn-add {
            padding: 11px 28px; background: #1a1a2e; color: #f5c842;
            border: none; border-radius: 10px; font-family: 'Outfit', sans-serif;
            font-size: 15px; font-weight: 600; cursor: pointer;
            transition: opacity 0.2s; margin-top: 14px;
        }
        .btn-add:hover { opacity: 0.85; }

        .children-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .child-card {
            background: #fff; border-radius: 16px; padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            display: flex; flex-direction: column; gap: 6px;
            border-top: 4px solid #f5c842;
        }
        .child-card.pending  { border-top-color: #ffc107; }
        .child-card.rejected { border-top-color: #dc3545; }
        .child-card.active   { border-top-color: #28a745; }

        .child-avatar { font-size: 2.5rem; text-align: center; margin-bottom: 8px; }
        .child-name { font-size: 1.1rem; font-weight: 600; color: #1a1a2e; text-align: center; }
        .child-info { font-size: 12px; color: #888; display: flex; gap: 6px; align-items: center; }
        .child-info i { color: #f5c842; }

        .status-badge {
            text-align: center; padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; margin: 6px auto; display: inline-block;
        }
        .status-pending  { background: #fff3cd; color: #856404; }
        .status-active   { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .card-actions { display: flex; gap: 8px; margin-top: 10px; }
        .btn-edit {
            flex: 1; padding: 8px; background: #fff8e1;
            color: #1a1a2e; border: 1.5px solid #f5c842; border-radius: 8px;
            font-family: 'Outfit', sans-serif; font-size: 13px; cursor: pointer;
        }
        .btn-delete {
            flex: 1; padding: 8px; background: #fff0f0;
            color: #dc3545; border: 1.5px solid #f8d7da; border-radius: 8px;
            font-family: 'Outfit', sans-serif; font-size: 13px; cursor: pointer;
        }
        .btn-edit:hover   { background: #fff3cd; }
        .btn-delete:hover { background: #f8d7da; }

        .empty-msg {
            text-align: center; padding: 40px; color: #999;
            background: #fff; border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .empty-msg i { font-size: 3rem; margin-bottom: 15px; display: block; }

        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert-success { background: rgba(40,167,69,0.15); color: #155724; border: 1px solid rgba(40,167,69,0.3); }
        .alert-error   { background: rgba(220,53,69,0.15); color: #721c24; border: 1px solid rgba(220,53,69,0.3); }

        /* Modal */
        .modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; }
        .modal-bg.show { display: flex; }
        .modal { background: #fff; border-radius: 16px; padding: 30px; width: 90%; max-width: 560px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
        .modal h3 { color: #1a1a2e; margin-bottom: 20px; }
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
            <div class="parent" style="color:#fff; font-size:17px">
                <?= htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']) ?>
            </div><br>
            <p style="color:rgb(131,131,131);font-size:10px;">Suivi Scolaire:</p>
            <li><a href="dashboard.php">🏠 Dashboard</a></li>

            <li><a href="grades.php">📊 Grades & Reports</a></li>
            <li><a href="absences.php">📅 Absences</a></li>
            <li><a href="disciplinary.php">⚠️ Disciplinary Records</a></li>
            <li><a href="parent.php">🕐 Timetable</a></li>
            <li><a href="children.php" style="color:#f5c842;">👦 My Children</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>👦 My Children</h1>
            <p>Ajoutez le dossier de votre enfant — l'administrateur le validera.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- فورم إضافة ولد -->
        <div class="add-card">
            <h3>➕ Ajouter un enfant</h3>
            <form method="POST" action="children.php">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Nom <span style="color:red">*</span></label>
                        <input type="text" name="child_name" placeholder="Nom de famille" required>
                    </div>
                    <div class="form-field">
                        <label>Prénom <span style="color:red">*</span></label>
                        <input type="text" name="prenom" placeholder="Prénom" required>
                    </div>
                    <div class="form-field">
                        <label>Date de naissance</label>
                        <input type="date" name="date_naissance">
                    </div>
                    <div class="form-field">
                        <label>Lieu de naissance</label>
                        <input type="text" name="lieu_naissance" placeholder="Ex: Oran">
                    </div>
                    <div class="form-field">
                        <label>Situation familiale</label>
                        <select name="situation_familiale">
                            <option value="famille_complete">Famille complète</option>
                            <option value="orphelin">Orphelin</option>
                            <option value="parents_divorces">Parents divorcés</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Niveau / Classe</label>
                        <input type="text" name="niveau" placeholder="Ex: 3AS1">
                    </div>
                </div>
                <button type="submit" class="btn-add">
                    <i class="fas fa-paper-plane"></i> Envoyer le dossier
                </button>
            </form>
        </div>

        <!-- قائمة الأولاد -->
        <?php if (empty($children)): ?>
            <div class="empty-msg">
                <i class="fas fa-child"></i>
                Aucun enfant ajouté. Remplissez le formulaire ci-dessus!
            </div>
        <?php else: ?>
            <h2 style="color:#1a1a2e;margin-bottom:16px;">Dossiers envoyés</h2>
            <div class="children-grid">
                <?php foreach ($children as $child):
                    $status = $child['status'] ?? 'pending';
                    $status_label = $status === 'active' ? '✅ Validé' : ($status === 'rejected' ? '❌ Refusé' : '⏳ En attente');
                    $status_cls = 'status-' . $status;
                ?>
                <div class="child-card <?= $status ?>">
                    <div class="child-avatar">👦</div>
                    <div class="child-name"><?= htmlspecialchars($child['child_name'] . ' ' . ($child['prenom'] ?? '')) ?></div>
                    <div style="text-align:center;">
                        <span class="status-badge <?= $status_cls ?>"><?= $status_label ?></span>
                    </div>
                    <?php if (!empty($child['date_naissance'])): ?>
                    <div class="child-info"><i class="fas fa-birthday-cake"></i> <?= date('d/m/Y', strtotime($child['date_naissance'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($child['lieu_naissance'])): ?>
                    <div class="child-info"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($child['lieu_naissance']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($child['situation_familiale'])): ?>
                    <div class="child-info"><i class="fas fa-home"></i> <?= htmlspecialchars(str_replace('_', ' ', $child['situation_familiale'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($child['niveau'])): ?>
                    <div class="child-info"><i class="fas fa-school"></i> <?= htmlspecialchars($child['niveau']) ?></div>
                    <?php endif; ?>

                    <div class="card-actions">
    <?php if ($status !== 'active'): ?>
        <button class="btn-edit" onclick="openEdit(
            <?= $child['id'] ?>,
            '<?= addslashes($child['child_name']) ?>',
            '<?= addslashes($child['prenom'] ?? '') ?>',
            '<?= $child['date_naissance'] ?? '' ?>',
            '<?= addslashes($child['lieu_naissance'] ?? '') ?>',
            '<?= $child['situation_familiale'] ?? 'famille_complete' ?>',
            '<?= addslashes($child['niveau'] ?? '') ?>'
        )">
            <i class="fas fa-edit"></i> Modifier
        </button>
    <?php else: ?>
        <div style="
            flex:1; padding:8px; background:#d4edda; color:#155724;
            border-radius:8px; font-size:13px; text-align:center;
            font-family:'Outfit',sans-serif;
        ">
            <i class="fas fa-lock"></i> Validé
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return confirm('Supprimer cet enfant?')">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="child_id" value="<?= $child['id'] ?>">
        <button type="submit" class="btn-delete">
            <i class="fas fa-trash"></i> Supprimer
        </button>
    </form>
</div>                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Edit -->
    <div class="modal-bg" id="editModal">
        <div class="modal">
            <h3>✏️ Modifier le dossier</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="child_id" id="e_id">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Nom</label>
                        <input type="text" name="child_name" id="e_nom" required>
                    </div>
                    <div class="form-field">
                        <label>Prénom</label>
                        <input type="text" name="prenom" id="e_prenom" required>
                    </div>
                    <div class="form-field">
                        <label>Date de naissance</label>
                        <input type="date" name="date_naissance" id="e_date">
                    </div>
                    <div class="form-field">
                        <label>Lieu de naissance</label>
                        <input type="text" name="lieu_naissance" id="e_lieu">
                    </div>
                    <div class="form-field">
                        <label>Situation familiale</label>
                        <select name="situation_familiale" id="e_situation">
                            <option value="famille_complete">Famille complète</option>
                            <option value="orphelin">Orphelin</option>
                            <option value="parents_divorces">Parents divorcés</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Niveau / Classe</label>
                        <input type="text" name="niveau" id="e_niveau">
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
        function openEdit(id, nom, prenom, date, lieu, situation, niveau) {
            document.getElementById('e_id').value        = id;
            document.getElementById('e_nom').value       = nom;
            document.getElementById('e_prenom').value    = prenom;
            document.getElementById('e_date').value      = date;
            document.getElementById('e_lieu').value      = lieu;
            document.getElementById('e_situation').value = situation;
            document.getElementById('e_niveau').value    = niveau;
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
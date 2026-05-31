<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$success = "";

// ── GET Actions ──
if (isset($_GET['approve'])) {
    $pdo->prepare("UPDATE children SET status='active' WHERE id=?")->execute([(int)$_GET['approve']]);
    $success = "✅ Child record approved successfully.";
}
if (isset($_GET['reject'])) {
    $pdo->prepare("UPDATE children SET status='rejected' WHERE id=?")->execute([(int)$_GET['reject']]);
    $success = "❌ Child record rejected.";
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM children WHERE id=?")->execute([(int)$_GET['delete']]);
    $success = "🗑️ Child record deleted.";
}

// ── POST Action : edit (only active) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id             = (int)$_POST['child_id'];
    $child_name     = trim($_POST['child_name']);
    $prenom         = trim($_POST['prenom']);
    $date_naissance = $_POST['date_naissance'];
    $lieu_naissance = trim($_POST['lieu_naissance']);
    $situation      = $_POST['situation_familiale'];
    $niveau         = trim($_POST['niveau']);

    // تحقق أن الولد في حالة active فقط
    $check = $pdo->prepare("SELECT status FROM children WHERE id=?");
    $check->execute([$id]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['status'] === 'active') {
        $pdo->prepare("UPDATE children SET 
            child_name=?, prenom=?, date_naissance=?, lieu_naissance=?, 
            situation_familiale=?, niveau=?
            WHERE id=?")
            ->execute([$child_name, $prenom, $date_naissance, $lieu_naissance, $situation, $niveau, $id]);
        $success = "✅ Child record updated successfully.";
    } else {
        $success = "❌ You can only edit approved records.";
    }
}

// ── Search & Filter ──
$search        = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? 'all';

$where  = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $where   .= " AND (c.child_name LIKE ? OR c.prenom LIKE ? OR c.niveau LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($filter_status !== 'all') {
    $where   .= " AND c.status = ?";
    $params[] = $filter_status;
}

$stmt = $pdo->prepare("
    SELECT c.*, u.first_name AS parent_fname, u.last_name AS parent_lname, u.email AS parent_email
    FROM children c
    JOIN users u ON u.id = c.parent_id
    $where
    ORDER BY c.id DESC
");
$stmt->execute($params);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Stats ──
$stats = $pdo->query("
    SELECT 
        SUM(status='pending')  AS pending,
        SUM(status='active')   AS active,
        SUM(status='rejected') AS rejected
    FROM children
")->fetch(PDO::FETCH_ASSOC);

$pending_list  = array_filter($children, fn($c) => $c['status'] === 'pending');
$active_list   = array_filter($children, fn($c) => $c['status'] === 'active');
$rejected_list = array_filter($children, fn($c) => $c['status'] === 'rejected');

$situation_map = [
    'famille_complete' => 'Complete Family',
    'orphelin'         => 'Orphan',
    'parents_divorces' => 'Divorced Parents',
    'autre'            => 'Other',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Children Records — Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        .stats-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: #fff; border-radius: 14px; padding: 18px 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); display: flex; align-items: center; gap: 14px; cursor: pointer; transition: transform 0.15s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .stat-icon.pending  { background: #fff3cd; }
        .stat-icon.active   { background: #d4edda; }
        .stat-icon.rejected { background: #f8d7da; }
        .stat-info strong { font-size: 1.5rem; color: #1a1a2e; display: block; line-height: 1; }
        .stat-info span   { font-size: 12px; color: #888; }

        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 2rem; color: #0f1f3d; }
        .page-header p  { color: #666; margin-top: 5px; }

        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert-success { background: rgba(40,167,69,0.15); color: #155724; border: 1px solid rgba(40,167,69,0.3); }

        .search-box { background: #fff; border-radius: 14px; padding: 18px 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 24px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .search-input-wrap { flex: 1; min-width: 220px; display: flex; align-items: center; gap: 10px; background: #f8f9fa; border-radius: 10px; padding: 10px 14px; border: 1.5px solid #e0e0e0; transition: border 0.2s; }
        .search-input-wrap:focus-within { border-color: #f5c842; }
        .search-input-wrap i { color: #aaa; }
        .search-input-wrap input { border: none; background: transparent; outline: none; font-family: 'Outfit', sans-serif; font-size: 14px; width: 100%; }
        .filter-select { padding: 10px 14px; border: 1.5px solid #e0e0e0; border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 14px; outline: none; background: #f8f9fa; cursor: pointer; }
        .filter-select:focus { border-color: #f5c842; }
        .btn-search { padding: 10px 22px; background: #1a1a2e; color: #f5c842; border: none; border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .btn-search:hover { opacity: 0.85; }
        .btn-clear { padding: 10px 16px; background: #f0f0f0; color: #555; border: none; border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 14px; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 6px; }

        .section-title { font-size: 1.1rem; font-weight: 600; color: #0f1f3d; margin: 28px 0 16px; display: flex; align-items: center; gap: 10px; padding-bottom: 10px; border-bottom: 2px solid #f5c842; }
        .count-badge { border-radius: 20px; padding: 2px 10px; font-size: 13px; font-weight: 600; background: #ffc107; color: #1a1a2e; }

        .children-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; margin-bottom: 10px; }

        .child-card { background: #fff; border-radius: 16px; padding: 22px; box-shadow: 0 2px 14px rgba(0,0,0,0.08); border-top: 4px solid #ffc107; display: flex; flex-direction: column; gap: 8px; transition: transform 0.2s, box-shadow 0.2s; }
        .child-card.active   { border-top-color: #28a745; }
        .child-card.rejected { border-top-color: #dc3545; }
        .child-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.12); }

        .child-header { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
        .child-avatar { width: 52px; height: 52px; background: #fff8e1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; border: 2px solid #f5c842; flex-shrink: 0; }
        .child-name  { font-size: 1.05rem; font-weight: 700; color: #1a1a2e; }
        .child-level { font-size: 12px; color: #888; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-top: 3px; }
        .badge-pending  { background: #fff3cd; color: #856404; }
        .badge-active   { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }

        .info-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #555; padding: 5px 0; border-bottom: 1px solid #f5f5f5; }
        .info-row i { color: #f5c842; width: 16px; text-align: center; }

        .parent-badge { display: inline-flex; align-items: center; gap: 6px; background: #e8f4fd; color: #0369a1; border-radius: 8px; padding: 5px 10px; font-size: 12px; font-weight: 500; margin-top: 4px; flex-wrap: wrap; }

        .card-actions { display: flex; gap: 6px; margin-top: 12px; flex-wrap: wrap; }
        .btn-action { flex: 1; min-width: 60px; padding: 8px 6px; border: none; border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: opacity 0.2s, transform 0.1s; display: flex; align-items: center; justify-content: center; gap: 4px; text-decoration: none; }
        .btn-approve { background: #d4edda; color: #155724; }
        .btn-reject  { background: #fff3cd; color: #856404; }
        .btn-del     { background: #f8d7da; color: #721c24; }
        .btn-edit    { background: #e8f4fd; color: #0369a1; }
        .btn-action:hover { opacity: 0.8; transform: scale(1.02); }

        /* pending — no edit hint */
        .pending-note { font-size: 11px; color: #aaa; text-align: center; font-style: italic; margin-top: 4px; }

        .empty-note { text-align: center; padding: 40px; color: #aaa; background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 20px; }
        .empty-note i { font-size: 2.5rem; display: block; margin-bottom: 10px; color: #f5c842; }

        .modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 300; align-items: center; justify-content: center; }
        .modal-bg.show { display: flex; }
        .modal { background: #fff; border-radius: 18px; padding: 32px; width: 90%; max-width: 580px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
        .modal h3 { color: #1a1a2e; margin-bottom: 20px; font-size: 1.2rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
        .form-field { display: flex; flex-direction: column; gap: 6px; }
        .form-field label { font-size: 13px; color: #555; font-weight: 500; }
        .form-field input, .form-field select { padding: 10px 14px; border: 1.5px solid #e0e0e0; border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 14px; outline: none; transition: border 0.2s; }
        .form-field input:focus, .form-field select:focus { border-color: #f5c842; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 22px; }
        .btn-cancel { background: #f0f0f0; color: #333; padding: 10px 22px; border-radius: 10px; border: none; cursor: pointer; font-family: 'Outfit', sans-serif; font-size: 14px; }
        .btn-save   { background: #1a1a2e; color: #f5c842; padding: 10px 26px; border-radius: 10px; border: none; cursor: pointer; font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 600; }
        .btn-cancel:hover { background: #e0e0e0; }
        .btn-save:hover   { opacity: 0.85; }
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
        <li><a href="admin_timetable.php">🕐 Timetable</a></li>
        <li><a href="admin_inscriptions.php" style="color:#f5c842;">📋 Inscriptions</a></li>
            <li><a href="admin_statistics.php">📊 Statistics</a></li>
            <li><a href="admin_analysis.php">🔍 Analysis</a></li>
        <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <div class="page-header">
        <h1>👦 Children Records</h1>
        <p>Review pending dossiers — edit only after approval.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon pending">⏳</div>
            <div class="stat-info">
                <strong><?= $stats['pending'] ?? 0 ?></strong>
                <span>Pending</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon active">✅</div>
            <div class="stat-info">
                <strong><?= $stats['active'] ?? 0 ?></strong>
                <span>Approved</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rejected">❌</div>
            <div class="stat-info">
                <strong><?= $stats['rejected'] ?? 0 ?></strong>
                <span>Rejected</span>
            </div>
        </div>
    </div>

    <!-- Search -->
    <form method="GET" action="admin_inscriptions.php">
        <div class="search-box">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by child name, parent or class..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="status" class="filter-select">
                <option value="all"      <?= $filter_status==='all'      ? 'selected':'' ?>>All Status</option>
                <option value="pending"  <?= $filter_status==='pending'  ? 'selected':'' ?>>⏳ Pending</option>
                <option value="active"   <?= $filter_status==='active'   ? 'selected':'' ?>>✅ Approved</option>
                <option value="rejected" <?= $filter_status==='rejected' ? 'selected':'' ?>>❌ Rejected</option>
            </select>
            <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
            <?php if ($search!=='' || $filter_status!=='all'): ?>
            <a href="admin_inscriptions.php" class="btn-clear"><i class="fas fa-times"></i> Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php
    // ── render function ──
    function renderSection($list, $situation_map, $showApprove=false, $showReject=false, $canEdit=false) {
        if (empty($list)) {
            echo '<div class="empty-note"><i class="fas fa-inbox"></i>No records found.</div>';
            return;
        }
        echo '<div class="children-grid">';
        foreach ($list as $c) {
            $status      = $c['status'];
            $badge_cls   = 'badge-'.$status;
            $badge_label = $status==='active' ? '✅ Approved' : ($status==='rejected' ? '❌ Rejected' : '⏳ Pending');
            $id          = $c['id'];
            $sit_label   = $situation_map[$c['situation_familiale'] ?? ''] ?? ($c['situation_familiale'] ?? '—');
            ?>
            <div class="child-card <?= $status ?>">
                <div class="child-header">
                    <div class="child-avatar">👦</div>
                    <div>
                        <div class="child-name"><?= htmlspecialchars($c['child_name'].' '.($c['prenom']??'')) ?></div>
                        <div class="child-level"><?= htmlspecialchars($c['niveau']??'—') ?></div>
                        <span class="status-badge <?= $badge_cls ?>"><?= $badge_label ?></span>
                    </div>
                </div>

                <?php if (!empty($c['date_naissance'])): ?>
                <div class="info-row"><i class="fas fa-birthday-cake"></i><?= date('d/m/Y', strtotime($c['date_naissance'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($c['lieu_naissance'])): ?>
                <div class="info-row"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($c['lieu_naissance']) ?></div>
                <?php endif; ?>
                <?php if (!empty($c['situation_familiale'])): ?>
                <div class="info-row"><i class="fas fa-home"></i><?= htmlspecialchars($sit_label) ?></div>
                <?php endif; ?>

                <div class="parent-badge">
                    <i class="fas fa-user"></i>
                    <?= htmlspecialchars($c['parent_fname'].' '.$c['parent_lname']) ?>
                    &nbsp;·&nbsp;
                    <?= htmlspecialchars($c['parent_email']) ?>
                </div>

                <div class="card-actions">
                    <?php if ($canEdit): ?>
                    <!-- Edit — only for active/rejected -->
                    <button class="btn-action btn-edit" onclick="openEdit(
                        <?= $id ?>,
                        '<?= addslashes($c['child_name']) ?>',
                        '<?= addslashes($c['prenom']??'') ?>',
                        '<?= $c['date_naissance']??'' ?>',
                        '<?= addslashes($c['lieu_naissance']??'') ?>',
                        '<?= $c['situation_familiale']??'famille_complete' ?>',
                        '<?= addslashes($c['niveau']??'') ?>'
                    )"><i class="fas fa-edit"></i> Edit</button>
                    <?php else: ?>
                    <!-- pending — no edit -->
                                        <?php endif; ?>

                    <?php if ($showApprove): ?>
                    <a href="?approve=<?= $id ?>" class="btn-action btn-approve"
                       onclick="return confirm('Approve this record?')">
                        <i class="fas fa-check"></i> Approve
                    </a>
                    <a href="?reject=<?= $id ?>" class="btn-action btn-reject"
                       onclick="return confirm('Reject this record?')">
                        <i class="fas fa-times"></i> Reject
                    </a>
                    <?php endif; ?>

                    <?php if ($showReject): ?>
                    <a href="?approve=<?= $id ?>" class="btn-action btn-approve"
                       onclick="return confirm('Approve anyway?')">
                        <i class="fas fa-check"></i> Approve
                    </a>
                    <?php endif; ?>

                    <a href="?delete=<?= $id ?>" class="btn-action btn-del"
                       onclick="return confirm('Delete permanently?')">
                        <i class="fas fa-trash"></i>delete 
                    </a>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
    ?>

    <!-- ⏳ PENDING — no edit -->
    <div class="section-title">
        ⏳ Pending
        <span class="count-badge"><?= count($pending_list) ?></span>
    </div>
    <?php renderSection($pending_list, $situation_map, true, false, false); ?>

    <!-- ✅ APPROVED — edit allowed -->
    <div class="section-title">
        ✅ Approved
        <span class="count-badge" style="background:#28a745;color:#fff;"><?= count($active_list) ?></span>
    </div>
    <?php renderSection($active_list, $situation_map, false, false, true); ?>

    <!-- ❌ REJECTED — edit allowed + re-approve -->
    <div class="section-title">
        ❌ Rejected
        <span class="count-badge" style="background:#dc3545;color:#fff;"><?= count($rejected_list) ?></span>
    </div>
    <?php renderSection($rejected_list, $situation_map, false, true, true); ?>

</div>

<!-- Edit Modal -->
<div class="modal-bg" id="editModal">
    <div class="modal">
        <h3>✏️ Edit Child Record</h3>
        <form method="POST" action="admin_inscriptions.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="child_id" id="e_id">
            <div class="form-grid">
                <div class="form-field">
                    <label>Last Name <span style="color:red">*</span></label>
                    <input type="text" name="child_name" id="e_nom" required>
                </div>
                <div class="form-field">
                    <label>First Name <span style="color:red">*</span></label>
                    <input type="text" name="prenom" id="e_prenom" required>
                </div>
                <div class="form-field">
                    <label>Date of Birth</label>
                    <input type="date" name="date_naissance" id="e_date">
                </div>
                <div class="form-field">
                    <label>Place of Birth</label>
                    <input type="text" name="lieu_naissance" id="e_lieu">
                </div>
                <div class="form-field">
                    <label>Family Situation</label>
                    <select name="situation_familiale" id="e_situation">
                        <option value="famille_complete">Complete Family</option>
                        <option value="orphelin">Orphan</option>
                        <option value="parents_divorces">Divorced Parents</option>
                        <option value="autre">Other</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Level / Class</label>
                    <input type="text" name="niveau" id="e_niveau">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
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

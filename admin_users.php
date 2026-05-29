<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

$success = $error = "";

// ── حذف مستخدم ──
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== $_SESSION['user']['id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        $success = "✅ User deleted successfully.";
    } else {
        $error = "❌ You cannot delete your own account.";
    }
}

// ── إضافة مستخدم ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = trim($_POST['password']);
    $role       = $_POST['role'];

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $error = "❌ Email already exists.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?,?,?,?,?)")
            ->execute([$first_name, $last_name, $email, $hashed, $role]);
        $success = "✅ User added successfully.";
    }
}

// ── تعديل مستخدم ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id         = (int)$_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $role       = $_POST['role'];
    $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, role=? WHERE id=?")
        ->execute([$first_name, $last_name, $email, $role, $id]);
    $success = "✅ User updated successfully.";
}

// جلب المستخدمين
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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

        .top-bar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .search-box {
            flex: 1; padding: 10px 16px; border: 1.5px solid #e0e0e0;
            border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 14px; outline: none;
        }
        .search-box:focus { border-color: #f5c842; }
        .btn {
            padding: 10px 20px; border-radius: 10px; border: none;
            font-family: 'Outfit', sans-serif; font-size: 14px; cursor: pointer;
            font-weight: 600; transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.85; }
        .btn-primary { background: #0f1f3d; color: #f5c842; }
        .btn-danger  { background: #dc3545; color: #fff; font-size: 12px; padding: 6px 12px; }
        .btn-edit    { background: #f5c842; color: #0f1f3d; font-size: 12px; padding: 6px 12px; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-admin     { background: #e8d5ff; color: #5b21b6; }
        .badge-parent    { background: #d4edda; color: #155724; }
        .badge-enseignant { background: #cfe2ff; color: #084298; }

        /* Modal */
        .modal-bg {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 200;
            align-items: center; justify-content: center;
        }
        .modal-bg.show { display: flex; }
        .modal {
            background: #fff; border-radius: 16px; padding: 30px;
            width: 90%; max-width: 480px; box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .modal h3 { color: #0f1f3d; margin-bottom: 20px; }
        .form-field { margin-bottom: 14px; }
        .form-field label { display: block; font-size: 13px; color: #555; margin-bottom: 5px; font-weight: 500; }
        .form-field input, .form-field select {
            width: 100%; padding: 10px 14px; border: 1.5px solid #e0e0e0;
            border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 14px; outline: none;
        }
        .form-field input:focus, .form-field select:focus { border-color: #f5c842; }
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
            <div style="color:#fff;font-size:17px;">
                👤 <?= htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']) ?>
            </div><br>
            <p style="color:rgb(131,131,131);font-size:10px;">Administration:</p>
            <li><a href="admin.php">🏠 Dashboard</a></li>
            <li><a href="admin_users.php" style="color:#f5c842;">👥 Users</a></li>
            <li><a href="admin_timetable.php">🕐 Timetable</a></li>
            <li><a href="admin_inscriptions.php">📋 Inscriptions</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>👥 Manage Users</h1>
            <p>Add, edit or delete platform users.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <div class="top-bar">
            <form method="GET" style="display:flex;gap:10px;flex:1;">
                <input type="text" name="search" class="search-box"
                       placeholder="🔍 Search by name or email..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="admin_users.php" class="btn" style="background:#f0f0f0;color:#333;">Clear</a>
                <?php endif; ?>
            </form>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>

        <table>
            <tr>
                <th>#</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['first_name']) ?></td>
                <td><?= htmlspecialchars($u['last_name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <span class="badge badge-<?= $u['role'] ?>">
                        <?= ucfirst($u['role']) ?>
                    </span>
                </td>
                <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                <td style="display:flex;gap:6px;justify-content:center;">
                    <button class="btn btn-edit"
                        onclick="openEditModal(<?= $u['id'] ?>, '<?= addslashes($u['first_name']) ?>', '<?= addslashes($u['last_name']) ?>', '<?= addslashes($u['email']) ?>', '<?= $u['role'] ?>')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                    <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger"
                       onclick="return confirm('Delete this user?')">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Modal Add -->
    <div class="modal-bg" id="addModal">
        <div class="modal">
            <h3>➕ Add New User</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-field">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-field">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
                <div class="form-field">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-field">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-field">
                    <label>Role</label>
                    <select name="role">
                        <option value="parent">Parent</option>
                        <option value="enseignant">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModals()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal-bg" id="editModal">
        <div class="modal">
            <h3>✏️ Edit User</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_id">
                <div class="form-field">
                    <label>First Name</label>
                    <input type="text" name="first_name" id="edit_fname" required>
                </div>
                <div class="form-field">
                    <label>Last Name</label>
                    <input type="text" name="last_name" id="edit_lname" required>
                </div>
                <div class="form-field">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-field">
                    <label>Role</label>
                    <select name="role" id="edit_role">
                        <option value="parent">Parent</option>
                        <option value="enseignant">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModals()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() { document.getElementById('addModal').classList.add('show'); }
        function openEditModal(id, fname, lname, email, role) {
            document.getElementById('edit_id').value    = id;
            document.getElementById('edit_fname').value = fname;
            document.getElementById('edit_lname').value = lname;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value  = role;
            document.getElementById('editModal').classList.add('show');
        }
        function closeModals() {
            document.querySelectorAll('.modal-bg').forEach(m => m.classList.remove('show'));
        }
        document.querySelectorAll('.modal-bg').forEach(m => {
            m.addEventListener('click', e => { if (e.target === m) closeModals(); });
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
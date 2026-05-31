<?php
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db.php';
    
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $action   = $_POST['action'];
    
    // ── LOGIN ──
    if ($action === 'login') {
        $selected_role = $_POST['selected_role'] ?? 'parent';

        // جلب المستخدم بالإيميل فقط
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // تحقق من كلمة المرور — plain text أو hashed
            $passwordOk = ($password === $user['password']) || 
                          password_verify($password, $user['password']);

            $db_role = $user['role'];
            $match = ($selected_role === 'parent'  && $db_role === 'parent') ||
                     ($selected_role === 'teacher' && $db_role === 'enseignant') ||
                     ($selected_role === 'admin'   && $db_role === 'admin');

            if (!$passwordOk || !$match) {
                $error = "❌ Incorrect Informations (email/password)!";
            } else {
                $_SESSION['user'] = $user;
                if ($user['role'] === 'admin')          header("Location: admin.php");
                elseif ($user['role'] === 'parent')     header("Location: dashboard.php");
                elseif ($user['role'] === 'enseignant') header("Location: enseignant.php");
                exit;
            }
        } else {
            $error = "❌ Incorrect Informations (email/password)!";
        }
    }
    
    // ── REGISTER ──
    if ($action === 'register') {
        $first_name = trim($_POST['first_name']);
        $last_name  = trim($_POST['last_name']);
        $role_raw   = $_POST['role'];
        $role       = ($role_raw === 'teacher') ? 'enseignant' : $role_raw;
        $secret     = $_POST['secret'] ?? '';
        $child_name = trim($_POST['child_name'] ?? '');
        
        if ($role === 'admin' && $secret !== 'ECOLNA2026') {
            $error = "Code secret admin incorrect!";
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            
            if ($check->fetch()) {
                $error = "Cet email est déjà utilisé!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$first_name, $last_name, $email, $password, $role]);

                $new_id = $pdo->lastInsertId();

                if ($role === 'parent' && !empty($child_name)) {
                    try {
                        $pdo->prepare("INSERT INTO children (parent_id, child_name) VALUES (?, ?)")
                            ->execute([$new_id, $child_name]);
                    } catch (Exception $e) {}
                }

                $error = "✅ Compte créé avec succès! Vous pouvez vous connecter.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ECOLNA - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <style>
        .error-msg {
            background: rgba(220,53,69,0.15);
            border: 1px solid rgba(220,53,69,0.4);
            color: #ff6b6b;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            text-align: center;
        }
        .success-msg {
            background: rgba(40,167,69,0.15);
            border: 1px solid rgba(40,167,69,0.4);
            color: #51cf66;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            text-align: center;
        }
        .pending-msg {
            background: rgba(255,193,7,0.15);
            border: 1px solid rgba(255,193,7,0.4);
            color: #856404;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="org">
        <nav>
            <a href="index.html" class="logo"></a>
            <div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="#">Student/Courses</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li>
                    <select id="language-select"onchange="changeLanguage(this.value)">
                        <option value="">Language</option>
                        <option value="">English</option>
                        <option value="en">French</option>
                        <option value="ar">Arabic</option>
                    </select>
                 </li> 
                <li><a href="login.php"><i class="fa fa-user" style="color:#000;"></i> Log In</a></li>
                <li id="theme-toggle" style="cursor:pointer;display:flex;align-items:center;margin-left:15px;"></li>
            </ul>
        </nav>

        <main class="auth-main">
            <div class="auth-wrapper">

                <!-- LEFT SIDE -->
                <div class="auth-side">
                    <span class="hero-badge">⭐️ Welcome to ECOLNA</span>
                    <h2>Stay Close to<br><span>Your Child's Journey</span></h2>
                    <p>ECOLNA bridges the gap between home and school — giving parents real-time visibility and teachers the tools to communicate effortlessly.</p>
                    <div class="side-features">
                        <div class="side-feature">
                            <div class="side-icon"><i class="fas fa-user-friends"></i></div>
                            <div class="side-text">
                                <strong>For Parents</strong>
                                <span>Track grades, attendance &amp; teacher feedback in real time.</span>
                            </div>
                        </div>
                        <div class="side-feature">
                            <div class="side-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="side-text">
                                <strong>For Teachers</strong>
                                <span>Share reports, manage classes &amp; stay connected with families.</span>
                            </div>
                        </div>
                        <div class="side-feature">
                            <div class="side-icon"><i class="fas fa-shield-alt"></i></div>
                            <div class="side-text">
                                <strong>For Admins</strong>
                                <span>Manage users, timetables &amp; all school data from one place.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE -->
                <div class="auth-card">

                    <div class="auth-tabs">
                        <button class="auth-tab active" id="tab-login" onclick="switchTab('login')">Log In</button>
                        <button class="auth-tab" id="tab-register" onclick="switchTab('register')">Register</button>
                    </div>

                    <?php if ($error): ?>
                        <?php
                            if (strpos($error, '✅') === 0) $cls = 'success-msg';
                            elseif (strpos($error, '⏳') === 0) $cls = 'pending-msg';
                            else $cls = 'error-msg';
                        ?>
                        <div class="<?= $cls ?>">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <!-- ── LOGIN FORM ── -->
                    <div class="auth-panel active" id="panel-login">
                        <p class="panel-sub">Sign in to access your dashboard</p>

                        <div class="role-row">
                            <div class="role-pill active" id="lrole-parent" onclick="setRole('login','parent')">
                                <i class="fas fa-user-friends"></i> Parent
                            </div>
                            <div class="role-pill" id="lrole-teacher" onclick="setRole('login','teacher')">
                                <i class="fas fa-chalkboard-teacher"></i> Teacher
                            </div>
                            <div class="role-pill" id="lrole-admin" onclick="setRole('login','admin')">
                                <i class="fas fa-shield-alt"></i> Admin
                            </div>
                        </div>

                        <form method="POST" action="login.php">
                            <input type="hidden" name="action" value="login">
                            <input type="hidden" name="selected_role" id="selected_role" value="parent">
                            <div class="form-field">
                                <label>Email Address</label>
                                <input type="email" name="email" placeholder="you@example.com" required>
                            </div>
                            <div class="form-field">
                                <label>Password</label>
                                <div class="input-wrap">
                                    <input type="password" name="password" id="lpw" placeholder="••••••••" required>
                                    <span class="eye-btn" onclick="togglePw('lpw',this)"><i class="fa fa-eye"></i></span>
                                </div>
                            </div>
                            <div class="form-row">
                                <label class="check-label"><input type="checkbox" class="check"> Remember me</label>
                                <a href="#" class="forgot">Forgot password?</a>
                            </div>
                            <button type="submit" class="btn-submit">Log In &nbsp;<i class="fa fa-arrow-right"></i></button>
                        </form>
                        <p class="switch-text">No account yet? <a onclick="switchTab('register')">Register here</a></p>
                    </div>

                    <!-- ── REGISTER FORM ── -->
                    <div class="auth-panel" id="panel-register">
                        <p class="panel-sub">Create your account and join the community</p>

                        <div class="role-row">
                            <div class="role-pill active" id="rrole-parent" onclick="setRole('register','parent')">
                                <i class="fas fa-user-friends"></i> Parent
                            </div>
                            <div class="role-pill" id="rrole-teacher" onclick="setRole('register','teacher')">
                                <i class="fas fa-chalkboard-teacher"></i> Teacher
                            </div>
                            <div class="role-pill" id="rrole-admin" onclick="setRole('register','admin')">
                                <i class="fas fa-shield-alt"></i> Admin
                            </div>
                        </div>

                        <form method="POST" action="login.php">
                            <input type="hidden" name="action" value="register">
                            <input type="hidden" name="role" id="register-role" value="parent">

                            <div class="name-grid">
                                <div class="form-field">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" placeholder="First name" required>
                                </div>
                                <div class="form-field">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" placeholder="Last name" required>
                                </div>
                            </div>

                            <div class="form-field">
                                <label>Email Address</label>
                                <input type="email" name="email" placeholder="you@example.com" required>
                            </div>

                            <div class="form-field" id="child-field">
                                <label><i class="fas fa-child" style="color:#f5c842;font-size:12px;margin-right:6px;"></i>Child's Full Name</label>
                                <input type="text" name="child_name" placeholder="Your child's full name">
                            </div>

                            <div class="form-field" id="school-field" style="display:none;">
                                <label><i class="fas fa-school" style="color:#f5c842;font-size:12px;margin-right:6px;"></i>School / Establishment</label>
                                <input type="text" name="school" placeholder="Your school name">
                            </div>

                            <div class="form-field" id="admin-field" style="display:none;">
                                <label><i class="fas fa-key" style="color:#f5c842;font-size:12px;margin-right:6px;"></i>Admin Secret Code</label>
                                <input type="password" name="secret" placeholder="Enter admin secret code">
                            </div>

                            <div class="form-field">
                                <label>Password</label>
                                <div class="input-wrap">
                                    <input type="password" name="password" id="rpw" placeholder="••••••••" required>
                                    <span class="eye-btn" onclick="togglePw('rpw',this)"><i class="fa fa-eye"></i></span>
                                </div>
                            </div>

                            <button type="submit" class="btn-submit" style="margin-top:6px;">Create Account &nbsp;<i class="fa fa-arrow-right"></i></button>
                        </form>
                        <p class="switch-text">Already registered? <a onclick="switchTab('login')">Log in here</a></p>
                    </div>

                </div>
            </div>
        </main>

        <!-- FOOTER -->
        <div class="footer">
            <div class="footer-top">
                <div class="footer-brand">
                    <h2>ECOLNA</h2>
                    <p>A dedicated learning platform empowering students across Algeria to reach their full academic potential.</p>
                </div>
                <div class="footer-col">
                    <h4>Navigation</h4>
                    <ol>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="courses.php">Student / Courses</a></li>
                        <li><a href="#">Language</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ol>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 ECOLNA. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.auth-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            document.getElementById('panel-' + tab).classList.add('active');
        }

        function togglePw(id, el) {
            const input = document.getElementById(id);
            const icon = el.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function setRole(panel, role) {
            const prefix = panel === 'login' ? 'lrole' : 'rrole';
            ['parent','teacher','admin'].forEach(r => {
                const el = document.getElementById(prefix + '-' + r);
                if (el) el.classList.toggle('active', r === role);
            });
            if (panel === 'login') {
                document.getElementById('selected_role').value = role;
            }
            if (panel === 'register') {
                document.getElementById('child-field').style.display  = role === 'parent'  ? 'block' : 'none';
                document.getElementById('school-field').style.display = role === 'teacher' ? 'block' : 'none';
                document.getElementById('admin-field').style.display  = role === 'admin'   ? 'block' : 'none';
                document.getElementById('register-role').value = role;
            }
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

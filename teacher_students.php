<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'enseignant') {
    header("Location: login.php"); exit;
}

// جلب كل الأولاد المفعّلين مصنفين حسب القسم
$stmt = $pdo->query("
    SELECT 
        c.id,
        c.child_name,
        c.classe,
        c.date_naissance,
        c.niveau,
        u.first_name AS parent_fname,
        u.last_name  AS parent_lname,
        u.email      AS parent_email
    FROM children c
    JOIN users u ON u.id = c.parent_id
    WHERE c.status = 'active'
    ORDER BY c.classe, c.child_name
");
$all_children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تصنيف حسب القسم
$classes = [];
foreach ($all_children as $child) {
    $classe = $child['classe'] ?: 'Sans classe';
    $classes[$classe][] = $child;
}

$enseignant_name = htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']);
$total_eleves = count($all_children);
$total_classes = count($classes);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Élèves — Enseignant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="parent.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f4f6fb;
            color: #1a1a2e;
        }

        /* ── Stats ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .stat-icon.blue   { background: #dbeafe; }
        .stat-icon.yellow { background: #fef9c3; }
        .stat-icon.green  { background: #dcfce7; }
        .stat-info strong { font-size: 1.6rem; color: #1a1a2e; display: block; line-height: 1.1; }
        .stat-info span   { font-size: 12px; color: #888; }

        /* ── Search & Filter ── */
        .toolbar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
            align-items: center;
        }
        .search-box {
            flex: 1;
            min-width: 220px;
            display: flex;
            align-items: center;
            background: #fff;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            padding: 10px 16px;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .search-box i { color: #aaa; }
        .search-box input {
            border: none; outline: none;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            background: transparent;
            width: 100%;
            color: #1a1a2e;
        }
        .filter-select {
            padding: 11px 16px;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            background: #fff;
            color: #1a1a2e;
            outline: none;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .filter-select:focus { border-color: #f5c842; }

        /* ── Class Section ── */
        .classe-section { margin-bottom: 32px; }

        .classe-header {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #fff;
            background: linear-gradient(135deg, #1a1a2e, #2d2d5e);

            padding: 14px 22px;
            border-radius: 14px;
            margin-bottom: 16px;
            box-shadow: 0 4px 14px rgba(26,26,46,0.18);
        }
        .classe-header .classe-icon {
            width: 42px; height: 42px;
            background: rgba(245,200,66,0.18);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
        }
        .classe-header h2 {
            font-size: 1.1rem;
            font-weight: 600;
            flex: 1;
        }
        .count-badge {
            background: #f5c842;
            color: #1a1a2e;
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 13px;
            font-weight: 700;
        }

        /* ── Student Cards ── */
        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .child-card {
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.07);
            border-top: 4px solid #f5c842;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: default;
        }
        .child-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .child-header-card {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }
        .child-avatar {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #fef9c3, #fde68a);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            border: 2px solid #f5c842;
            flex-shrink: 0;
        }
        .child-name {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.2;
        }
        .child-classe-tag {
            display: inline-block;
            background: #f0f4ff;
            color: #3b5bdb;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 3px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #555;
            padding: 5px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .info-row:last-of-type { border-bottom: none; }
        .info-row i { color: #f5c842; width: 16px; text-align: center; flex-shrink: 0; }

        .parent-badge {
            display: flex;
            align-items: center;
            gap: 7px;
            background: #e8f4fd;
            color: #0369a1;
            border-radius: 10px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 6px;
        }
        .parent-badge i { font-size: 12px; }

        /* ── Empty ── */
        .empty-msg {
            text-align: center;
            padding: 70px 40px;
            color: #999;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .empty-msg i {
            font-size: 4rem;
            margin-bottom: 18px;
            display: block;
            color: #f5c842;
        }
        .empty-msg h3 { color: #1a1a2e; margin-bottom: 8px; font-size: 1.2rem; }
        .empty-msg p  { font-size: 14px; }

        /* ── Page Header ── */
        .page-header { margin-bottom: 26px; }
        .page-header h1 { font-size: 2rem; color: #1a1a2e; }
        .page-header p  { color: #666; margin-top: 5px; font-size: 14px; }

        /* ── No results (search) ── */
        .no-results {
            display: none;
            text-align: center;
            padding: 40px;
            color: #999;
            background: #fff;
            border-radius: 14px;
            font-size: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }
        .no-results i { font-size: 2.5rem; display: block; margin-bottom: 12px; }
    </style>
</head>
<body>

<div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>

<nav>
    <a href="HomePfe.html" class="logo"></a>
    <p style="color:rgb(131,131,131);font-size:10px;">Platforme Scolaire</p>
    <ul>
        <div style="color:#fff;font-size:16px;font-weight:600;">
            👨‍🏫 <?= $enseignant_name ?>
        </div><br>
        <p style="color:rgb(131,131,131);font-size:10px;">Espace Enseignant:</p>
            <li><a href="enseignant.php">🏠 Dashboard</a></li>
            <li><a href="teacher_attendance.php">📅 Mark Attendance</a></li>
            <li><a href="teacher_students.php" style="color:#f5c842;">👥 Student List</a></li>
            <li><a href="teacher_grades.php">⭐ Manage Grades</a></li>
            <li><a href="teacher_disciplinary.php">⚠️ Disciplinary</a></li>
            <li><a href="login.php" style="color:#ff6b6b;">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <!-- En-tête -->
    <div class="page-header">
        <h1>👦 Mes Élèves</h1>
        <p>Liste des élèves validés par l'administration, classés par section.</p>
    </div>

    <!-- Statistiques -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue">👦</div>
            <div class="stat-info">
                <strong><?= $total_eleves ?></strong>
                <span>Total élèves</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">🏫</div>
            <div class="stat-info">
                <strong><?= $total_classes ?></strong>
                <span>Classes</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <strong><?= $total_eleves ?></strong>
                <span>Validés</span>
            </div>
        </div>
    </div>

    <!-- Barre de recherche et filtre -->
    <div class="toolbar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input
                type="text"
                id="searchInput"
                placeholder="Rechercher un élève..."
                onkeyup="filterEleves()"
            >
        </div>
        <select class="filter-select" id="classeFilter" onchange="filterEleves()">
            <option value="">Toutes les classes</option>
            <?php foreach (array_keys($classes) as $cl): ?>
                <option value="<?= htmlspecialchars($cl) ?>">
                    <?= htmlspecialchars($cl) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Liste des élèves par classe -->
    <?php if (empty($classes)): ?>
        <div class="empty-msg">
            <i class="fas fa-user-slash"></i>
            <h3>Aucun élève validé pour le moment</h3>
            <p>Les élèves apparaîtront ici dès que l'administration validera leurs dossiers.</p>
        </div>
    <?php else: ?>

        <div id="no-results" class="no-results">
            <i class="fas fa-search"></i>
            Aucun élève trouvé pour cette recherche.
        </div>

        <?php foreach ($classes as $classe_nom => $eleves): ?>
        <div class="classe-section" data-classe="<?= htmlspecialchars($classe_nom) ?>">

            <!-- En-tête de la classe -->
            <div class="classe-header">
                <div class="classe-icon">🏫</div>
                <h2>Classe : <?= htmlspecialchars($classe_nom) ?></h2>
                <span class="count-badge"><?= count($eleves) ?> élève(s)</span>
            </div>

            <!-- Grille des élèves -->
            <div class="children-grid">
                <?php foreach ($eleves as $child): ?>
                <div class="child-card"
                     data-name="<?= strtolower(htmlspecialchars($child['child_name'])) ?>"
                     data-classe="<?= htmlspecialchars($classe_nom) ?>">

                    <div class="child-header-card">
                        <div class="child-avatar">👦</div>
                        <div>
                            <div class="child-name">
                                <?= htmlspecialchars($child['child_name']) ?>
                            </div>
                            <span class="child-classe-tag">
                                <?= htmlspecialchars($child['classe']) ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($child['date_naissance'])): ?>
                    <div class="info-row">
                        <i class="fas fa-birthday-cake"></i>
                        <?= date('d/m/Y', strtotime($child['date_naissance'])) ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($child['niveau'])): ?>
                    <div class="info-row">
                        <i class="fas fa-graduation-cap"></i>
                        <?= htmlspecialchars($child['niveau']) ?>
                    </div>
                    <?php endif; ?>

                    <div class="parent-badge">
                        <i class="fas fa-user-tie"></i>
                        Parent : <?= htmlspecialchars($child['parent_fname'] . ' ' . $child['parent_lname']) ?>
                    </div>

                    <div class="parent-badge" style="background:#f0fdf4;color:#15803d;margin-top:4px;">
                        <i class="fas fa-envelope"></i>
                        <?= htmlspecialchars($child['parent_email']) ?>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div><!-- /container -->

<script>
    // ── Hamburger menu ──
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

    // ── Recherche + Filtre par classe ──
    function filterEleves() {
        const search      = document.getElementById('searchInput').value.toLowerCase().trim();
        const classeFilter = document.getElementById('classeFilter').value;

        const sections = document.querySelectorAll('.classe-section');
        let totalVisible = 0;

        sections.forEach(section => {
            const sectionClasse = section.getAttribute('data-classe');

            // filtre par classe
            if (classeFilter && sectionClasse !== classeFilter) {
                section.style.display = 'none';
                return;
            }

            const cards = section.querySelectorAll('.child-card');
            let visibleInSection = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const match = name.includes(search);
                card.style.display = match ? '' : 'none';
                if (match) visibleInSection++;
            });

            section.style.display = visibleInSection > 0 ? '' : 'none';
            totalVisible += visibleInSection;
        });

        // message aucun résultat
        document.getElementById('no-results').style.display =
            totalVisible === 0 && (search || classeFilter) ? 'block' : 'none';
    }
</script>

</body>
</html>
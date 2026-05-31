<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses – ECOLNA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="HomePfe.css">
    <style>
        /* ── PAGE HERO ── */
        .courses-hero {
            background: linear-gradient(135deg, rgba(15,31,61,0.85), rgba(0,150,224,0.5)),
                        url('heros.png') center/cover no-repeat;
            padding: 100px 60px 60px;
            text-align: center;
            color: #fff;
        }
        .courses-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            margin-bottom: 14px;
        }
        .courses-hero p {
            font-size: 17px;
            color: rgba(255,255,255,0.85);
            max-width: 560px;
            margin: 0 auto 30px;
        }

        /* ── SEARCH & FILTER ── */
        .filter-bar {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
            padding: 30px 60px;
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(245,200,66,0.2);
        }
        .search-input {
            padding: 10px 20px;
            border: 1.5px solid #e0e0e0;
            border-radius: 25px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            outline: none;
            width: 280px;
            transition: border 0.2s;
        }
        .search-input:focus { border-color: #f5c842; }

        .filter-btn {
            padding: 10px 22px;
            border-radius: 25px;
            border: 1.5px solid #e0e0e0;
            background: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            color: #333;
        }
        .filter-btn:hover,
        .filter-btn.active {
            background: #f5c842;
            border-color: #f5c842;
            color: #0f1f3d;
            font-weight: 600;
        }

        /* ── COURSES GRID ── */
        .courses-section {
            padding: 60px 60px;
        }
        .courses-section .section-header {
            margin-bottom: 40px;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .course-card {
            background: rgba(124,207,252,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
            backdrop-filter: blur(6px);
        }
        .course-card:hover {
            transform: translateY(-6px);
            border-color: #f5c842;
            box-shadow: 0 12px 32px rgba(245,200,66,0.2);
        }

        .course-thumb {
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
        }
        .thumb-math   { background: linear-gradient(135deg, #667eea, #764ba2); }
        .thumb-lang   { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .thumb-hist   { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .thumb-geo    { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        .thumb-islamic { background: linear-gradient(135deg, #11998e, #38ef7d); }
        .thumb-sport  { background: linear-gradient(135deg, #f5c842, #fc4a1a); }

        .course-body { padding: 20px; }
        .course-tag {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(245,200,66,0.15);
            color: #c49a00;
            border: 1px solid rgba(245,200,66,0.3);
            margin-bottom: 10px;
        }
        .course-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f1f3d;
            margin-bottom: 8px;
        }
        .course-desc {
            font-size: 13px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid rgba(0,0,0,0.06);
            padding-top: 12px;
        }
        .course-meta span { display: flex; align-items: center; gap: 5px; }
        .course-meta i { color: #f5c842; }

        .btn-enroll {
            display: block;
            width: 100%;
            padding: 10px;
            background: #0f1f3d;
            color: #f5c842;
            border: none;
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 14px;
            transition: opacity 0.2s;
        }
        .btn-enroll:hover { opacity: 0.85; }

        /* ── NO RESULTS ── */
        .no-results {
            text-align: center;
            padding: 60px;
            color: #888;
            display: none;
        }
        .no-results i { font-size: 3rem; display: block; margin-bottom: 14px; color: #f5c842; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .courses-hero { padding: 80px 24px 40px; }
            .courses-hero h1 { font-size: 32px; }
            .filter-bar { padding: 20px 24px; }
            .courses-section { padding: 40px 24px; }
            .search-input { width: 100%; }
        }
    </style>
</head>
<body>

    <div class="org" style="height:auto;background-image:none;background:transparent;">
        <nav>
            <a href="index.html" class="logo"></a>
            <div class="hamburger" id="hamburger"><i class="fa fa-bars"></i></div>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="courses.html" style="color:#f5c842;">Student/Courses</a></li>
                <li><a href="#">Language</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="login.php"><i class="fa fa-user"></i> Log In</a></li>
            </ul>
        </nav>
    </div>

    <div class="courses-hero">
        <span class="hero-badge">📚 Our Courses</span>
        <h1>Explore <span>All Courses</span></h1>
        <p>Discover structured courses across all subjects — taught by expert teachers for every level.</p>
    </div>

    <div class="filter-bar">
        <input type="text" class="search-input" id="searchInput"
               placeholder="🔍 Search courses..." oninput="filterCourses()">
        <button class="filter-btn active" onclick="filterByTag('all', this)">All</button>
        <button class="filter-btn" onclick="filterByTag('Mathematics', this)">Mathematics</button>
        <button class="filter-btn" onclick="filterByTag('Arabic', this)">Arabic</button>
        <button class="filter-btn" onclick="filterByTag('French', this)">French</button>
        <button class="filter-btn" onclick="filterByTag('English', this)">English</button>
        <button class="filter-btn" onclick="filterByTag('History', this)">History</button>
        <button class="filter-btn" onclick="filterByTag('Geography', this)">Geography</button>
        <button class="filter-btn" onclick="filterByTag('Islamic Studies', this)">Islamic Studies</button>
        <button class="filter-btn" onclick="filterByTag('Sport', this)">Sport</button>
    </div>

    <section class="courses-section">
        <div class="section-header">
            <h2>Available <span>Courses</span></h2>
            <p>Choose a course and start your learning journey today</p>
        </div>

        <div class="courses-grid" id="coursesGrid">

            <div class="course-card" data-tag="Mathematics" data-title="mathematics algebra">
                <div class="course-thumb thumb-math">📐</div>
                <div class="course-body">
                    <span class="course-tag">Mathematics</span>
                    <div class="course-title">Algebra & Functions</div>
                    <div class="course-desc">Master algebraic equations, functions, and graphs from basic to advanced level.</div>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 24 hours</span>
                        <span><i class="fas fa-users"></i> 320 students</span>
                        <span><i class="fas fa-star"></i> 4.8</span>
                    </div>
                    <button class="btn-enroll" onclick="location.href='login.php'">Enroll Now →</button>
                </div>
            </div>

            <div class="course-card" data-tag="Arabic" data-title="arabic language">
                <div class="course-thumb thumb-lang">🌙</div>
                <div class="course-body">
                    <span class="course-tag">Arabic</span>
                    <div class="course-title">Arabic Language</div>
                    <div class="course-desc">Master Arabic grammar, literature, and expressive writing.</div>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 25 hours</span>
                        <span><i class="fas fa-users"></i> 420 students</span>
                        <span><i class="fas fa-star"></i> 4.8</span>
                    </div>
                    <button class="btn-enroll" onclick="location.href='login.php'">Enroll Now →</button>
                </div>
            </div>

            <div class="course-card" data-tag="French" data-title="french language">
                <div class="course-thumb thumb-lang">🇫🇷</div>
                <div class="course-body">
                    <span class="course-tag">French</span>
                    <div class="course-title">French Language</div>
                    <div class="course-desc">Improve your French grammar, vocabulary, and expression skills.</div>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 20 hours</span>
                        <span><i class="fas fa-users"></i> 350 students</span>
                        <span><i class="fas fa-star"></i> 4.9</span>
                    </div>
                    <button class="btn-enroll" onclick="location.href='login.php'">Enroll Now →</button>
                </div>
            </div>

            <div class="course-card" data-tag="English" data-title="english language">
                <div class="course-thumb thumb-lang">🇬🇧</div>
                <div class="course-body">
                    <span class="course-tag">English</span>
                    <div class="course-title">English Language</div>
                    <div class="course-desc">Build your English skills from grammar basics to fluent communication.</div>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 28 hours</span>
                        <span><i class="fas fa-users"></i> 380 students</span>
                        <span><i class="fas fa-star"></i> 4.9</span>
                    </div>
                    <button class="btn-enroll" onclick="location.href='login.php'">Enroll Now →</button>
                </div>
            </div>

            <div class="course-card" data-tag="History" data-title="history algeria">
                <div class="course-thumb thumb-hist">🏛️</div>
                <div class="course-body">
                    <span class="course-tag">History</span>
                    <div class="course-title">History of Algeria</div>
                    <div class="course-desc">Explore Algerian history from ancient civilizations to modern independence.</div>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 16 hours</span>
                        <span><i class="fas fa-users"></i> 240 students</span>
                        <span><i class="fas fa-star"></i> 4.7</span>
                    </div>
                    <button class="btn-enroll" onclick="location.href='login.php'">Enroll Now →</button>
                </div>
            </div>

            <div class="course-card" data-tag="Geography" data-title="geography">
                <div class="course-thumb thumb-geo">🌍</div>
                <div class="course-body">
                    <span class="course-tag">Geography</span>
                    <div class="course-title">Geography & Environment</div>
                    <div class="course-desc">Study world geography, climate, natural resources, and maps.</div>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 14 hours</span>
                        <span><i class="fas fa-users"></i> 180 students</span>
                        <span><i class="fas fa-star"></i> 4.5</span>
                    </div>
                    <button class="btn-enroll" onclick="location.href='login.php'">Enroll Now →</button>
                </div>
            </div>

            <div class="course-card" data-tag="Islamic Studies" data-title="islamic studies education">
                <div class="course-thumb thumb-islamic">🕌</div>
                <div class="course-body">
                    <span class="course-tag">Islamic Studies</span>
                    <div class="course-title">Islamic Education</div>
                    <div class="course-desc">Learn Islamic values, Quranic studies, and prophetic biography (Seerah).</div>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 18 hours</span>
                        <span><i class="fas fa-users"></i> 310 students</span>
                        <span><i class="fas fa-star"></i> 4.9</span>
                    </div>
                    <button class="btn-enroll" onclick="location.href='login.php'">Enroll Now →</button>
                </div>
            </div>

            <div class="course-card" data-tag="Sport" data-title="sport physical education">
                <div class="course-thumb thumb-sport">⚽</div>
                <div class="course-body">
                    <span class="course-tag">Sport</span>
                    <div class="course-title">Physical Education</div>
                    <div class="course-desc">Improve physical fitness, team sports skills, and healthy lifestyle habits.</div>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 12 hours</span>
                        <span><i class="fas fa-users"></i> 400 students</span>
                        <span><i class="fas fa-star"></i> 4.8</span>
                    </div>
                    <button class="btn-enroll" onclick="location.href='login.php'">Enroll Now →</button>
                </div>
            </div>

        </div>

        <div class="no-results" id="noResults">
            <i class="fas fa-search"></i>
            No courses found matching your search.
        </div>
    </section>

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
                    <li><a href="courses.html">Student / Courses</a></li>
                    <li><a href="#">Language</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ol>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 ECOLNA. All rights reserved.</p>
        </div>
    </div>

    <div class="letters-container" id="lettersContainer"></div>

    <script>
        // ── FILTER BY TAG ──
        let currentTag = 'all';

        function filterByTag(tag, btn) {
            currentTag = tag;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            filterCourses();
        }

        function filterCourses() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            let visible = 0;
            document.querySelectorAll('.course-card').forEach(card => {
                const matchTag   = currentTag === 'all' || card.dataset.tag === currentTag;
                const matchSearch = card.dataset.title.includes(q) ||
                                    card.querySelector('.course-title').textContent.toLowerCase().includes(q) ||
                                    card.querySelector('.course-tag').textContent.toLowerCase().includes(q);
                if (matchTag && matchSearch) {
                    card.style.display = 'block';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });
            document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
        }

        // ── LETTERS ──
        const letters = ['أ','ب','ت','ج','ح','د','ر','س','ش','ع','ف','ق','ك','ل','م','ن','و','ي','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        const colors  = ['#f5c842','#ef5350','#66bb6a','#7986cb','#29b6f6','#ff7043'];
        const container = document.getElementById('lettersContainer');

        function createLetter() {
            if (!container) return;
            const el = document.createElement('div');
            el.className = 'letter';
            el.textContent = letters[Math.floor(Math.random() * letters.length)];
            const size = Math.random() * 22 + 14;
            const left = Math.random() * 100;
            const duration = Math.random() * 12 + 10;
            const delay = Math.random() * 6;
            el.style.cssText = `left:${left}%;font-size:${size}px;color:${colors[Math.floor(Math.random()*colors.length)]};animation-duration:${duration}s;animation-delay:${delay}s;text-shadow:0 0 8px currentColor;`;
            container.appendChild(el);
            setTimeout(() => el.remove(), (duration + delay) * 1000);
        }
        for (let i = 0; i < 15; i++) createLetter();
        setInterval(createLetter, 900);

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
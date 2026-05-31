<?php
$message_sent = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = trim($_POST["name"]);
    $email   = trim($_POST["email"]);
    $message = trim($_POST["message"]);

    $to      = "ayoubiayoub018@gmail.com";
    $subject = "ECOLNA Contact Message";
    $body    = "Name: $name\nEmail: $email\n\nMessage:\n$message";
    $headers = "From: $email";

    if (mail($to, $subject, $body, $headers)) {
        $message_sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - ECOLNA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f1f3d 0%, #1a3a6b 100%);
        }

        
        .contact-main {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 89px);
            padding: 40px 20px;
        }

        .contact-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
        }

        .contact-left {
            background: linear-gradient(135deg, #0f1f3d, #1a3a6b);
            padding: 50px 40px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .contact-left h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #f5c842;
        }

        .contact-left p {
            font-size: 14px;
            color: rgba(255,255,255,0.75);
            margin-bottom: 36px;
            line-height: 1.7;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .contact-info-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .contact-info-item .icon {
            width: 42px; height: 42px;
            background: rgba(245,200,66,0.15);
            border: 1px solid rgba(245,200,66,0.3);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #f5c842;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .contact-info-item div strong {
            display: block;
            font-size: 13px;
            color: #f5c842;
        }

        .contact-info-item div span {
            font-size: 13px;
            color: rgba(255,255,255,0.7);
        }

        .contact-right {
            padding: 50px 40px;
            background: #fff;
        }

        .contact-right h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f1f3d;
            margin-bottom: 6px;
        }

        .contact-right p {
            font-size: 13px;
            color: #888;
            margin-bottom: 28px;
        }

        .success-msg {
            background: rgba(40,167,69,0.12);
            border: 1px solid rgba(40,167,69,0.3);
            color: #155724;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        .form-field label {
            font-size: 13px;
            color: #555;
            font-weight: 500;
        }

        .form-field input,
        .form-field textarea {
            padding: 12px 16px;
    border: 1.5px solid #858080 !important;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border 0.2s;
            color: black !important;
            width: 100%;
        }
        .form-field input::placeholder{
            color:#1a1a2e !important;
        }
        .form-field input:focus,.form-field textarea:focus {
            border-color: #f5c842 !important;
        }


        .form-field textarea { resize: vertical; min-height: 120px; }
        .form-field placeholder{
            color:blue;
        }
        .btn-send {
            width: 100%;
            padding: 13px;
            background: #0f1f3d;
            color: #f5c842;
            border: none;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            margin-top: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-send:hover { opacity: 0.88; transform: translateY(-1px); }


        @media (max-width: 700px) {
            .contact-wrapper { grid-template-columns: 1fr; }
            .contact-left { padding: 36px 28px; }
            .contact-right { padding: 36px 28px; }
            nav { padding: 14px 20px; }
            nav ul { gap: 16px; }
            nav ul li a { font-size: 13px; }
        }
    </style>
</head>
<body>

<nav>
    <a href="index.html" class="logo"></a>
    <div class="hamburger" id="hamburger">
         <i class="fa fa-bars" ></i> </div>
    <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="courses.php">Student / Courses</a></li>
        <li><a href="contact.php">Contact</a></li>
            <li>
                    <select id="language-select"onchange="changeLanguage(this.value)">
                        <option value="">Language</option>
                        <option value="">English</option>
                        <option value="en">French</option>
                        <option value="ar">Arabic</option>
                    </select>
             </li> 
        <li><a href="login.php"><i class="fa fa-user" style="color:#000;"></i> Login</a></li>
    </ul>
</nav>

<div class="contact-main">
    <div class="contact-wrapper">


        <div class="contact-left">
            <h2>Get in Touch</h2>
            <p>Have a question or need help? We're here for you. Fill out the form and we'll get back to you as soon as possible.</p>

            <div class="contact-info">
                <div class="contact-info-item">
                    <div class="icon"><i class="fas fa-envelope"></i></div>
                    <div>
                        <strong>Email</strong>
                        <span>ayoubiayoub018@gmail.com</span>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <strong>Location</strong>
                        <span>Algeria</span>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <strong>Working Hours</strong>
                        <span>Sun – Thu, 8:00 AM – 4:00 PM</span>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fas fa-graduation-cap"></i></div>
                    <div>
                        <strong>Platform</strong>
                        <span>ECOLNA — School Management</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="contact-right">
            <h3>Send us a Message</h3>
            <p>We'll respond within 24 hours.</p>

            <?php if ($message_sent): ?>
                <div class="success-msg">
                    ✅ Your message has been sent successfully!
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-field">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Your full name" required>
                </div>
                <div class="form-field">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-field">
                    <label>Message</label>
                    <textarea name="message" placeholder="Write your message here..." required></textarea>
                </div>
                <button type="submit" class="btn-send">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>

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

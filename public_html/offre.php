<?php
require_once 'app/db.php';

$jobId = $_GET['id'] ?? null;
$job = null;

if ($jobId) {
    try {
        $stmt = $db->prepare("SELECT * FROM jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Error handling
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $job ? htmlspecialchars($job['title']) . ' - CiaoCV' : 'Offre non trouvée'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --text-white: #FFFFFF;
            --text-gray: #94A3B8;
            --bg-dark: #0a0f1a;
            --glass: rgba(30, 41, 59, 0.4);
            --glass-border: rgba(255, 255, 255, 0.08);
            --radius-lg: 28px;
            --bg-gradient: radial-gradient(ellipse 80% 50% at 70% 20%, rgba(99, 102, 241, 0.12) 0%, transparent 50%),
                radial-gradient(ellipse 60% 40% at 20% 80%, rgba(167, 139, 250, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 0%, rgba(15, 23, 42, 0.95) 0%, #0a0f1a 60%);
        }

        [data-theme="light"] {
            --primary: #4f46e5;
            --text-white: #0f172a;
            --text-gray: #475569;
            --bg-dark: #f8fafc;
            --glass: rgba(255, 255, 255, 0.6);
            --glass-border: rgba(0, 0, 0, 0.1);
            --bg-gradient: radial-gradient(circle at 50% 0%, #e0f2fe 0%, #f8fafc 100%);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--bg-dark);
            background-image: var(--bg-gradient);
            color: var(--text-white);
            margin: 0;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Reusing Navbar Styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-white);
            font-weight: 500;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #1e3a5f, #2563eb);
            color: white;
            padding: 0.65rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
        }

        /* Job Detail Specifics */
        .job-header-section {
            padding: 4rem 5% 2rem;
            text-align: center;
        }

        .job-container {
            max-width: 800px;
            margin: 0 auto 4rem;
            padding: 0 5%;
        }

        .job-card-detail {
            background: var(--glass);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .job-title-large {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .job-meta {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin-bottom: 2rem;
            color: var(--text-gray);
            font-size: 0.95rem;
            flex-wrap: wrap;
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .job-description {
            font-size: 1rem;
            color: var(--text-white);
            margin-bottom: 2.5rem;
            white-space: pre-wrap; /* Preserve line breaks */
        }

        .btn-apply-large {
            display: inline-block;
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 1rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: transform 0.2s, background 0.2s;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }

        .btn-apply-large:hover {
            transform: translateY(-2px);
            background: var(--primary-hover);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-gray);
            text-decoration: none;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        .back-link:hover { color: var(--primary); }

        /* Footer */
        footer {
            margin-top: auto;
            padding: 3rem 5%;
            border-top: 1px solid var(--glass-border);
            background: rgba(10, 15, 26, 0.5);
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .nav-links { display: none; }
        }
    </style>
</head>

<body>

    <header class="navbar">
        <a href="/" class="logo">ciao<span style="color:var(--text-white)">cv</span></a>
        <nav class="nav-links">
            <a href="/">Accueil</a>
            <a href="/emplois">Offres d'emploi</a>
        </nav>
        <div style="display:flex; gap:1rem; align-items:center;">
            <a href="https://app.ciaocv.com/connexion" class="btn-login">Connexion</a>
        </div>
    </header>

    <div class="job-container" style="margin-top: 2rem;">
        <a href="/emplois" class="back-link">← Retour aux offres</a>

        <?php if ($job): ?>
            <div class="job-card-detail">
                <div style="text-align:center; margin-bottom:2rem;">
                    <h1 class="job-title-large"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <div style="font-size: 1.2rem; font-weight: 600; color: var(--primary); margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($job['company']); ?>
                    </div>
                    
                    <div class="job-meta">
                        <span>📍 <?php echo htmlspecialchars($job['location']); ?></span>
                        <span>💼 <?php echo htmlspecialchars($job['type']); ?></span>
                        <?php if(!empty($job['salary'])): ?>
                            <span>💰 <?php echo htmlspecialchars($job['salary']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <hr style="border:0; border-top:1px solid var(--glass-border); margin: 0 0 2rem;">

                <div class="job-description">
                    <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                </div>

                <div style="text-align:center;">
                    <a href="#" class="btn-apply-large">Postuler maintenant (Vidéo)</a>
                    <p style="margin-top:1rem; font-size:0.85rem; color:var(--text-gray);">
                        Aucun CV requis. Répondez simplement à 3 questions en vidéo.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="job-card-detail" style="text-align:center; padding: 4rem;">
                <h2>Offre introuvable</h2>
                <p>Désolé, cette offre d'emploi n'existe plus ou a été retirée.</p>
                <a href="/emplois" class="btn-apply-large" style="margin-top:2rem;">Voir les autres offres</a>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>© 2026 CiaoCV - Fièrement humain ❤️</p>
    </footer>

    <script>
        // Check theme
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    </script>

</body>
</html>
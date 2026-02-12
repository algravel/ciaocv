<?php
/**
 * Page tarifs – données depuis gestion_plans (DB)
 */
$projectRoot = dirname(__DIR__);
$envFile = $projectRoot . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
    }
}

$plans = [];
$lang = ($_COOKIE['language'] ?? 'fr') === 'en' ? 'en' : 'fr';
try {
    require_once $projectRoot . '/gestion/includes/Database.php';
    $pdo = Database::get();
    $stmt = $pdo->query('SELECT id, name_fr, name_en, video_limit, price_monthly, price_yearly, features_json, is_popular FROM gestion_plans WHERE COALESCE(active, 1) = 1 ORDER BY price_monthly ASC');
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $featuresJson = $r['features_json'] ?? null;
        $features = $featuresJson ? (json_decode($featuresJson, true) ?: []) : [];
        $plans[] = [
            'id' => (int) $r['id'],
            'name' => $lang === 'en' ? ($r['name_en'] ?? $r['name_fr']) : ($r['name_fr'] ?? $r['name_en']),
            'video_limit' => (int) $r['video_limit'],
            'price_monthly' => (float) $r['price_monthly'],
            'price_yearly' => (float) $r['price_yearly'],
            'features' => $features,
            'is_popular' => !empty($r['is_popular']),
        ];
    }
} catch (Throwable $e) {
    $plans = [];
}

function e($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

// Rendu du prix
$formatPrice = function($price) { return number_format($price, 0, ',', ' '); };
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifs et forfaits - CiaoCV</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/design-system.css?v=1770868074">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <style>
        .pricing-hero { text-align: center; padding: 6rem 5% 3rem; max-width: 900px; margin: 0 auto; }
        .pricing-hero h1 { font-size: 3rem; font-weight: 800; margin-bottom: 1.5rem; color: var(--text-white); letter-spacing: -0.02em; }
        .pricing-hero p { font-size: 1.25rem; color: var(--text-gray); line-height: 1.8; }
        .pricing-section { width: 100%; padding: 0 10%; box-sizing: border-box; display: flex; }
        .pricing-grid { flex: 1 1 auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem; max-width: 1200px; width: 100%; margin: 0 auto 6rem; padding: 0; box-sizing: border-box; }
        .pricing-card { background: white; border: 1px solid var(--border-dark); border-radius: var(--radius-lg); padding: 2rem 1.5rem; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s; position: relative; }
        .pricing-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -5px rgba(0, 0, 0, 0.1); }
        .pricing-card.featured { border: 2px solid var(--primary); box-shadow: 0 10px 30px rgba(37, 99, 235, 0.1); transform: scale(1.05); z-index: 2; }
        .pricing-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--primary); color: white; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 20px; text-transform: uppercase; white-space: nowrap; }
        .price-header { text-align: center; margin-bottom: 2rem; }
        .price-header h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-white); }
        .price-amount { font-size: 2.25rem; font-weight: 800; color: var(--text-white); line-height: 1.2; }
        .price-period { font-size: 0.9rem; color: var(--text-gray); font-weight: 400; }
        .price-desc { color: var(--text-gray); font-size: 0.85rem; margin-top: 0.5rem; }
        .features-list { flex: 1; list-style: none; margin: 1.5rem 0; padding: 0; }
        .features-list li { position: relative; padding-left: 1.5rem; margin-bottom: 0.8rem; color: var(--text-gray); font-size: 0.9rem; }
        .features-list li::before { content: "✓"; position: absolute; left: 0; color: var(--primary); font-weight: bold; }
        .btn-price { display: block; text-align: center; padding: 0.85rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: all 0.2s; }
        .btn-price.primary { background: var(--primary); color: white; }
        .btn-price.primary:hover { opacity: 0.9; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .btn-price.outline { background: transparent; border: 1px solid var(--border-dark); color: var(--text-white); }
        .btn-price.outline:hover { border-color: var(--text-white); background: var(--bg-alt); }
        .btn-price { cursor: pointer; font-family: inherit; }
        .comparison-section { padding: 0 5% 6rem; max-width: 1200px; margin: 0 auto; overflow-x: auto; }
        .feature-table { width: 100%; border-collapse: collapse; text-align: left; min-width: 800px; }
        .feature-table th, .feature-table td { padding: 1rem; border-bottom: 1px solid var(--border-dark); }
        .feature-table th { font-weight: 700; color: var(--text-white); width: 18%; text-align: center; }
        .feature-table th:first-child { width: 28%; color: var(--text-gray); text-align: left; }
        .feature-table td { color: var(--text-gray); font-size: 0.95rem; text-align: center; }
        .feature-table td:first-child { text-align: left; }
        .check-icon { color: var(--primary); font-weight: bold; }
        .x-icon { color: #cbd5e1; }
        @media (max-width: 1100px) { .pricing-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .pricing-grid { grid-template-columns: 1fr; } }
        /* Modal */
        .plan-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1000; align-items: center; justify-content: center; padding: 1rem; }
        .plan-modal-overlay.active { display: flex; }
        .plan-modal { background: white; border-radius: var(--radius-lg); max-width: 420px; width: 100%; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative; }
        .plan-modal h2 { font-size: 1.5rem; font-weight: 700; color: var(--text-white); margin-bottom: 1.5rem; text-align: center; }
        .plan-modal .billing-choice { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; }
        .plan-modal .billing-option { flex: 1; display: flex; align-items: center; justify-content: center; padding: 0.75rem; border: 1px solid var(--border-dark); border-radius: 8px; cursor: pointer; font-size: 0.95rem; color: var(--text-gray); transition: all 0.2s; }
        .plan-modal .billing-option:hover { border-color: var(--primary); color: var(--primary); }
        .plan-modal .billing-option input { display: none; }
        .plan-modal .billing-option:has(input:checked) { border-color: var(--primary); background: rgba(37, 99, 235, 0.08); color: var(--primary); font-weight: 600; }
        .plan-modal .form-group { margin-bottom: 1rem; }
        .plan-modal .form-group label { display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-white); margin-bottom: 0.35rem; }
        .plan-modal .form-group input { width: 100%; padding: 0.65rem 0.9rem; border: 1px solid var(--border-dark); border-radius: 8px; font-size: 0.95rem; font-family: inherit; }
        .plan-modal .form-group input:focus { outline: none; border-color: var(--primary); }
        .plan-modal .btn-submit { width: 100%; padding: 0.9rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; font-family: inherit; }
        .plan-modal .btn-submit:hover { opacity: 0.9; }
        .plan-modal .btn-close { position: absolute; top: 1rem; right: 1rem; width: 36px; height: 36px; border: none; background: transparent; color: var(--text-gray); font-size: 1.5rem; cursor: pointer; line-height: 1; padding: 0; }
        .plan-modal .btn-close:hover { color: var(--text-white); }
    </style>
</head>

<body>
    <header class="navbar">
        <a href="/" class="logo">ciao<span style="color:var(--text-white)">cv</span></a>
        <nav class="nav-links">
            <a href="/tarifs" style="color:var(--primary); font-weight:700;" data-i18n="nav.service">Notre service</a>
            <a href="/guide-candidat" data-i18n="nav.guide">Guide candidat</a>
        </nav>
        <div class="nav-actions">
            <a href="#" class="lang-toggle" id="langToggleDesktop" style="font-weight:600; margin-right:1rem; color:var(--text-gray); text-decoration:none;">EN</a>
            <a href="https://app.ciaocv.com/connexion" class="btn-header-primary" data-i18n="nav.login">Se connecter</a>
            <button class="hamburger" aria-label="Menu" onclick="toggleMenu()"><span></span><span></span><span></span></button>
        </div>
    </header>

    <div class="mobile-menu" id="mobileMenu">
        <button class="hamburger active" style="position:absolute; top: 1.25rem; right: 5%; display:flex;" onclick="toggleMenu()"><span></span><span></span><span></span></button>
        <a href="#" class="lang-toggle" id="langToggleMobile" style="font-size: 1.2rem; margin-bottom: 1rem; color: var(--primary); font-weight: 700; text-decoration: none;">EN</a>
        <a href="/tarifs" onclick="toggleMenu()" data-i18n="nav.service">Notre service</a>
        <a href="/guide-candidat" onclick="toggleMenu()" data-i18n="nav.guide">Guide candidat</a>
        <div style="margin-top:2rem; display:flex; flex-direction:column; gap:1rem; width:80%; text-align:center;">
            <a href="https://app.ciaocv.com/connexion" class="btn-header-primary" style="display:block; text-align:center; padding:1rem;" data-i18n="nav.login">Se connecter</a>
        </div>
    </div>

    <div class="plan-modal-overlay" id="planModal" role="dialog" aria-modal="true" aria-labelledby="planModalTitle">
        <div class="plan-modal">
            <button type="button" class="btn-close" onclick="closePlanModal()" aria-label="Fermer">&times;</button>
            <h2 id="planModalTitle"></h2>
            <form id="planModalForm" action="https://app.ciaocv.com/signup" method="POST">
                <input type="hidden" name="plan_id" id="planModalPlanId">
                <input type="hidden" name="billing" id="planModalBilling" value="yearly">
                <div class="billing-choice" id="planModalBillingChoice" style="display:none;">
                    <label class="billing-option">
                        <input type="radio" name="billing_radio" value="monthly">
                        <span><?= $lang === 'en' ? 'Monthly' : 'Mensuel' ?></span>
                    </label>
                    <label class="billing-option">
                        <input type="radio" name="billing_radio" value="yearly" checked>
                        <span><?= $lang === 'en' ? 'Annual' : 'Annuel' ?></span>
                    </label>
                </div>
                <div class="form-group">
                    <label for="planModalNom"><?= $lang === 'en' ? 'Last name' : 'Nom' ?></label>
                    <input type="text" id="planModalNom" name="nom" required autocomplete="family-name">
                </div>
                <div class="form-group">
                    <label for="planModalPrenom"><?= $lang === 'en' ? 'First name' : 'Prénom' ?></label>
                    <input type="text" id="planModalPrenom" name="prenom" required autocomplete="given-name">
                </div>
                <div class="form-group">
                    <label for="planModalEmail"><?= $lang === 'en' ? 'Email' : 'Courriel' ?></label>
                    <input type="email" id="planModalEmail" name="email" required autocomplete="email">
                </div>
                <button type="submit" class="btn-submit"><?= $lang === 'en' ? 'Continue' : 'Continuer' ?></button>
            </form>
        </div>
    </div>

    <div class="pricing-hero">
        <h1 data-i18n="pricing.title">Identifiez le candidat parfait sans perdre une seconde.</h1>
        <p data-i18n="pricing.subtitle">Des tarifs simples et transparents pour moderniser votre processus de recrutement. Commencez gratuitement, évoluez selon vos besoins.</p>
    </div>

    <section class="pricing-section">
        <div class="pricing-grid">
            <?php
            $featuredIndex = -1;
            foreach ($plans as $i => $p) {
                if (!empty($p['is_popular']) || strcasecmp(trim($p['name']), 'Pro') === 0) { $featuredIndex = $i; break; }
            }
            foreach ($plans as $i => $p):
                $isFree = $p['price_monthly'] == 0 && $p['price_yearly'] == 0;
                $isFeatured = ($i === $featuredIndex);
                $videoText = $p['video_limit'] >= 9999 ? ($lang === 'en' ? 'Unlimited video interviews' : 'Entrevues vidéo illimitées') : $p['video_limit'] . ' entrevues vidéo';
            ?>
            <div class="pricing-card<?= $isFeatured ? ' featured' : '' ?>">
                <?php if ($isFeatured): ?><div class="pricing-badge">Populaire</div><?php endif; ?>
                <div class="price-header">
                    <h3><?= e($p['name']) ?></h3>
                    <div class="price-amount">
                        <?php if ($isFree): ?>
                            <?= $lang === 'en' ? 'Free' : 'Gratuit' ?>
                        <?php elseif ($p['price_monthly'] > 0 && $p['price_yearly'] > 0): ?>
                            <?= e($formatPrice($p['price_yearly'] / 12)) ?>$ <span class="price-period">/mois</span>
                            <div class="price-desc"><?= $lang === 'en' ? 'Billed annually' : 'Facturé annuellement' ?></div>
                            <div class="price-desc" style="font-size:0.85rem;">ou <?= e($formatPrice($p['price_monthly'])) ?>$ <?= $lang === 'en' ? 'if monthly' : 'si mensuel' ?></div>
                        <?php else: ?>
                            <?= e($formatPrice($p['price_monthly'])) ?>$
                            <div class="price-desc"><?= $lang === 'en' ? 'one-time payment' : 'paiement unique' ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <ul class="features-list">
                    <?php
                    $planFeatures = $p['features'] ?? [];
                    if (!empty($planFeatures)):
                        foreach ($planFeatures as $f): ?>
                    <li><?= e($f) ?></li>
                    <?php endforeach; else: ?>
                    <li><?= e($videoText) ?></li>
                    <li data-i18n="pricing.card.pro.feat.3">Outils collaboratifs</li>
                    <li data-i18n="pricing.card.pro.feat.4">Marque employeur</li>
                    <li data-i18n="pricing.card.one.feat.4">Questions personnalisées</li>
                    <?php endif; ?>
                </ul>
                <button type="button" class="btn-price <?= $isFeatured ? 'primary' : 'outline' ?>" data-plan-id="<?= e($p['id']) ?>" data-plan-name="<?= e($p['name']) ?>" data-is-free="<?= $isFree ? '1' : '0' ?>" data-price-monthly="<?= e($p['price_monthly']) ?>" data-price-yearly="<?= e($p['price_yearly']) ?>" onclick="openPlanModal(this)">
                    <?= $isFree ? ($lang === 'en' ? 'Create account' : 'Créer un compte') : ($lang === 'en' ? 'Start now' : 'Démarrer maintenant') ?>
                </button>
            </div>
            <?php endforeach; ?>
            <?php if (empty($plans)): ?>
            <p style="color:var(--text-gray); grid-column:1/-1; text-align:center;">Aucun forfait disponible.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($plans)): ?>
    <section class="comparison-section">
        <h2 style="text-align:center; font-size:2rem; font-weight:800; margin-bottom: 3rem; color:var(--text-white);" data-i18n="compare.title">Comparatif des fonctionnalités</h2>
        <table class="feature-table">
            <thead>
                <tr>
                    <th data-i18n="compare.col.feature">Fonctionnalité</th>
                    <?php foreach ($plans as $p): ?><th><?= e($p['name']) ?></th><?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td data-i18n="compare.row.video">Entrevues vidéo disponibles</td>
                    <?php foreach ($plans as $p):
                        $videoCell = $p['video_limit'] >= 9999 ? ($lang === 'en' ? 'Unlimited video interviews' : 'Entrevues vidéo illimitées') : $p['video_limit'] . ' entrevues vidéo';
                    ?>
                    <td><?= e($videoCell) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td data-i18n="compare.row.collab">Outils collaboratifs</td>
                    <?php foreach ($plans as $p): ?><td><span class="check-icon">✓</span></td><?php endforeach; ?>
                </tr>
                <tr>
                    <td data-i18n="compare.row.brand">Marque employeur</td>
                    <?php foreach ($plans as $p): ?><td><span class="check-icon">✓</span></td><?php endforeach; ?>
                </tr>
                <tr>
                    <td data-i18n="compare.row.questions">Questions personnalisées</td>
                    <?php foreach ($plans as $p): ?><td><span class="check-icon">✓</span></td><?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <section style="max-width:800px; margin:0 auto 6rem; padding:0 5%;">
        <h2 style="text-align:center; margin-bottom:3rem; font-size:2rem; color:var(--text-white); font-weight:800;" data-i18n="faq.title">Questions Fréquentes</h2>
        <div style="display:grid; gap:2rem;">
            <div>
                <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:0.5rem; color:var(--text-white);" data-i18n="faq.q1">Puis-je annuler à tout moment ?</h3>
                <p style="color:var(--text-gray);" data-i18n="faq.a1">Oui, l'offre Pro est sans engagement. Vous pouvez arrêter votre abonnement en un clic depuis votre espace. Aucun frais caché.</p>
            </div>
            <div>
                <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:0.5rem; color:var(--text-white);" data-i18n="faq.q2">Qu'est-ce qu'une candidature vidéo ?</h3>
                <p style="color:var(--text-gray);" data-i18n="faq.a2">C'est une réponse enregistrée par le candidat via sa webcam ou son téléphone. Vous définissez les questions, ils répondent quand ils veulent. Vous gagnez un temps précieux en évitant les premiers appels téléphoniques.</p>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-inner">
            <div class="footer-links">
                <h4>CIAOCV</h4>
                <ul>
                    <li><a href="/tarifs" data-i18n="footer.service">Notre Service</a></li>
                    <li><a href="/guide-candidat" data-i18n="footer.guide">Guide candidat</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4 data-i18n="footer.legal">Légal</h4>
                <ul>
                    <li><a href="/confidentialite" data-i18n="footer.privacy">Politique de confidentialité</a></li>
                    <li><a href="/conditions" data-i18n="footer.terms">Conditions d'utilisation</a></li>
                    <li><a href="#">EFVP</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4 data-i18n="footer.contact">Contact</h4>
                <ul>
                    <li><a href="mailto:bonjour@ciaocv.com">bonjour@ciaocv.com</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom" style="display:block; text-align:center;">
            <p style="margin-bottom: 0.5rem; opacity: 0.8; text-align:center;" data-i18n="footer.proudly">❤️<br>Fièrement humain</p>
            <p style="text-align:center;">© 2026 CiaoCV</p>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            if (menu.classList.contains('active')) { menu.classList.remove('active'); document.body.style.overflow = 'auto'; }
            else { menu.classList.add('active'); document.body.style.overflow = 'hidden'; }
            document.querySelectorAll('.hamburger').forEach(btn => btn.classList.toggle('active'));
        }
        function openPlanModal(btn) {
            const planId = btn.getAttribute('data-plan-id');
            const planName = btn.getAttribute('data-plan-name');
            const isFree = btn.getAttribute('data-is-free') === '1';
            const modal = document.getElementById('planModal');
            const title = document.getElementById('planModalTitle');
            const billingChoice = document.getElementById('planModalBillingChoice');
            const form = document.getElementById('planModalForm');
            form.reset();
            document.getElementById('planModalPlanId').value = planId;
            document.getElementById('planModalBilling').value = 'yearly';
            title.textContent = planName;
            billingChoice.style.display = isFree ? 'none' : 'flex';
            billingChoice.querySelectorAll('input').forEach(r => { r.checked = r.value === 'yearly'; });
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closePlanModal() {
            document.getElementById('planModal').classList.remove('active');
            document.body.style.overflow = '';
        }
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('planModal');
            const form = document.getElementById('planModalForm');
            modal.addEventListener('click', function(e) { if (e.target === modal) closePlanModal(); });
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closePlanModal(); });
            form.querySelectorAll('input[name="billing_radio"]').forEach(r => {
                r.addEventListener('change', function() { document.getElementById('planModalBilling').value = this.value; });
            });
        });
    </script>
    <script src="assets/js/i18n.js?v=1770868074"></script>
    <script src="assets/js/cookie-consent.js"></script>
</body>
</html>

<?php
// Charger les variables d'environnement
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $value = trim($value, '"\'');
            $_ENV[trim($key)] = $value;
        }
    }
}

/**
 * Envoie un email via ZeptoMail
 * 
 * @param string|array $to Email destinataire (string ou array ['address'=>, 'name'=>])
 * @param string $subject Sujet
 * @param string $htmlbody Corps HTML
 * @return array|false Réponse API ou false si erreur
 */
function send_zepto($to, $subject, $htmlbody) {
    $apiUrl = $_ENV['ZEPTO_API_URL'] ?? '';
    $token = $_ENV['ZEPTO_TOKEN'] ?? '';
    $fromAddress = $_ENV['ZEPTO_FROM_ADDRESS'] ?? '';
    $fromName = $_ENV['ZEPTO_FROM_NAME'] ?? 'CiaoCV';

    if (!$apiUrl || !$token) {
        error_log("ZeptoMail: Configuration manquante");
        return false;
    }

    // Formatage destinataire
    $toRecipient = [];
    if (is_array($to)) {
        $toRecipient[] = [
            "email_address" => [
                "address" => $to['address'],
                "name" => $to['name'] ?? ''
            ]
        ];
    } else {
        $toRecipient[] = [
            "email_address" => [
                "address" => $to,
                "name" => ""
            ]
        ];
    }

    $postData = [
        "from" => [
            "address" => $fromAddress,
            "name" => $fromName
        ],
        "to" => $toRecipient,
        "subject" => $subject,
        "htmlbody" => $htmlbody
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "authorization: " . $token,
            "cache-control: no-cache",
            "content-type: application/json",
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("ZeptoMail Error: " . $err);
        return false;
    }

    return json_decode($response, true);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CiaoCV - Vidéo</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #111827;
            --card-bg: #1f2937;
            --white: #ffffff;
            --text: #f9fafb;
            --text-light: #9ca3af;
            --border: #374151;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            text-align: center;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            letter-spacing: -2px;
        }

        .tagline {
            color: var(--text-light);
            font-size: 1rem;
            margin-bottom: 3rem;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--card-bg);
            padding: 1.25rem 1.5rem;
            border-radius: 1rem;
            text-decoration: none;
            color: var(--text);
            border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .menu-item:hover {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .menu-item:active {
            transform: translateY(0);
        }

        .menu-icon {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.2);
            border-radius: 0.75rem;
        }

        .menu-item:hover .menu-icon {
            background: rgba(255, 255, 255, 0.2);
        }

        .menu-text {
            flex: 1;
            text-align: left;
        }

        .menu-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .menu-desc {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .menu-item:hover .menu-desc {
            color: rgba(255, 255, 255, 0.8);
        }

        .menu-arrow {
            font-size: 1.25rem;
            color: var(--text-light);
        }

        .menu-item:hover .menu-arrow {
            color: white;
        }

        .footer {
            margin-top: 3rem;
            color: var(--text-light);
            font-size: 0.75rem;
        }

        .footer a {
            color: var(--primary);
            text-decoration: none;
        }

        /* iPhone safe area */
        @supports (padding-top: env(safe-area-inset-top)) {
            .container {
                padding-top: calc(2rem + env(safe-area-inset-top));
                padding-bottom: calc(2rem + env(safe-area-inset-bottom));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">CiaoCV</div>
        <p class="tagline">Votre CV vidéo en 60 secondes</p>

        <nav class="menu">
            <a href="candidate.php" class="menu-item">
                <div class="menu-icon">👤</div>
                <div class="menu-text">
                    <div class="menu-title">Espace candidats</div>
                    <div class="menu-desc">Enregistrer mon CV vidéo, mes candidatures</div>
                </div>
                <span class="menu-arrow">→</span>
            </a>

            <a href="employer.php" class="menu-item">
                <div class="menu-icon">🏢</div>
                <div class="menu-text">
                    <div class="menu-title">Espace employeur</div>
                    <div class="menu-desc">Gérer mes postes et candidats</div>
                </div>
                <span class="menu-arrow">→</span>
            </a>
        </nav>

        <div class="footer">
            <p>© 2026 CiaoCV — <a href="https://www.ciaocv.com">Retour au site</a></p>
        </div>
    </div>
</body>
</html>

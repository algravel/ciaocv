<?php
/**
 * Fonctions partagées
 * Prérequis : $_ENV chargé (via db.php ou chargement .env)
 */

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

/**
 * Dérive un nom d'entreprise plausible depuis l'email (ex: admin@olymel.com → Olymel).
 */
function company_name_from_email(string $email): string
{
    $email = trim($email);
    if ($email === '') return '';
    $at = strpos($email, '@');
    if ($at === false) return '';
    $domain = substr($email, $at + 1);
    $part = strpos($domain, '.') !== false ? strstr($domain, '.', true) : $domain;
    return $part !== false && $part !== '' ? ucfirst(strtolower($part)) : '';
}

/**
 * Vérifie le token Cloudflare Turnstile.
 *
 * @param string $token Token cf-turnstile-response
 * @param string|null $remoteIp IP du client (optionnel)
 * @return array Réponse de l'API siteverify (success, error-codes, etc.)
 */
function turnstile_verify(string $token, ?string $remoteIp = null): array
{
    $secret = $_ENV['TURNSTILE_SECRET_KEY'] ?? '';
    if ($secret === '' || $token === '') {
        return ['success' => false, 'error-codes' => ['missing-input-response']];
    }
    $data = ['secret' => $secret, 'response' => $token];
    if ($remoteIp !== null && $remoteIp !== '') {
        $data['remoteip'] = $remoteIp;
    }
    $ctx = stream_context_create([
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10,
        ],
    ]);
    $response = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    if ($response === false) {
        return ['success' => false, 'error-codes' => ['internal-error']];
    }
    $result = json_decode($response, true);
    return is_array($result) ? $result : ['success' => false, 'error-codes' => ['internal-error']];
}

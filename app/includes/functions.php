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

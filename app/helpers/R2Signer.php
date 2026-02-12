<?php
/**
 * R2Signer – Génère des presigned URLs S3v4 pour Cloudflare R2.
 * Réutilisable pour PUT (upload) et GET (lecture vidéo).
 */
class R2Signer
{
    /**
     * Génère un presigned URL (S3 Signature V4, compatible R2).
     *
     * @param string $objectKey  Chemin de l'objet dans le bucket (ex: entrevue/abc/file.mp4)
     * @param string $method     HTTP method (PUT ou GET)
     * @param string $contentType Content-Type (nécessaire pour PUT, vide pour GET)
     * @param int    $expires    Durée de validité en secondes
     */
    public static function presignedUrl(
        string $objectKey,
        string $method = 'GET',
        string $contentType = '',
        int    $expires = 3600
    ): ?string {
        $accessKey = $_ENV['R2_ACCESS_KEY_ID'] ?? '';
        $secretKey = $_ENV['R2_SECRET_ACCESS_KEY'] ?? '';
        $endpoint  = rtrim($_ENV['R2_ENDPOINT'] ?? '', '/');
        $bucket    = $_ENV['R2_BUCKET'] ?? 'ciaocv';

        if ($accessKey === '' || $secretKey === '' || $endpoint === '') {
            return null;
        }

        $region  = 'auto';
        $service = 's3';
        $now     = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $datestamp = $now->format('Ymd');
        $amzDate   = $now->format('Ymd\THis\Z');
        $scope     = $datestamp . '/' . $region . '/' . $service . '/aws4_request';

        $host = parse_url($endpoint, PHP_URL_HOST);
        $url  = $endpoint . '/' . $bucket . '/' . $objectKey;

        // Signed headers
        $signedHeaders = $contentType !== '' ? 'content-type;host' : 'host';

        $queryParams = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => $accessKey . '/' . $scope,
            'X-Amz-Date'          => $amzDate,
            'X-Amz-Expires'       => (string) $expires,
            'X-Amz-SignedHeaders' => $signedHeaders,
        ];
        ksort($queryParams);
        $canonicalQueryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        // Canonical headers
        $canonicalHeaders = '';
        if ($contentType !== '') {
            $canonicalHeaders .= 'content-type:' . $contentType . "\n";
        }
        $canonicalHeaders .= 'host:' . $host . "\n";

        // Canonical URI : encoder chaque segment individuellement (les / ne doivent PAS être encodés)
        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $objectKey)));

        // Canonical request
        $canonicalRequest = implode("\n", [
            $method,
            '/' . $bucket . '/' . $encodedKey,
            $canonicalQueryString,
            $canonicalHeaders,
            $signedHeaders,
            'UNSIGNED-PAYLOAD',
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = hash_hmac('sha256', 'aws4_request',
            hash_hmac('sha256', $service,
                hash_hmac('sha256', $region,
                    hash_hmac('sha256', $datestamp, 'AWS4' . $secretKey, true),
                    true),
                true),
            true);

        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        return $url . '?' . $canonicalQueryString . '&X-Amz-Signature=' . $signature;
    }

    /**
     * Presigned GET URL pour la lecture vidéo (1h par défaut).
     */
    public static function videoUrl(string $videoPath, int $expires = 3600): ?string
    {
        if ($videoPath === '') {
            return null;
        }
        return self::presignedUrl($videoPath, 'GET', '', $expires);
    }

    /**
     * Supprime un fichier sur R2 via l'API S3 (DELETE object).
     * Nécessite les credentials en ENV.
     */
    public static function deleteFile(string $objectKey): bool
    {
        $accessKey = $_ENV['R2_ACCESS_KEY_ID'] ?? '';
        $secretKey = $_ENV['R2_SECRET_ACCESS_KEY'] ?? '';
        $endpoint  = rtrim($_ENV['R2_ENDPOINT'] ?? '', '/');
        $bucket    = $_ENV['R2_BUCKET'] ?? 'ciaocv';

        if ($accessKey === '' || $secretKey === '' || $endpoint === '') {
            error_log("R2Signer::deleteFile - Credentials manquants");
            return false;
        }

        $region  = 'auto';
        $service = 's3';
        $now     = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $amzDate = $now->format('Ymd\THis\Z');
        $datestamp = $now->format('Ymd');
        
        $host = parse_url($endpoint, PHP_URL_HOST);
        $uri  = '/' . $bucket . '/' . implode('/', array_map('rawurlencode', explode('/', $objectKey)));
        
        // 1. Canonical Request
        $method = 'DELETE';
        $canonicalUri = $uri;
        $canonicalQuery = '';
        $canonicalHeaders = "host:" . $host . "\n" . "x-amz-content-sha256:e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855\n" . "x-amz-date:" . $amzDate . "\n";
        $signedHeaders = "host;x-amz-content-sha256;x-amz-date";
        $payloadHash = "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"; // Empty payload hash

        $canonicalRequest = "$method\n$canonicalUri\n$canonicalQuery\n$canonicalHeaders\n$signedHeaders\n$payloadHash";

        // 2. String to Sign
        $algorithm = "AWS4-HMAC-SHA256";
        $credentialScope = "$datestamp/$region/$service/aws4_request";
        $stringToSign = "$algorithm\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);

        // 3. Signature
        $kSecret = 'AWS4' . $secretKey;
        $kDate = hash_hmac('sha256', $datestamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // 4. Send Request
        $authorizationHeader = "$algorithm Credential=$accessKey/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";
        
        $url = $endpoint . $uri;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: $authorizationHeader",
            "x-amz-date: $amzDate",
            "x-amz-content-sha256: $payloadHash",
            "Host: $host"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log("R2Signer::deleteFile - HTTP $httpCode - $response - Error: $error");
            return false;
        }
    }
}

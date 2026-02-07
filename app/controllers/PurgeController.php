<?php
/**
 * Purge LSCache (LiteSpeed) – endpoint sécurisé par secret.
 * Appelé après un déploiement pour vider le cache de l’ensemble des pages.
 *
 * Utilisation : GET /purge-cache avec en-tête X-Purge-Secret: <PURGE_CACHE_SECRET>
 * Ou depuis la ligne de commande après upload :
 *   curl -s -H "X-Purge-Secret: \$PURGE_CACHE_SECRET" "https://app.ciaocv.com/purge-cache"
 */
class PurgeController extends Controller
{
    public function index(): void
    {
        $secret = $_ENV['PURGE_CACHE_SECRET'] ?? '';

        if ($secret === '') {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not configured';
            exit;
        }

        $provided = $_SERVER['HTTP_X_PURGE_SECRET'] ?? '';
        if (!hash_equals($secret, (string) $provided)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }

        // LiteSpeed : purger tout le cache quand la réponse contient ce header
        header('X-LiteSpeed-Purge: *');
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(200);
        echo 'OK';
        exit;
    }
}

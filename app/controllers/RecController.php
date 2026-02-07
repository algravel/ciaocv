<?php
/**
 * Contrôleur pour la page candidat – entrevue de présélection
 * URL : /entrevue/{longId} (ancienne URL /rec/{longId} redirigée en 301)
 * Source unique des données : Affichage::getPosteByLongId()
 */
class RecController extends Controller
{
    public function show(string $longId): void
    {
        $poste = Affichage::getPosteByLongId($longId);
        if (!$poste) {
            http_response_code(404);
            require VIEWS_PATH . '/errors/404.php';
            return;
        }

        $this->view('rec.index', [
            'longId'    => $longId,
            'poste'     => $poste,
            'pageTitle' => 'Entrevue de présélection',
        ], 'rec');
    }
}

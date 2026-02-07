<?php
/**
 * Redirections 301 des anciennes URLs vers les slugs SEO-friendly.
 */
class RedirectController extends Controller
{
    public function toConnexion(): void
    {
        $this->redirect('/connexion', 301);
    }

    public function toTableauDeBord(): void
    {
        $this->redirect('/tableau-de-bord', 301);
    }

    public function toEntrevue(string $id): void
    {
        $this->redirect('/entrevue/' . $id, 301);
    }
}

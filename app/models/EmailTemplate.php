<?php
/**
 * Modèle EmailTemplate
 * Données mock – à remplacer par des requêtes DB.
 */
class EmailTemplate
{
    /**
     * @return array<int, array{title: string, content: string}>
     */
    public static function getAll(): array
    {
        return [
            [
                'title'   => 'Confirmation de réception',
                'content' => "Bonjour {{nom_candidat}},\n\nNous avons bien reçu votre candidature pour le poste de {{titre_poste}} chez {{nom_entreprise}} et nous vous en remercions.\n\nNotre équipe examinera votre dossier dans les prochains jours. Nous vous tiendrons informé(e) de la suite du processus.\n\nCordialement,\nL'équipe de recrutement",
            ],
            [
                'title'   => "Invitation à l'entrevue vidéo",
                'content' => "Bonjour {{nom_candidat}},\n\nFélicitations ! Votre profil a retenu notre attention pour le poste de {{titre_poste}}.\n\nNous vous invitons à réaliser une entrevue vidéo de présélection. Vous pourrez enregistrer vos réponses au moment qui vous convient.\n\nCliquez sur le lien ci-dessous pour commencer :\n[Lien vers l'entrevue]\n\nCordialement,\nL'équipe de recrutement",
            ],
            [
                'title'   => 'Refus poli',
                'content' => "Bonjour {{nom_candidat}},\n\nNous vous remercions sincèrement pour l'intérêt que vous avez porté à notre offre de {{titre_poste}}. Après une analyse attentive de l'ensemble des candidatures reçues, nous avons décidé de poursuivre avec d'autres profils.\n\nNous conservons votre dossier et ne manquerons pas de vous recontacter si une opportunité correspondant à votre profil se présente.\n\nCordialement,\nL'équipe de recrutement",
            ],
            [
                'title'   => 'Poste comblé',
                'content' => "Bonjour {{nom_candidat}},\n\nNous tenons à vous informer que le poste de {{titre_poste}} pour lequel vous avez postulé a été comblé.\n\nNous avons reçu un grand nombre de candidatures de qualité et la décision a été difficile.\n\nNous vous encourageons à consulter nos futures offres et à postuler de nouveau.\n\nMerci et bonne continuation,\nL'équipe de recrutement",
            ],
        ];
    }
}

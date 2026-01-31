<?php
/**
 * Données démo pour la maquette fonctionnelle (quand la DB n'est pas prête)
 */
return [
    'jobs' => [
        [
            'id' => 1,
            'title' => 'Développeur Frontend React',
            'description' => "Nous cherchons un développeur passionné pour rejoindre notre équipe.\n\nResponsabilités : développement de l'interface utilisateur, intégration API, tests.",
            'status' => 'active',
            'nb_candidates' => 4,
            'questions' => [
                ['question_text' => 'Parlez-nous de votre expérience avec React'],
                ['question_text' => 'Quel projet êtes-vous le plus fier d\'avoir réalisé ?'],
                ['question_text' => 'Comment gérez-vous le travail en équipe ?'],
            ],
        ],
        [
            'id' => 2,
            'title' => 'Designer UI/UX',
            'description' => 'Rejoignez notre équipe créative pour concevoir des expériences utilisateur mémorables.',
            'status' => 'draft',
            'nb_candidates' => 1,
            'questions' => [
                ['question_text' => 'Quelle est votre approche du design centré utilisateur ?'],
                ['question_text' => 'Présentez un portfolio que vous aimez'],
            ],
        ],
        [
            'id' => 3,
            'title' => 'Chef de projet digital',
            'description' => 'Poste pourvu.',
            'status' => 'closed',
            'nb_candidates' => 2,
            'questions' => [
                ['question_text' => 'Décrivez une gestion de projet complexe'],
                ['question_text' => 'Comment pilotez-vous une équipe ?'],
            ],
        ],
    ],
    'candidates' => [
        1 => [
            ['candidate_name' => 'Marie Dupont', 'candidate_email' => 'marie@exemple.com', 'status' => 'new', 'video_url' => ''],
            ['candidate_name' => 'Thomas Martin', 'candidate_email' => 'thomas@exemple.com', 'status' => 'accepted', 'video_url' => ''],
            ['candidate_name' => 'Sophie Bernard', 'candidate_email' => 'sophie@exemple.com', 'status' => 'rejected', 'video_url' => ''],
            ['candidate_name' => 'Alex Tremblay', 'candidate_email' => 'alex@exemple.com', 'status' => 'pool', 'video_url' => ''],
        ],
        2 => [
            ['candidate_name' => 'Lucas Petit', 'candidate_email' => 'lucas@exemple.com', 'status' => 'new', 'video_url' => ''],
        ],
        3 => [
            ['candidate_name' => 'Julie Roy', 'candidate_email' => 'julie@exemple.com', 'status' => 'accepted', 'video_url' => ''],
            ['candidate_name' => 'Marc Leblanc', 'candidate_email' => 'marc@exemple.com', 'status' => 'rejected', 'video_url' => ''],
        ],
    ],
    'my_profile' => [
        'name' => 'Marie Dupont',
        'email' => 'marie@exemple.com',
        'presentations' => [
            ['title' => 'Présentation générale', 'has_video' => true],
            ['title' => 'Focus développement', 'has_video' => true],
            ['title' => 'Slot libre', 'has_video' => false],
        ],
    ],
    'my_applications' => [
        ['job_id' => 1, 'job_title' => 'Développeur Frontend React', 'job_status' => 'active'],
        ['job_id' => 2, 'job_title' => 'Designer UI/UX', 'job_status' => 'draft'],
        ['job_id' => 3, 'job_title' => 'Chef de projet digital', 'job_status' => 'closed'],
    ],
];

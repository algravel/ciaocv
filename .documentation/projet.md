# CiaoCV - Le Recrutement par Vidéo

## Vision du Produit
CiaoCV révolutionne le marché de l'emploi en remplaçant les descriptions textuelles et les lettres de motivation par des interactions vidéo courtes et authentiques. L'objectif est d'humaniser le recrutement et de gagner du temps.

## Fonctionnalités Clés

### 1. Le Concept "60 Secondes"
- **Pour les Entreprises** : Elles doivent présenter le poste, l'équipe et la culture en une vidéo de 60 secondes maximum. Plus de pavés de texte illisibles.
- **Pour les Candidats** : Ils répondent à l'offre par une vidéo de 60 secondes (le "pitch"). Plus de CV papier standardisé, place à la personnalité.

### 2. Espace Entreprise
- Création et gestion des affichages (offres d'emploi).
- Enregistrement ou upload du pitch vidéo de l'offre.
- Tableau de bord de suivi des candidatures (ATS simplifié).
- Visionnage des réponses vidéo des candidats.
- Gestion du statut des candidats (Intéressant, À recontacter, Refusé).

### 3. Espace Candidat
- Recherche d'opportunités (feed vidéo ou liste).
- Enregistrement du pitch vidéo pour chaque candidature.
- Tableau de bord "Mes Candidatures" : suivi des statuts (Vu, En attente, etc.).
- Profil candidat simple.

## Stack Technique (Prévisionnel)
- **Frontend** : À définir (HTML/JS moderne ou Framework JS).
- **Backend** : PHP / MySQL (selon l'environnement actuel).
- **Stockage** : FTP / Cloud pour les vidéos.
- **Sécurité** : Chiffrement des données personnelles (AES-256).

## Architecture et Domaines

- **Site Principal (Vitrine)** : `www.ciaocv.com`
  - Dossier sur le serveur : `/public_html`
  - Contient : Landing page (`index.html`), assets publics.

- **Application (Candidat/Employeur)** : `app.ciaocv.com`
  - Dossier sur le serveur : `/app` (Racine FTP, hors de public_html pour sécurité, pointé par sous-domaine).
  - Contient : Logique métier PHP (`index.php`, `record.php`, `view.php`).

- **Documentation** :
  - Dossier local : `.documentation/` (ne pas uploader).
  - Contient : `design.md`, `projet.md`, etc.

## Module Gestion (administration)

- **Journalisation** : Chaque opération **Create**, **Update** ou **Delete** doit être journalisée dans `gestion_events` et affichée dans le dashboard (section « Journalisation des événements »).

## Prochaines Étapes
1. Design de la Landing Page (Page d'attente).
2. Maquettage des flux vidéo.
3. Développement du module d'upload et lecture vidéo.

# Design System - CiaoCV

Ce document définit les normes visuelles et ergonomiques pour l'application CiaoCV, basées sur l'interface "Blue & Clean" (style moderne, arrondi, épuré).

## 1. Global / Design System

### Palette de Couleurs
- **Primaire (Bleu Royal)** : `#2563EB` (Utilisé pour : Header, Boutons principaux, Icônes actives)..
- **Secondaire (Violet/Gradient)** : `#7C3AED` (Utilisé pour : Accents, Barres de progression, Gradients).
- **Arrière-plan (App)** : `#F3F4F6` (Gris très clair, pour faire ressortir les cartes).
- **Surface (Cartes)** : `#FFFFFF` (Blanc pur).
- **Texte Principal** : `#111827` (Presque noir).
- **Texte Secondaire** : `#6B7280` (Gris moyen).
- **Succès/Validation** : `#10B981` (Vert).
- **Erreur/Danger** : `#EF4444` (Rouge).

### Typographie
- **Font Family** : `Inter`, `Roboto`, ou `System UI`. Sans-serif, moderne et lisible.
- **Titres** : Gras (Bold 700), Grande taille.
- **Corps** : Regular (400) ou Medium (500).
- **Labels/Boutons** : Semi-Bold (600).

### Formes et Espacement
- **Arrondis (Border Radius)** :
  - **Cartes** : `16px` à `24px` (Très arrondi).
  - **Boutons** : `50px` (Pill shape) ou `12px` (Arrondi standard).
  - **Header** : Arrondi spécifique en bas (`border-bottom-left-radius: 30px`, `border-bottom-right-radius: 30px`).
- **Ombres (Box Shadow)** :
  - Douces et diffusées : `0 10px 15px -3px rgba(0, 0, 0, 0.1)`.
- **Espacement** : Aéré (`padding: 20px`), séparation claire entre les sections.

---

## 2. App Côté Candidat (Client)

L'interface candidat se concentre sur la découverte, la recherche et le profil.

### Header "Hero"
- Fond bleu (`Primary`).
- Contient : Avatar utilisateur, Salutation ("Bonjour, [Nom]"), Icône de notification.
- **Barre de Recherche** : Intégrée dans le header (fond semi-transparent ou blanc, icône loupe).

### Navigation
- **Bottom Tab Bar** : Fixe en bas, fond blanc.
- Icônes : Home, Candidatures, Favoris, Profil.
- Indicateur actif : Icône bleue + point ou fond subtil.

### Composants Clés
- **Carte "Compléter Profil"** :
  - Visuel attractif (Illustration 3D ou photo pro).
  - Barre de progression (ex: "Profil complété à 40%").
  - CTA : "Ajouter expérience" ou "Voir jobs".
- **Liste des Jobs** :
  - Carte blanche.
  - Logo entreprise (Carré arrondi).
  - Titre du poste (Gras) + Entreprise (Gris).
  - Tags : "Remote", "Full time", "Junior" (Fond bleu pâle, texte bleu).
  - Bouton d'action rapide (Favori, Postuler).
- **Vue Détail (Offre)** :
  - En-tête avec Logo centré.
  - Cartes de stats (Salaire, Expérience, Type).
  - Onglets : "Description", "Entreprise", "Avis".
  - Bouton sticky en bas : "Postuler maintenant" (Large, Bleu).

---

## 3. App Côté Employeur (Entreprise)

L'interface employeur reprend les codes visuels mais avec une densité d'information adaptée à la gestion.

### Dashboard
- **Header** : Similaire (Bleu), mais avec des actions rapides (ex: "Publier une offre").
- **Cartes "Statistiques"** :
  - Nombre de vues, Candidatures reçues, Entretiens prévus.
  - Graphiques simples (Ligne ou Barres) sur fond blanc.

### Gestion des Candidats (ATS Simplifié)
- **Liste des Candidats** :
  - Format "Carte" par candidat.
  - Photo/Vidéo Preview (Rond ou rectangle).
  - Nom + Poste visé.
  - Actions : "Voir le pitch vidéo", "Accepter" (Vert), "Refuser" (Rouge/Gris).
- **Vue "Pitch Vidéo"** :
  - Lecteur vidéo central (Format portrait 9:16 ou paysage optimisé).
  - Infos candidat en dessous (CV résumé, Compétences).
  - Boutons d'action flottants ou fixes en bas.

### Création d'Offre
- Formulaire par étapes (Wizard).
- Champs input arrondis (`border-radius: 12px`, fond gris clair `#F9FAFB`).
- Enregistrement du "Pitch Vidéo Entreprise" (Interface caméra similaire à celle du candidat).

# Design System - CiaoCV (Vitrine & Showcase)

Ce document définit les normes visuelles, ergonomiques et techniques de la vitrine (site public) et de l'identité visuelle globale de CiaoCV. Le design est inspiré d'une esthétique moderne, épurée et "premium" (style SaaS/Tech).

## 1. Identité Visuelle (Design Tokens)

### Palette de Couleurs
- **Primaire (Bleu Tech)** : `#2563EB` (Utilisé pour : Logos, boutons principaux, titres forts).
- **Accent (Violet)** : `#8B5CF6` (Utilisé pour : Dégradés, micro-interactions).
- **Texte Principal** : `#0F172A` (Bleu nuit très foncé pour une lisibilité optimale).
- **Texte Secondaire** : `#64748B` (Gris ardoise pour les sous-titres et descriptions).
- **Gris Muet** : `#94A3B8`.
- **Fond de page** : `#FFFFFF` (Blanc pur) avec des sections alternées en `#F8FAFC`.
- **Succès** : `#10B981` (Vert émeraude).
- **Erreur** : `#EF4444` (Rouge vif).

### Typographie
- **Police (Font Family)** : `Montserrat` (Google Fonts). Un sans-serif géométrique, moderne et professionnel.
- **Titres (h1, h2)** : Gras (800) avec un `letter-spacing: -0.025em`.
- **Corps de texte** : Regular (400) ou Medium (500). Taille de base : `16px`.
- **Navigation/Boutons** : Semi-Bold (600) ou Bold (700).

### Formes et Style "Glassmorphism"
- **Arrondis (Border Radius)** :
  - **Boutons** : `50px` (Pill shape) pour un look moderne et amical.
  - **Cartes** : `16px` à `24px` (Large radius).
- **Glassmorphism** :
  - Utilisation de `backdrop-filter: blur(10px)`.
  - Backgrounds semi-transparents : `rgba(255, 255, 255, 0.9)`.
  - Bordures subtiles : `1px solid rgba(226, 232, 240, 0.8)`.
- **Ombres (Shadows)** : Très diffuses et légères (`rgba(0, 0, 0, 0.05)`).

---

## 2. Architecture de la Vitrine

Le site est composé de quatre pages principales partageant la même structure de navigation et de pied de page :
1. **Accueil (`index.html`)** : Présentation de la proposition de valeur, stats et bénéfices.
2. **Notre Service (`tarifs.html`)** : Table de prix et comparatif détaillé des fonctionnalités.
3. **Guide Candidat (`guide-candidat.html`)** : Page de conseils pour réussir l'entrevue vidéo (checklists, astuces).
4. **Espace Candidat (`emplois.html`)** : Portail de connexion pour les candidats.

### Header (Navigation)
- **Sticky** : Reste fixé en haut de l'écran avec un fond "glass" au défilement.
- **Actions** : Toggle de langue (FR/EN) et boutons de connexion doubles (Espace Recruteur vs Espace Candidat).
- **Mobile** : Menu hamburger avec navigation plein écran et toggle de langue intégré.

### Footer (Pied de page)
- **Couleur** : Fond `#2563EB` (Bleu Primaire) avec texte en blanc.
- **Contenu** : Liens rapides, mentions légales, branding "Un projet de 3W Capital" et signature "Fièrement humain ❤️".

---

## 3. Système International (i18n)

La vitrine est entièrement bilingue (**Français** par défaut, **Anglais**) via un système client-side léger (`assets/js/i18n.js`).

- **Détection** : Automatique via la langue du navigateur.
- **Persistance** : Sauvegarde dans le `localStorage`.
- **Implémentation** : Attributs `data-i18n="clé.traduction"` sur les éléments HTML.
- **Switch** : Bascule instantanée sans rechargement de page.

---

## 4. Composants UX Clés

- **Hero CTAs** : Pas de détour, le bouton principal ("Commencer maintenant") dirige directement vers la page des tarifs/services.
- **Checklists interactives** : Utilisées dans le guide candidat pour engager l'utilisateur.
- **Tableau comparatif** : Design clair sur fond gris (`#F1F5F9`) pour une lecture facilitée des offres B2B.
- **Favicon** : Logo minimaliste "c" bleu sur fond blanc arrondi.

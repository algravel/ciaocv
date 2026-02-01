# Documentation Design System & UI - Ecme Next

**Objectif :** Reproduire l'interface visuelle (Look & Feel) du template d'administration "Ecme Next".
**Stack Technique Cible :** React (Next.js), Tailwind CSS, Lucide React (Icônes).

---

## Référence visuelle cible — Version ordinateur (desktop)

**Pour la version ordinateur de l'app, le design doit correspondre à ce type d'interface « gestion » :**

* **Sidebar fixe à gauche** : logo en haut, navigation par catégories (labels en majuscules), items de menu avec icône + libellé, item actif mis en évidence (fond bleu clair, texte indigo).
* **Header fin en haut** : barre blanche sur toute la largeur du contenu ; à gauche : menu hamburger (pour replier la sidebar), recherche ; à droite : langue, notifications (badge), paramètres, avatar utilisateur avec indicateur de statut.
* **Zone principale large** : fond gris clair (`bg-gray-50`), contenu organisé en **grille multi-colonnes** (pas une colonne étroite centrée).
* **Cartes (cards)** : cartes blanches à bords arrondis, bordures légères, ombre légère ; métriques (Total profit, Total order, etc.) avec icône colorée dans un cercle, gros chiffre, libellé, variation en % (vert/rouge).
* **Mise en page** : sections type "Overview" avec cartes de stats en ligne, graphiques (courbes), blocs "Sales target" (jauge circulaire), listes "Top product" avec vignette + texte + indicateur.
* **Esthétique** : minimaliste, aéré, palette claire (blanc, gris clair), accents indigo/bleu pour les actions et l’état actif.

*Référence visuelle : capture d’écran type dashboard Ecme (sidebar + header + grille de cartes et graphiques). C’est ce rendu « gestion » qui est visé pour le desktop ; la version mobile reste adaptée (empilage, pas de sidebar fixe).*

---

## 1. Fondations & Design Tokens (Tailwind Config)

Le design est "propre", minimaliste et aéré, typique des interfaces SaaS modernes.

### 🎨 Palette de Couleurs (Colors)
Le thème utilise principalement les couleurs par défaut de Tailwind avec une couleur primaire personnalisée (souvent Indigo ou Blue) et des gris neutres (Slate ou Gray) pour la structure.

* **Primaire (Brand) :** `Indigo-600` (Light) / `Indigo-500` (Dark)
    * *Hex ref:* `#4f46e5` (Primary), `#4338ca` (Hover)
* **Backgrounds :**
    * `bg-white` (Light mode, surface des cartes)
    * `bg-gray-50` / `bg-slate-50` (Fond de page principal)
    * `bg-gray-900` / `bg-slate-900` (Dark mode background)
* **Texte :**
    * Titres : `text-gray-900` (Gras/Semi-bold)
    * Corps : `text-gray-500` ou `text-slate-500`
    * Muted : `text-gray-400`
* **Bordures :** `border-gray-200` (Subtil)

### 🔠 Typographie
Police sans-serif moderne, très lisible.
* **Font Family :** `Inter` (Recommandé) ou `Plus Jakarta Sans`.
* **Échelle :**
    * `text-sm` (14px) pour le corps de texte standard et les inputs.
    * `text-xs` (12px) pour les labels secondaires.
    * `text-xl` / `text-2xl` pour les titres de page.
    * `font-semibold` (600) pour les boutons et titres.

### 📐 Formes & Espacements (Shape & Spacing)
* **Radius (Arrondis) :** Généreux. Utilisez `rounded-xl` pour les cartes et `rounded-lg` pour les inputs/boutons.
* **Ombres (Shadows) :** Douces et diffusées. `shadow-sm` pour les cartes, `shadow-md` au survol.
* **Spacing :** Interface aérée. Padding interne des cartes souvent `p-6` ou `p-5`.

---

## 2. Page de Connexion (Login)
**URL :** `/sign-in`
**Layout :** Split Screen (Écran divisé) ou Centré (selon la variante). La version "Ecme" utilise souvent un **Split Layout**.

### Structure Visuelle
1.  **Conteneur Principal :** `h-screen w-full grid grid-cols-1 md:grid-cols-2`
2.  **Zone Gauche (Formulaire) :**
    * Fond : `bg-white`
    * Alignement : Flex center (`flex flex-col justify-center items-center`)
    * Largeur content : `w-full max-w-md`
    * **Logo :** Placé en haut à gauche ou centré au-dessus du titre.
    * **Titres :**
        * H1: "Welcome back!" (`text-2xl font-bold mb-2`)
        * Sub: "Please enter your credentials to sign in!" (`text-gray-500 text-sm mb-8`)
3.  **Zone Droite (Visuel/Auth Side) :**
    * Classe : `hidden md:block bg-indigo-50` (ou image de fond).
    * Contenu : Une illustration vectorielle ou une image abstraite moderne au centre.
    * *Note :* Si le layout est "Card", utiliser un conteneur centré `max-w-md` avec `shadow-lg rounded-2xl` sur fond gris.

### Composants du Formulaire
* **Input Fields :**
    * Style : `w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all`
    * Label : `block text-sm font-medium text-gray-700 mb-1.5`
* **Bouton Principal (Sign In) :**
    * Style : `w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg transition-colors`
* **Séparateur (Divider) :**
    * Texte : "or continue with"
    * Style : Ligne fine grise avec texte au milieu (`flex items-center gap-2 text-gray-400 text-xs uppercase`).
* **Social Login (Google/Github) :**
    * Grid : `grid grid-cols-2 gap-4`
    * Bouton : `border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 py-2.5 rounded-lg flex justify-center items-center gap-2 font-medium transition`

---

## 3. Interface Administration (Dashboard)
**URL :** `/dashboards/ecommerce`
**Layout :** Sidebar Layout standard (Sidebar fixe à gauche, Header fixe en haut).

**C’est ce type de layout qui est visé pour la version ordinateur de CiaoCV** : sidebar + header + zone principale en grille avec cartes blanches, métriques, listes ; pas une interface « mobile-first » étroite sur grand écran. Voir la référence visuelle en tête de document.

### A. Sidebar (Navigation Latérale)
* **Dimensions :** Largeur `w-64` (ou `w-72`), Hauteur `h-screen`, Fixe.
* **Couleurs :**
    * *Option 1 (Light) :* `bg-white border-r border-gray-200`
    * *Option 2 (Dark/Brand) :* `bg-slate-900 text-white`
* **Éléments :**
    * **Logo :** Hauteur ~64px, Padding `px-6`, centré verticalement.
    * **Menu Items :**
        * Padding : `px-4 py-2.5 mx-2`
        * Radius : `rounded-lg`
        * État Inactif : `text-gray-500 hover:bg-gray-100 hover:text-gray-900`
        * État Actif : `bg-indigo-50 text-indigo-600 font-semibold` (si sidebar blanche) OU `bg-indigo-600 text-white` (si sidebar sombre).
    * **Groupes :** Petits labels en majuscules (`text-xs font-bold text-gray-400 uppercase tracking-wider px-6 mt-6 mb-2`).

### B. Header (Top Bar)
* **Position :** Sticky top (`sticky top-0 z-50`).
* **Style :** `bg-white/80 backdrop-blur-md border-b border-gray-200 h-16 px-6 flex items-center justify-between`.
* **Contenu :**
    * **Gauche :** Toggle Sidebar (Menu Hamburger), Recherche globale (`bg-gray-100 rounded-full px-4 py-2 text-sm text-gray-500 w-64`).
    * **Droite :**
        * Actions : Notifications, Changement de langue, Dark Mode toggle.
        * Profil : Avatar rond (`rounded-full h-8 w-8`) avec statut en ligne.

### C. Contenu Principal (Dashboard Ecommerce)
* **Fond :** `bg-gray-50` (pour créer du contraste avec les cartes blanches).
* **Padding :** `p-6` (padding global du contenu).
* **Structure de Grille (Grid) :**
    * Utiliser `grid grid-cols-12 gap-6`.
* **Widgets Tyiques (Cartes) :**
    * **Stat Cards (4 en haut) :** `col-span-12 md:col-span-6 lg:col-span-3`.
        * Contenu : Icône (avec fond coloré clair ex: `bg-blue-100 text-blue-600`), Valeur (Gros chiffre), Label, Indicateur de croissance (Vert/Rouge).
    * **Sales Chart (Principal) :** `col-span-12 lg:col-span-8 bg-white rounded-xl shadow-sm p-5`.
    * **Recent Orders (Tableau) :**
        * Tableau propre : Header gris clair (`bg-gray-50 text-xs uppercase text-gray-500`), lignes avec bordure (`border-b border-gray-100`), padding confortable (`py-3`).
        * Status Badges : `px-2.5 py-0.5 rounded-full text-xs font-medium` (ex: Paid = `bg-green-100 text-green-700`).

---

## 4. Recommandations pour l'Agent de Code

1.  **Initialisation :** Installer `lucide-react` pour les icônes (car elles ressemblent au style Ecme) et `recharts` pour les graphiques.
2.  **Base CSS :** Ajouter `@tailwind base; @tailwind components; @tailwind utilities;`
3.  **Composant Card :** Créer un composant réutilisable `<Card className="bg-white rounded-xl shadow-sm border border-gray-100 p-6" />`.
4.  **Interactivité :** Assurer que les inputs ont un état `focus:ring` visible (accessibilité et esthétique moderne).
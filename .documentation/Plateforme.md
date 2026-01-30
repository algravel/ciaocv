# Document 2 : Fonctionnalités de la plateforme et expérience utilisateur

## 2.1 Conformité « Privacy by Design » (Loi 25)

La Loi modernisant des dispositions législatives en matière de protection des renseignements personnels (**Loi 25**) n'est pas une simple contrainte administrative ; c'est le cadre structurant de toutes les fonctionnalités de la plateforme. En 2026, la non-conformité expose l'entreprise à des sanctions pénales et administratives pouvant atteindre **25 millions de dollars** ou **4 % du chiffre d'affaires mondial**.

### 2.1.1 Gestion granulaire du consentement

L'interface utilisateur doit dépasser la simple bannière de cookies. Le tableau de bord candidat intègre un **« Centre de Contrôle des Données »** :

- **Consentement biométrique :** La collecte de CVs vidéo implique la captation de la voix et de l'image du visage, définies comme des caractéristiques biométriques lorsqu'elles permettent l'identification. Avant tout enregistrement, une fenêtre modale distincte doit obtenir un consentement exprès : *« Je consens à l'utilisation de mon image et de ma voix pour les fins exclusives de ma candidature, pour une durée de X mois »*.
- **Droit de retrait et d'oubli :** Un bouton d'action directe permet au candidat de retirer son consentement. Techniquement, cela déclenche un appel API vers Backblaze B2 pour une suppression définitive (*Hard Delete*) des fichiers vidéo et une anonymisation des entrées dans la base de données SQL et l'index Meilisearch.

### 2.1.2 Portabilité des données

Conformément aux dispositions entrées en vigueur en septembre 2024, la plateforme offre une fonctionnalité d'exportation automatisée.

- **Format technologique structuré :** Le candidat peut télécharger un fichier (JSON ou XML) contenant l'intégralité de son profil, ses lettres de présentation, et l'historique de ses candidatures. Cela permet à l'utilisateur de « repartir » avec ses données, renforçant la confiance et la transparence.

### 2.1.3 Registre des incidents automatisé

La plateforme intègre un **journal d'audit interne immuable**. Toute tentative d'accès non autorisé ou toute anomalie détectée dans l'accès aux données personnelles est consignée automatiquement dans un **registre des incidents de confidentialité**, une obligation légale pour permettre au Responsable de la Protection des Renseignements Personnels (RPRP) de documenter et rapporter les violations potentielles à la Commission d'accès à l'information (CAI).

---

## 2.2 Module de recrutement vidéo 360°

Pour se différencier d'Indeed, la plateforme mise sur l'**humanisation du processus** via la vidéo, tout en atténuant les biais inconscients.

### 2.2.1 Fonctionnalités candidat : le CV vidéo augmenté

- **Enregistrement guidé :** Pour éviter le syndrome de la page blanche, l'interface propose des questions aléatoires ou sélectionnées par l'employeur (ex. : *« Parlez-nous d'un projet dont vous êtes fier »*). La vidéo est limitée à **90 secondes** pour forcer la synthèse.
- **Option « Anti-biais » (Floutage IA) :** Une innovation majeure pour l'équité. Le candidat peut choisir d'activer un filtre qui floute son visage et neutralise la hauteur de sa voix lors de la première écoute par le recruteur. Cela permet une évaluation basée sur le contenu sémantique (les mots, la structure de la pensée) plutôt que sur l'apparence ou l'origine, répondant aux préoccupations éthiques modernes.

### 2.2.2 Fonctionnalités employeur : la marque employeur dynamique

- **Offres d'emploi vidéo :** L'employeur peut remplacer ou compléter la description textuelle par une vidéo de l'équipe ou des bureaux. Hébergée sur Cloudflare Stream, cette vidéo se charge instantanément et s'adapte à la bande passante du candidat. C'est un outil puissant pour attirer les candidats passifs qui ne lisent pas les descriptions longues.
- **Questions vidéo asynchrones :** L'employeur peut pré-enregistrer **3 questions**. Le candidat y répond en vidéo à son rythme. Cela remplace l'entrevue téléphonique de pré-qualification, sauvant un temps précieux aux RH.

---

## 2.3 Expérience de recherche et matching (UX)

L'expérience de recherche est conçue pour **réduire la friction** et **maximiser la pertinence**.

### 2.3.1 Recherche instantanée et tolérante

Grâce à Meilisearch, la barre de recherche réagit à chaque frappe (*Keystroke dynamics*).

- **Tolérance aux fautes :** Si un utilisateur tape « Injenieur » ou « Manœuvre », le système comprend et corrige automatiquement pour afficher « Ingénieur » ou « Manœuvre », incluant les variantes de genre et les synonymes métier (ex. : « Dev » = « Développeur » = « Programmeur »).
- **Géolocalisation précise :** Utilisation des données géospatiales pour permettre une recherche par rayon réel (*« Emplois à 5 km de chez moi »*) ou par temps de transport, une fonctionnalité cruciale pour les travailleurs horaires qui veulent minimiser leur navettage.

### 2.3.2 Tableau de bord analytique pour PME

Pour les petites entreprises sans département RH :

- **Pipeline Kanban :** Une vue visuelle simple (Candidatures reçues → À contacter → En entrevue → Offre faite).
- **Gestion des délais de consentement :** Le tableau de bord affiche une alerte visuelle (rouge/orange) pour les profils dont le consentement de conservation des données arrive à échéance (ex. : *« Consentement expire dans 15 jours »*). L'employeur peut envoyer une demande de renouvellement en un clic via Proton Mail, assurant une conformité continue sans effort manuel.

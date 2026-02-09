# Document 1 : Architecture technologique et infrastructure

## 1.1 Infrastructure d'hébergement : PlanetHoster World

Le choix de l'infrastructure est la pierre angulaire de la souveraineté numérique. Contrairement aux solutions infonuagiques hyperscalaires (AWS, Azure, Google Cloud) dont la complexité de facturation et la localisation des données peuvent poser des défis de conformité, la solution **World** de PlanetHoster, basée à Laval et Montréal, offre un excellent rapport qualité-prix pour une plateforme en démarrage.

### 1.1.1 Caractéristiques du plan World

Le plan **World** est un hébergement mutualisé optimisé sur l'infrastructure N0C de PlanetHoster. Il se distingue par :

| Caractéristique | Détail | Avantage |
|-----------------|--------|----------|
| **Espace disque** | Illimité (SSD) | Pas de contrainte sur la croissance de la base de données et des fichiers statiques. |
| **Bande passante** | Illimitée | Aucun frais supplémentaire en cas de pic de trafic. |
| **Sites web** | Illimités | Possibilité de créer des sous-domaines ou environnements de staging. |
| **PHP / MySQL** | Versions récentes (PHP 8.x) | Compatibilité avec les frameworks modernes. |
| **SSL gratuit** | Let's Encrypt automatisé | HTTPS sans configuration manuelle. |
| **Localisation** | Montréal (Canada) | Souveraineté des données, conformité Loi 25 facilitée. |

### 1.1.2 Architecture applicative simplifiée

Sur le plan World, l'application fonctionne en mode **LAMP classique** (Linux, Apache/LiteSpeed, MySQL, PHP) sans conteneurisation Docker. Cette approche présente plusieurs avantages pour le lancement :

- **Simplicité de déploiement :** Upload FTP/SFTP direct des fichiers PHP, pas de build ni d'orchestration.
- **Maintenance réduite :** Les mises à jour de sécurité du serveur sont gérées par PlanetHoster.
- **Coût maîtrisé :** Le plan World est significativement moins cher qu'une solution HybridCloud ou VPS.

**Structure de fichiers recommandée :**

```
/public_html/
├── index.php          # Point d'entrée
├── app/               # Logique applicative (MVC)
├── assets/            # CSS, JS, images statiques
├── uploads/           # Fichiers temporaires (avant transfert B2)
└── .htaccess          # Réécriture d'URL, sécurité
```

**Évolution future :** Si la plateforme atteint un volume de trafic ou de données nécessitant plus de ressources, une migration vers **HybridCloud N0C** (avec Docker et services dédiés) reste une option naturelle au sein de l'écosystème PlanetHoster.

---

## 1.2 Base de données : MySQL / MariaDB

### Version utilisée (production)

| Élément | Valeur |
|---------|--------|
| **Version** | MySQL 10.6.21-MariaDB |
| **Type JSON natif** | OUI (5.7.8+) |
| **Compatibilité** | Le projet utilise TEXT pour les colonnes JSON (compatibilité MySQL 5.5+). |

> Vérifier la version : exécuter `php gestion/check-mysql-version.php` sur le serveur.

---

## 1.3 Moteur de recherche : SQL natif

Pour la phase de lancement, la recherche d'offres d'emploi repose sur les capacités natives de **MySQL/MariaDB**, sans moteur de recherche externe comme Meilisearch ou Elasticsearch.

### 1.3.1 Recherche FULLTEXT

MySQL offre un système d'indexation **FULLTEXT** performant pour les recherches textuelles :

```sql
-- Création de l'index FULLTEXT sur les colonnes pertinentes
ALTER TABLE offres_emploi 
ADD FULLTEXT INDEX idx_recherche (titre, description, competences);

-- Requête de recherche avec pertinence
SELECT *, MATCH(titre, description, competences) AGAINST('développeur PHP' IN NATURAL LANGUAGE MODE) AS score
FROM offres_emploi
WHERE MATCH(titre, description, competences) AGAINST('développeur PHP' IN NATURAL LANGUAGE MODE)
ORDER BY score DESC
LIMIT 20;
```

### 1.3.2 Limitations et contournements

| Limitation | Solution de contournement |
|------------|---------------------------|
| Pas de tolérance aux fautes de frappe | Suggestions côté client (JavaScript) ou table de synonymes. |
| Pas de recherche « as-you-type » instantanée | Recherche déclenchée après 300 ms de pause ou sur validation. |
| Performance sur très gros volumes | Index optimisés, pagination, mise en cache des requêtes fréquentes. |

**Évolution future :** Lorsque le volume d'offres et de candidats justifiera un investissement supplémentaire, l'intégration de **Meilisearch** (auto-hébergé sur HybridCloud ou en SaaS) permettra d'offrir une expérience de recherche instantanée et tolérante aux erreurs.

---

## 1.4 Pipeline vidéo : Enregistrement navigateur et Backblaze B2

La fonctionnalité distinctive de la plateforme est le **recrutement vidéo**. L'architecture choisie privilégie la simplicité et l'économie en utilisant l'enregistrement directement depuis le navigateur avec upload vers Backblaze B2.

### 1.4.1 Enregistrement vidéo côté client (MediaRecorder API)

Les navigateurs modernes intègrent l'API **MediaRecorder** qui permet de capturer la webcam et le microphone sans plugin ni logiciel tiers.

**Flux technique :**

1. **Capture :** L'utilisateur autorise l'accès à sa caméra/micro via `navigator.mediaDevices.getUserMedia()`.
2. **Enregistrement :** L'API `MediaRecorder` encode le flux en temps réel (format WebM/VP9 ou MP4/H.264 selon le navigateur).
3. **Prévisualisation :** L'utilisateur peut revoir sa vidéo avant de la soumettre.
4. **Upload direct vers B2 :** Le fichier est envoyé directement au bucket Backblaze B2 via une URL pré-signée.

**Exemple de code JavaScript :**

```javascript
// Initialisation de la capture
const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
const mediaRecorder = new MediaRecorder(stream, { mimeType: 'video/webm;codecs=vp9' });

const chunks = [];
mediaRecorder.ondataavailable = (e) => chunks.push(e.data);

mediaRecorder.onstop = async () => {
  const blob = new Blob(chunks, { type: 'video/webm' });
  // Upload vers B2 via URL pré-signée
  await uploadToB2(blob, presignedUrl);
};

// Démarrage (limite à 90 secondes)
mediaRecorder.start();
setTimeout(() => mediaRecorder.stop(), 90000);
```

### 1.4.2 Stockage sur Backblaze B2

**Backblaze B2** est utilisé comme stockage objet principal pour toutes les vidéos de la plateforme.

| Aspect | Détail |
|--------|--------|
| **Coût de stockage** | ~6 USD/To/mois (10× moins cher qu'AWS S3). |
| **Bande passante** | 1 Go gratuit/jour, puis ~0.01 USD/Go. |
| **API compatible S3** | Intégration facile avec les SDK existants. |
| **Localisation** | Datacenter US (possibilité EU), transfert chiffré. |

**Upload via URL pré-signée :**

Le serveur PHP génère une URL pré-signée (valide quelques minutes) permettant au navigateur d'uploader directement vers B2 sans passer par le serveur applicatif. Cela :

- Réduit la charge sur l'hébergement World.
- Évite les timeouts sur les gros fichiers.
- Simplifie l'architecture (pas de stockage temporaire côté serveur).

```php
// Génération de l'URL pré-signée côté serveur (PHP)
$b2 = new B2Client($accountId, $applicationKey);
$presignedUrl = $b2->getUploadUrl($bucketId, $fileName, $expireInSeconds);
// Retourner l'URL au JavaScript client
```

### 1.4.3 Diffusion des vidéos

Pour la lecture, les vidéos sont servies directement depuis B2 via **Cloudflare CDN** (gratuit) grâce à la **Bandwidth Alliance** :

- **Aucun frais de sortie** (egress) entre B2 et Cloudflare.
- **Cache global** : Les vidéos populaires sont mises en cache sur les serveurs edge de Cloudflare.
- **URLs signées** : Le serveur génère des URLs temporaires pour protéger l'accès aux vidéos privées.

---

## 1.5 Communications : Zepto (applicatif) et Proton Mail (bureautique)

L'architecture de messagerie distingue deux usages distincts avec des solutions adaptées à chacun.

### 1.5.1 Zepto — Emails transactionnels de l'application

**Zepto** (anciennement ZeptoMail, par Zoho) est le service SMTP utilisé pour tous les emails automatisés de la plateforme :

| Type d'email | Exemple |
|--------------|---------|
| **Transactionnel** | Confirmation d'inscription, réinitialisation de mot de passe. |
| **Notification** | Nouvelle candidature reçue, offre correspondant au profil. |
| **Relais candidat-recruteur** | Messages anonymisés entre les parties. |

**Avantages de Zepto :**

- **Délivrabilité élevée** : Infrastructure optimisée pour les emails transactionnels (pas de marketing).
- **Prix compétitif** : ~0.30 USD pour 1 000 emails (sans abonnement mensuel fixe).
- **API simple** : Intégration REST ou SMTP standard.
- **Logs et analytics** : Suivi des ouvertures, bounces, et plaintes.

**Configuration PHP :**

```php
// Envoi via l'API Zepto
$zepto = new ZeptoMailClient($apiKey);
$zepto->sendEmail([
    'from' => ['address' => 'noreply@plateforme.quebec', 'name' => 'CiaoCV'],
    'to' => [['email_address' => ['address' => $destinataire]]],
    'subject' => 'Nouvelle candidature reçue',
    'htmlbody' => $htmlContent
]);
```

### 1.5.2 Proton Mail — Bureautique interne

**Proton Mail** est réservé aux communications internes de l'équipe et aux échanges sensibles nécessitant un chiffrement de bout en bout :

- **Support client** : Réponses personnalisées aux demandes complexes.
- **Communications légales** : Échanges avec avocats, comptables, partenaires.
- **Administration** : Boîtes email de l'équipe (`contact@`, `support@`, `admin@`).

**Bénéfices :**

- **Chiffrement E2E** : Les emails entre utilisateurs Proton sont automatiquement chiffrés.
- **Hébergement suisse** : Protection juridique forte pour les données sensibles.
- **Pas de publicité** : Aucune analyse du contenu des emails.

Cette séparation garantit que les emails transactionnels (volume élevé, automatisés) ne risquent pas d'impacter la réputation ou les quotas des boîtes bureautiques, tout en offrant une sécurité maximale pour les communications confidentielles.

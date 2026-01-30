# Document 1 : Architecture technologique et infrastructure

## 1.1 Infrastructure d'hébergement : PlanetHoster HybridCloud N0C

Le choix de l'infrastructure est la pierre angulaire de la souveraineté numérique. Contrairement aux solutions infonuagiques hyperscalaires (AWS, Azure, Google Cloud) dont la complexité de facturation et la localisation des données peuvent poser des défis de conformité, la solution **HybridCloud N0C** de PlanetHoster, basée à Laval et Montréal, offre un compromis optimal entre performance dédiée et gestion simplifiée.

### 1.1.1 Architecture matérielle et isolation des ressources

La plateforme N0C de PlanetHoster se distingue par une architecture qui isole les ressources (CPU, RAM, I/O) pour chaque compte, évitant l'effet de « voisin bruyant » typique des hébergements partagés classiques. Pour une plateforme de recrutement visant à concurrencer Indeed, les charges de travail anticipées (indexation de milliers d'offres, gestion de trafic concurrent, appels API vidéo) nécessitent un dimensionnement robuste.

Selon les spécifications techniques actuelles, le déploiement doit s'orienter vers l'offre **HybridCloud Max** ou une configuration personnalisée supérieure. Les ressources critiques identifiées sont les suivantes :

| Ressource | Spécification recommandée | Justification technique et impact sur la performance |
|-----------|---------------------------|------------------------------------------------------|
| **Processeur (CPU)** | 12 à 32 cœurs dédiés | L'indexation des documents dans le moteur de recherche (Meilisearch) est une opération multi-threadée intensive. Chaque mise à jour de l'index (ajout d'offres) consomme environ 50 % des cœurs disponibles pour maintenir la réactivité de la recherche. |
| **Mémoire vive (RAM)** | 64 Go à 128 Go | La performance de la recherche dépend directement de la capacité à charger les fichiers d'index en mémoire (Memory Mapping). Une RAM insuffisante force le système à utiliser le swap ou à lire sur le disque, augmentant la latence au-delà du seuil critique de 50 ms. |
| **Stockage disque** | NVMe (200 Go à plusieurs To) | La technologie NVMe est impérative pour supporter les I/O élevés (jusqu'à 2048 MB/s sur N0C). Le stockage objet (fichiers lourds) sera délesté vers Backblaze, mais la base de données relationnelle et les index de recherche doivent résider sur le NVMe local pour une vélocité maximale. |
| **Localisation** | Montréal (Canada) | Garantie de souveraineté des données, facilitant la conformité à la Loi 25 sans nécessiter d'Évaluation des Facteurs Relatifs à la Vie Privée (EFVP) pour le transfert hors-Québec. |

L'interface de gestion N0C fournit des statistiques détaillées sur l'utilisation des partitions `/home` et `/root`. Une attention particulière doit être portée à la partition `/root`, souvent utilisée par défaut par le moteur Docker pour stocker les images et les conteneurs temporaires. Dans un environnement HybridCloud, cette partition peut être limitée. Il est donc **architecturalement nécessaire** de configurer le démon Docker pour utiliser un répertoire de données situé sur la partition `/home` (beaucoup plus vaste), afin d'éviter la saturation du système d'exploitation principal lors des mises à jour d'images ou de la génération de logs.

### 1.1.2 Stratégie de conteneurisation Docker sur N0C

Le déploiement des services (Application Web, API, Moteur de Recherche) via Docker assure la portabilité et la reproductibilité de l'environnement. Cependant, l'exécution de Docker sur une infrastructure gérée comme celle de PlanetHoster présente des défis spécifiques liés aux permissions et à la sécurité.

#### Le défi du « Rootless Mode »

Pour des raisons de sécurité, il est préférable, voire imposé, d'exécuter les conteneurs en mode **rootless** (sans privilèges administrateur complets) sur l'infrastructure HybridCloud. Cela mitige les risques en cas de compromission d'un conteneur, empêchant l'attaquant d'obtenir les droits root sur l'hôte.

#### Contraintes et résolutions techniques

**Binding des ports privilégiés**

- **Problème :** En mode rootless, Docker ne peut pas lier les ports inférieurs à 1024 (comme le port 80 pour HTTP ou 443 pour HTTPS) directement à l'interface réseau publique.
- **Solution architecturale :** Utilisation d'un **Proxy inverse (Reverse Proxy)**. Le serveur web LiteSpeed (natif à N0C) ou un serveur Nginx géré par PlanetHoster écoute sur les ports publics (80/443) et gère la terminaison SSL. Il redirige ensuite le trafic vers les conteneurs Docker écoutant sur des ports hauts (ex. : `127.0.0.1:3000` pour l'app, `127.0.0.1:7700` pour Meilisearch). Cette approche simplifie également la gestion des certificats SSL, qui sont pris en charge automatiquement par l'infrastructure N0C.

**Persistance et permissions des volumes**

- **Problème :** Les conteneurs Docker sont éphémères ; un redémarrage efface toutes les données non stockées sur un volume monté. De plus, le mappage des utilisateurs (User Namespaces) en mode rootless peut causer des erreurs de permission (*Permission denied*) lors de l'accès aux dossiers de l'hôte.
- **Solution :** Il est impératif de définir explicitement les permissions des dossiers de données sur l'hôte (`/home/user/data`) pour correspondre à l'UID (User ID) utilisé à l'intérieur du conteneur.

**Configuration type :**

```bash
# Création des dossiers persistants avec les bonnes permissions
mkdir -p /home/user/meili_data
# Lancement du conteneur avec montage de volume explicite
docker run -d \
  -p 7700:7700 \
  -v /home/user/meili_data:/meili_data \
  --restart always \
  getmeili/meilisearch:latest
```

Cette commande assure que les index de recherche survivent aux redémarrages et mises à jour de l'infrastructure.

---

## 1.2 Moteur de recherche : Meilisearch

Pour remplacer l'expérience utilisateur d'Indeed, la **vitesse de recherche** est le facteur critique de succès. Les utilisateurs de 2026, habitués aux standards des réseaux sociaux, n'acceptent plus les temps de chargement de page lors de l'application de filtres. Meilisearch a été sélectionné comme moteur de recherche principal pour sa capacité à fournir une expérience **« As-You-Type »** (instantanée) et sa tolérance native aux fautes de frappe, surpassant Elasticsearch sur ces métriques spécifiques pour des volumes de données moyens à grands.

### 1.2.1 Architecture interne et performance mémoire

Contrairement aux bases de données traditionnelles, Meilisearch utilise une architecture basée sur **LMDB** (Lightning Memory-Mapped Database). Cela signifie qu'il ne charge pas les données en RAM de manière traditionnelle, mais utilise le mappage mémoire du système d'exploitation pour accéder aux données sur le disque comme s'il s'agissait de mémoire vive.

- **Gestion de la RAM :** Meilisearch tente par défaut d'utiliser jusqu'à deux tiers de la RAM disponible pour le cache du système de fichiers. Sur une instance PlanetHoster de 64 Go, environ 42 Go seront implicitement dédiés au cache de recherche. Il est crucial de ne pas limiter artificiellement la mémoire du conteneur Docker (sauf si nécessaire pour la stabilité des autres services), car la performance de lecture chute drastiquement si les index ne tiennent plus dans le cache RAM.
- **Comportement multi-thread :** L'indexation (l'ajout ou la mise à jour d'offres d'emploi) est une tâche lourde en CPU. Meilisearch est configuré pour n'utiliser qu'environ la moitié des cœurs de processeur disponibles pour l'indexation, afin de laisser les autres cœurs disponibles pour répondre aux requêtes de recherche des utilisateurs. Sur un serveur HybridCloud à 24 cœurs, 12 seront dédiés à l'ingestion des données et 12 à la lecture, garantissant que la plateforme ne ralentit pas même lors de l'importation massive d'offres la nuit.

### 1.2.2 Configuration de sécurité en production

L'exposition d'un moteur de recherche sur Internet comporte des risques majeurs si elle n'est pas sécurisée. Meilisearch en mode développement permet un accès complet sans clé, ce qui est **inacceptable en production**.

- **Clé maître (Master Key) :** Il est impératif de définir la variable d'environnement `MEILI_MASTER_KEY` avec une chaîne aléatoire complexe d'au moins 16 octets lors du lancement du conteneur. Cette clé permet de générer deux clés API par défaut :
  - **Default Search Key :** À utiliser dans le front-end (public) pour permettre aux utilisateurs de chercher.
  - **Default Admin Key :** À conserver strictement sur le serveur (back-end) pour la gestion des index et des documents.
- **Ségrégation des données (Tenant Tokens) :** Pour une conformité maximale, notamment dans le cas où des recruteurs cherchent dans une banque de CVs, l'application ne doit pas utiliser la clé de recherche publique. Elle doit générer des **Tenant Tokens** signés qui intègrent des règles de filtrage forcées (ex. : `filter: "visibility = public"`). Cela empêche mathématiquement un utilisateur de contourner les filtres pour voir des profils privés.

---

## 1.3 Pipeline vidéo : Cloudflare Stream et Backblaze B2

La fonctionnalité distinctive de la plateforme est le **recrutement vidéo**. L'hébergement vidéo traditionnel est soit extrêmement coûteux (AWS S3 + CloudFront), soit techniquement complexe (gestion des codecs, transcodage). La combinaison de **Cloudflare Stream** et **Backblaze B2** offre une solution élégante et économiquement disruptive.

### 1.3.1 L'alliance de la bande passante (Bandwidth Alliance)

L'intégration technique repose sur la **Bandwidth Alliance**, un accord commercial et technique entre Cloudflare et Backblaze qui supprime les frais de sortie de données (*egress fees*) pour le trafic circulant entre les deux services.

**Architecture du flux vidéo :**

1. **Stockage des archives (Backblaze B2) :** Les fichiers vidéo bruts (*Master files*) téléchargés par les candidats ou les entreprises sont stockés dans des « Buckets » privés sur Backblaze B2. Ce stockage est utilisé pour la rétention à long terme et la conformité légale (obligation de conserver les données de recrutement pendant une certaine période). Le coût est d'environ **6 USD/To/mois**, ce qui est nettement inférieur aux solutions concurrentes.
2. **Diffusion et transcodage (Cloudflare Stream) :** Lorsqu'une vidéo doit être rendue publique ou visionnée, elle n'est pas servie directement depuis B2. Elle est ingérée par Cloudflare Stream. Stream gère automatiquement le transcodage en plusieurs résolutions et débits (**Adaptive Bitrate Streaming — ABR**), garantissant que la vidéo est lisible aussi bien sur un cellulaire en 4G dans le métro de Montréal que sur une connexion fibre optique au bureau.

**Avantage concurrentiel :** Cette architecture permet de dissocier le coût de stockage (B2) du coût de diffusion (Cloudflare). Contrairement à une solution monolithique, nous ne payons le transcodage et la diffusion via Stream que pour les vidéos actives, tandis que les archives dormantes restent à très bas coût sur B2. De plus, l'absence de frais de transfert entre B2 et Cloudflare élimine la « taxe de succès » (où plus la plateforme est populaire, plus la facture de bande passante explose de manière exponentielle).

### 1.3.2 Sécurité des contenus (Signed URLs)

La protection des CVs vidéo est critique. Une vidéo ne doit pas être téléchargeable ou partageable hors contexte.

- **Implémentation :** L'API de Cloudflare Stream permet de générer des **tokens signés** (JSON Web Tokens). Le lecteur vidéo sur la plateforme ne reçoit qu'une URL temporaire (ex. : valide pour 1 heure).
- **Restriction de domaine :** Les vidéos sont configurées pour ne se lancer que si l'en-tête `Referer` de la requête HTTP correspond au domaine de la plateforme (`jobs.quebec`). Cela empêche l'intégration des vidéos sur des sites tiers non autorisés.

---

## 1.4 Communications sécurisées : Proton Mail

La confidentialité des échanges entre candidats et recruteurs est un point de friction majeur sur les plateformes actuelles. L'utilisation de **Proton Mail** vise à garantir un niveau de chiffrement supérieur, aligné avec les attentes de sécurité de 2026.

### 1.4.1 Architecture de relais SMTP (Bridge)

Proton Mail est conçu pour le chiffrement de bout en bout (PGP), ce qui rend son utilisation via une API standard complexe. L'intégration technique passe par le **Proton Mail Bridge** ou une configuration de relais SMTP sécurisé.

- **Fonctionnement :** La plateforme agit comme un intermédiaire (« Relais »). Le candidat envoie un courriel à une adresse alias (ex. : `candidat-ref123@plateforme.quebec`). Le serveur de la plateforme reçoit ce message, effectue un assainissement (suppression des métadonnées de traçage), et le retransmet vers la boîte de réception sécurisée du recruteur.
- **Bénéfice technique :** Ce système masque les adresses courriel réelles des deux parties (**Anonymisation par défaut**), réduisant le risque de harcèlement ou de constitution de bases de données de spam par des recruteurs peu scrupuleux.

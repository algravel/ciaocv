# Rapport d'Audit : Sécurité & Performance

**Date** : 13 Février 2026
**Projet** : CiaoCV
**Statut** : Analyse Initiale
Gimini

## 1. Synthèse Globale

Le système repose sur une architecture MVC (Modèle-Vue-Contrôleur) native en PHP, structurée de manière claire. L'application respecte plusieurs bonnes pratiques de sécurité (structure des dossiers, protection CSRF), mais présente des **problèmes de performance structurels** qui ralentiront l'application à mesure que le volume de données augmentera.

---

## 2. Audit de Sécurité

### ✅ Points Forts
1.  **Isolation du Code** : Le dossier `app/` est situé en dehors de `public_html/`, empêchant l'accès direct aux fichiers système.
2.  **Protection CSRF** : Mécanisme de jetons (`csrf_verify()`, `csrf_field()`) utilisé systématiquement sur les actions POST.
3.  **Échappement XSS** : Fonction `e()` (`htmlspecialchars`) présente et utilisée dans les vues.
4.  **Contrôle d'accès (RBAC)** : Ségrégation des rôles (Admin, Client, Évaluateur) gérée dans les contrôleurs (ex: `requireNotEvaluateur()`).

### ⚠️ Vulnérabilités & Risques Identifiés

#### A. Injection XSS via JSON (Risque Moyen)
Dans `app/views/layouts/app.php`, des données PHP sont injectées directement dans une balise `<script>` :
```php
currentUser: <?= json_encode(($user ?? [])['name'] ?? 'Utilisateur', JSON_UNESCAPED_UNICODE) ?>,
```
**Risque** : Si un nom d'utilisateur contient `</script><script>...`, cela peut casser le contexte JS et exécuter du code malveillant.
**Solution** : Utiliser les flags `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`.

#### B. Risque SQL (Contexte CRON/Admin)
Certaines commandes d'administration ou scripts CRON utilisent `exec()` ou `query()` avec concaténation de variables.
*Exemple (`gestion/cron_retention.php`)* : `$pdo->exec("DELETE ... IN ($ids)")`.
**Solution** : S'assurer que `$ids` est validé strictement (tableau d'entiers) avant injection.

#### C. Validation des Uploads
Vérifier systématiquement les types MIME et extensions pour tous les uploads de fichiers (CVs, Vidéos) pour éviter l'exécution de scripts PHP malveillants masqués.

---

## 3. Audit de Performance

### ⚠️ Problèmes Critiques

#### A. Problème de requête N+1 (Majeur)
Dans `DashboardController::index`, le chargement des membres de l'équipe effectue une requête SQL par membre :
```php
foreach ($memberIds as $id) {
    $pu = $platformUserModel->findById($id); // 1 requête par itération
}
```
**Impact** : Pour une équipe de 50 membres, 51 requêtes sont exécutées inutilement.

#### B. Absence de Pagination (Scalabilité)
Le tableau de bord charge **toutes** les données via `getAll()` :
```php
$candidats = Candidat::getAll($effectiveOwnerId);
$affichages = Affichage::getAll($effectiveOwnerId);
```
**Impact** : Avec l'augmentation du nombre de candidats et d'affichages, la consommation mémoire et le temps de réponse augmenteront linéairement, risquant le crash (Timeout/OOM).

#### C. Chargement des Assets
L'injection de gros volumes de données JSON (`APP_DATA`) directement dans le HTML ralentit le temps de chargement initial (TTFB/FCP).
**Solution** : Charger les données volumineuses via des appels API asynchrones (AJAX) uniquement lorsque nécessaire.

---

## 4. Recommandations Prioritaires

### 🔴 Critique (À corriger immédiatement)
1.  **Optimisation SQL (N+1)** : Implémenter une méthode `findByIds(array $ids)` dans `PlatformUser` pour récupérer les membres en une seule requête `WHERE id IN (...)`.
2.  **Sécurisation JSON** : Mettre à jour toutes les injections `json_encode` dans les vues avec les flags de sécurité hexadécimaux.

### 🟠 Important (À planifier)
3.  **Pagination** : Mettre en place la pagination pour les listes de candidats et d'affichages dans le Dashboard et les vues dédiées.
4.  **Revue CRON** : Auditer et sécuriser les variables dans les scripts de maintenance (`gestion/cron_retention.php`).

### 🟢 Amélioration Continue
5.  **Refactoring Assets** : Déplacer le chargement des données non critiques vers des endpoints API dédiés.

# Améliorations proposées pour /app (CiaoCV)

Analyse du code dans `/app` et recommandations concrètes.

---

## 1. Architecture & structure

### 1.1 Route /rec en dehors du Router
**Constat :** La route `/rec/{longid}` est gérée manuellement dans `index.php` (lignes 40–47), alors que les autres routes passent par le `Router`.

**Recommandation :** Intégrer la route dans le Router pour un point d’entrée unique et une configuration cohérente.

- **Option A – Route paramétrée dans le Router :**  
  Ajouter une méthode `getPattern(string $pattern, string $controller, string $method)` qui fait un `preg_match` sur l’URI et passe les groupes capturés en arguments à l’action (ex. `show($longId)`).

- **Option B – Conserver le bloc actuel :**  
  Déplacer au minimum la logique dans une méthode dédiée du Router, ex. `Router::matchRecUri($uri): ?array` qui retourne `['controller' => 'RecController', 'method' => 'show', 'args' => [$longId]]` ou `null`, et appeler cette méthode depuis `index.php` avant `$router->dispatch()`.

**Bénéfice :** Toutes les routes au même endroit, plus simple à faire évoluer (middlewares, logs, etc.).

---

### 1.2 Chargement des contrôleurs
**Constat :** `index.php` ne charge pas les contrôleurs ; chaque route fait un `require_once` dans `Router::callAction()`. La route `/rec` fait un `require_once RecController` manuel.

**Recommandation :**  
- Soit charger tous les contrôleurs une fois dans `index.php` (comme les modèles).  
- Soit laisser le Router charger à la demande mais supprimer le `require_once` spécifique pour `/rec` une fois la route intégrée au Router.  
Objectif : un seul mécanisme de chargement.

---

### 1.3 Fichier api/search.php obsolète
**Constat :** `app/api/search.php` fait un `require_once '../db.php'` alors que `db.php` n’existe pas dans le projet. Ce script n’est pas relié au MVC (pas de config, pas de Router).

**Recommandation :**
- Si l’API n’est pas utilisée : **supprimer** `app/api/search.php` (ou le déplacer dans `_documentation/` / archive) pour éviter des 500 en cas d’appel.
- Si vous prévoyez une API recherche : créer un contrôleur dédié (ex. `ApiController::search()`), une route (ex. `GET /api/search`) et utiliser la même config que le reste de l’app (config, éventuelle couche DB future).

---

## 2. Sécurité

### 2.1 Authentification désactivée
**Constat :** Dans `DashboardController::index()`, l’appel à `$this->requireAuth()` est commenté. Le tableau de bord est donc accessible sans connexion.

**Recommandation :** Réactiver `$this->requireAuth();` dès que la couche d’authentification réelle (DB, vérification mot de passe) est en place. En attendant, documenter clairement en en-tête du contrôleur que l’auth est désactivée (mock).

---

### 2.2 Connexion mock sans vérification du mot de passe
**Constat :** `AuthController::authenticate()` accepte n’importe quel email (non vide) et crée une session sans vérifier le mot de passe.

**Recommandation :**
- Garder le comportement actuel uniquement en environnement démo/dev (ex. `APP_ENV=development`).
- En production : exiger une vérification réelle (hash du mot de passe, DB) avant de créer la session ; sinon rediriger avec un message d’erreur et ne jamais faire `$_SESSION['user_id'] = ...` sans auth valide.

---

### 2.3 Redirection après login (open redirect)
**Constat :** `Controller::redirect($url)` et `AuthController` utilisent des URLs en dur (`/dashboard`, `/login`). Pas de risque actuel, mais une future utilisation de `$_GET['redirect']` pourrait introduire une open redirect.

**Recommandation :** Si vous ajoutez un paramètre de redirection, valider que l’URL est relative ou fait partie d’une liste blanche de domaines/hosts (pas de redirection vers un domaine arbitraire).

---

### 2.4 Valeurs .env entre guillemets
**Constat :** Dans `config/app.php`, les valeurs du `.env` sont utilisées après `trim()` uniquement. Les guillemets autour des valeurs (ex. `FTP_PASS="!VV]72^w;s"`) restent dans la valeur.

**Recommandation :** Après `trim($value)`, retirer les guillemets simples ou doubles en début et fin si présents, par exemple :

```php
if ((strlen($value) >= 2) && (($value[0] === '"' && $value[strlen($value)-1] === '"') || ($value[0] === "'" && $value[strlen($value)-1] === "'"))) {
    $value = substr($value, 1, -1);
}
$_ENV[$key] = $value;
```

Cela évite des échecs de connexion FTP (ou autres) à cause de guillemets inclus dans le mot de passe.

---

## 3. Maintenabilité & duplication

### 3.1 Données mock dupliquées
**Constat :** Les infos du poste « Développeur Frontend » (titre, département, lieu, questions, durée) sont définies à la fois dans `Poste::getAll()`, `Affichage::getAll()` et en dur dans `RecController::show()`.

**Recommandation :**
- Introduire une source unique pour un « poste » (ex. `Poste::getByLongId(string $longId)` ou un mapping `longId → poste_id` côté affichage).
- `RecController::show($longId)` doit récupérer le poste (et l’affichage) via ce mécanisme, pas un tableau en dur. Les modèles restent en mock tant qu’il n’y a pas de DB, mais la logique d’accès est centralisée.

---

### 3.2 Départements en dur
**Constat :** La liste des départements est en tableau PHP dans `DashboardController` :  
`['Technologie', 'Gestion', 'Design', ...]`.

**Recommandation :** Déplacer cette liste dans la config (ex. `config/app.php`) ou dans un modèle dédié (ex. `Departement::getAll()`). Réutiliser la même liste partout (formulaires, filtres, affichage) pour éviter les incohérences.

---

### 3.3 Team members mock dans le contrôleur
**Constat :** `$teamMembers` est défini directement dans `DashboardController::index()`.

**Recommandation :** Créer un modèle (ex. `User` ou `TeamMember`) avec une méthode `getAll()` ou `getForCompany()`, et l’appeler depuis le contrôleur. Même avec des données mock, la séparation responsabilités (contrôleur / données) sera plus claire.

---

## 4. Qualité du code

### 4.1 Controller : extract() dans view()
**Constat :** `Controller::view()` utilise `extract($data)` pour injecter les variables en vue. C’est pratique mais rend les dépendances de la vue peu explicites et peut écraser des variables existantes.

**Recommandation (optionnelle) :** Passer un objet ou un tableau unique, ex. `$data` ou `$viewData`, et dans la vue utiliser `$viewData['poste']`, `$viewData['longId']`, etc. Sinon, au minimum documenter en en-tête de chaque vue les variables attendues (comme déjà fait partiellement avec les `@var` en haut de `rec/index.php`).

---

### 4.2 Typage et documentation
**Constat :** Les modèles ont de bons `@return` sur les méthodes principales. Les contrôleurs ont peu de types sur les paramètres (sauf `RecController::show(string $longId)`).

**Recommandation :**  
- Typer les arguments et retours partout où c’est possible (PHP 8+).  
- Ajouter des `@param` / `@return` sur les méthodes publiques des contrôleurs pour faciliter l’IDE et les refactorings.

---

### 4.3 Gestion d’erreurs du Router
**Constat :** En cas de contrôleur ou méthode introuvable, le Router lance une `RuntimeException` qui est attrapée par le `set_exception_handler` global. C’est cohérent.

**Recommandation :** Pour les 404, vous utilisez déjà une vue dédiée. Vérifier que les exceptions « route non trouvée » ne sont pas confondues avec de vraies erreurs 500 (par ex. en utilisant une classe dédiée `NotFoundException` ou en retournant explicitement 404 avant d’inclure la vue 404, comme c’est le cas actuellement).

---

## 5. Vue & front

### 5.1 Inline styles et CSS dans rec/index.php
**Constat :** La page `views/rec/index.php` contient un grand bloc `<style>` et quelques styles inline.

**Recommandation :**
- Déplacer les styles dans un fichier dédié (ex. `assets/css/rec.css`) et le charger uniquement pour le layout `rec` (comme pour `app.css` sur le dashboard).  
- Réduire les styles inline au strict nécessaire (ou les remplacer par des classes).  
Cela améliore la réutilisabilité et la maintenabilité du CSS.

---

### 5.2 Constantes d’URL en dur
**Constat :** Dans les vues, on trouve par endroits des URLs en dur (ex. `https://www.ciaocv.com/guide-candidat.html`). La constante `SITE_URL` existe déjà.

**Recommandation :** Utiliser `SITE_URL` pour le domaine et composer les chemins : ex. `SITE_URL . '/guide-candidat.html'`. Si vous ajoutez une constante `APP_URL`, l’utiliser pour les liens vers l’app (ex. lien de partage rec).

---

## 6. Performance & déploiement

### 6.1 Chargement du .env
**Constat :** Le fichier `.env` est relu et réanalysé à chaque requête.

**Recommandation :** Acceptable pour l’instant. Si le nombre de variables augmente beaucoup, envisager un cache simple (ex. mettre les variables dans une constante ou un tableau en cache après premier chargement). Pas prioritaire tant que le fichier reste petit.

---

### 6.2 Session
**Constat :** `session_start()` est appelé dans `config/app.php` pour toutes les requêtes, y compris la page rec (candidat) qui n’a peut‑être pas besoin de session.

**Recommandation (optionnelle) :** Pour la route `/rec/*`, ne pas démarrer la session si vous n’utilisez ni session ni cookie côté candidat. Par exemple, démarrer la session uniquement après le premier accès à `$_SESSION` ou pour certaines routes (ex. tout sauf `/rec`). Cela évite d’envoyer un cookie de session aux candidats qui n’en ont pas besoin.

---

## 7. Résumé des actions prioritaires

| Priorité | Action |
|----------|--------|
| Haute    | Corriger le chargement des valeurs `.env` (guillemets) pour éviter les bugs (FTP, etc.). |
| Haute    | Supprimer ou réécrire `api/search.php` (référence à `db.php` inexistant). |
| Haute    | Réactiver `requireAuth()` sur le dashboard dès que l’auth réelle est en place. |
| Moyenne  | Intégrer la route `/rec/{longid}` dans le Router (méthode paramétrée ou `matchRecUri`). |
| Moyenne  | Centraliser les données poste/affichage (une source pour RecController et les modèles). |
| Moyenne  | Déplacer les départements (et éventuellement team members) en config ou modèle. |
| Basse    | Extraire le CSS de la page rec dans `assets/css/rec.css`. |
| Basse    | Utiliser `SITE_URL` (et `APP_URL`) pour toutes les URLs absolues dans les vues. |

---

*Document généré à partir de l’analyse du code dans `/app`. À adapter selon la roadmap (DB, auth réelle, API).*

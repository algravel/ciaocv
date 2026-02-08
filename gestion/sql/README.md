# SQL et Seed — Module Gestion

## Journalisation (audit)

**Chaque opération Create, Update ou Delete** doit être journalisée dans `gestion_events` et affichée dans le dashboard (section « Journalisation des événements » du Tableau de bord).

- Table : `gestion_events` (admin_id, action_type, entity_type, entity_id, details_encrypted)
- Types d’action : `creation`, `modification`, `suppression`, `sale`
- Le modèle `Event` fournit `log()` pour enregistrer un événement.
- Les événements récents sont chargés dans le dashboard et affichés dans la section statistiques.

## Mise en place

1. **Exécuter le schéma** (création des tables) :
   ```bash
   mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASS $MYSQL_DB < gestion/sql/schema.sql
   ```

2. **Exécuter le seed** (données de test + admin par défaut) :
   ```bash
   php gestion/sql/seed.php
   ```

3. **Synchroniser les forfaits** avec [www.ciaocv.com/tarifs](https://www.ciaocv.com/tarifs) (remplace tous les forfaits existants) :
   ```bash
   php gestion/sql/sync-forfaits.php
   ```

## Admin par défaut

Après le seed : `admin@ciaocv.com` / `AdminDemo2026!`

## Clé de chiffrement

Le module utilise `GESTION_ENCRYPTION_KEY` ou `APP_ENCRYPTION_KEY` depuis `.env`. Pour une clé dédiée :

```bash
openssl rand -base64 32
```

Ajouter dans `.env` : `GESTION_ENCRYPTION_KEY="..."`

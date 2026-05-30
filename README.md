# SynoManager

Console web centralisée pour superviser et inventorier des NAS Synology.  
Permet à des agents Docker (déployés sur chaque NAS) d'envoyer leurs données vers un serveur central, de décoder et afficher les informations système, stockage, packages, etc.

---

## Stack technique

| Composant | Version |
|-----------|---------|
| PHP | 8.2+ |
| Laravel | 11 |
| Base de données | MySQL / MariaDB |
| Frontend | Blade + Bootstrap 5 + Alpine.js |
| Build | Vite |

---

## Prérequis

- PHP 8.2+ avec extensions : `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`
- Composer 2
- Node.js 18+ et npm
- MySQL 8+ ou MariaDB 10.6+
- Serveur web (Apache / Nginx) — ou WAMP/Laragon en local

---

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/VOTRE-COMPTE/SynoManager.git
cd SynoManager
```

### 2. Installer les dépendances PHP

```bash
composer install --no-dev --optimize-autoloader
```

> Les assets front-end (`public/build/`) sont **inclus dans le dépôt** — aucune installation Node.js requise sur le serveur.

### 3. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Éditer `.env` avec les valeurs adaptées à votre environnement :

```env
APP_NAME=SynoManager
APP_ENV=production          # local en développement
APP_DEBUG=false             # true en développement
APP_URL=https://votre-domaine.fr

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=synomanager
DB_USERNAME=votre_user
DB_PASSWORD=votre_mdp

SYNOLOGY_TIMEOUT=30
SYNOLOGY_SSL_VERIFY=true    # false si certificat auto-signé sur le NAS
```

### 4. Créer la base de données

```sql
CREATE DATABASE synomanager
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### 5. Lancer les migrations

```bash
php artisan migrate
```

### 6. Lien de stockage (si besoin)

```bash
php artisan storage:link
```

---

## Première connexion

1. Ouvrir `APP_URL/register`
2. Créer votre compte — **le premier utilisateur enregistré est automatiquement administrateur**
3. Désactiver l'inscription libre dans `.env` pour que les nouveaux comptes passent par invitation :
   ```env
   REGISTRATION_ENABLED=false
   ```
4. Inviter les utilisateurs suivants depuis **Administration → Utilisateurs → Inviter par email**

---

## Configuration post-installation

Ces éléments ne sont pas dans le dépôt et doivent être reconfigurés après chaque installation :

| Élément | Où le recréer |
|---------|---------------|
| Modèles API + Décodeurs | **Import / Export** → importer le fichier `.json` d'export |
| Configuration SMTP | **Paramètres → Configuration SMTP** (stocké en base de données) |
| NAS et snapshots | Se ré-enregistrent automatiquement dès que les agents ou la Test Console envoient des données |
| Utilisateurs supplémentaires | **Administration → Utilisateurs** |

---

## Fonctionnalités

- **Supervision NAS** — liste, statut, approbation des NAS en attente
- **Snapshots** — historique des collectes, visualisation JSON brut
- **Modèles API** — configuration des endpoints Synology DSM à collecter
- **Décodeurs JSON** — configuration de l'affichage des données (blocs, éléments, colonnes, sous-colonnes) avec transformateurs
- **Transformateurs disponibles** : `bytes`, `megabytes`, `date`, `timestamp`, `duration`, `uptime`, `boolean`, `badge_map`, `color_if`
- **Import / Export** — sauvegarde et restauration des modèles API et décodeurs
- **Test Console** — collecte manuelle depuis un NAS Synology (proxy serveur-à-serveur)
- **Gestion des utilisateurs** — rôles `admin` / `user`, protection du dernier admin
- **Configuration SMTP** — stockée en base, remplace `.env` au démarrage

---

## Structure des migrations

Les migrations s'appliquent dans l'ordre suivant :

| # | Table | Description |
|---|-------|-------------|
| 1 | `api_models` | Modèles de collecte API |
| 2 | `api_model_entries` | Entrées API par modèle |
| 3 | `json_decoder_models` | Modèles de décodage |
| 4 | `display_blocks` | Blocs d'affichage |
| 5 | `display_elements` | Éléments (valeur / boucle) |
| 6 | `display_columns` | Colonnes de boucle |
| 7 | `display_sub_columns` | Sous-colonnes |
| 8 | `nas_devices` | NAS enregistrés |
| 9 | `nas_api_available` | APIs disponibles par NAS |
| 10 | `nas_snapshots` | Snapshots de collecte |
| … | Migrations additionnelles | Évolutions de schéma |

---

## Mise à jour en production

```bash
# 1. Récupérer les dernières modifications
git pull origin main

# 2. Mettre à jour les dépendances PHP si nécessaire
composer install --no-dev --optimize-autoloader

# 3. Appliquer les nouvelles migrations
php artisan migrate --force

# 4. Vider les caches
php artisan config:clear
php artisan cache:clear
```

> **Remarque :** les assets front-end (`public/build/`) sont versionnés dans le dépôt, aucun `npm run build` n'est nécessaire sur le serveur.

---

## Développement local (WAMP)

```env
APP_URL=http://localhost/SynoManager/public
APP_DEBUG=true
APP_ENV=local
DB_PASSWORD=          # vide par défaut sous WAMP
SYNOLOGY_SSL_VERIFY=false
```

```bash
# Uniquement si vous modifiez le CSS/JS :
npm install && npm run dev    # Vite en mode watch
npm run build                 # Puis rebuilder avant de commiter
```

---

## Sécurité

- Les credentials DSM Synology ne sont **jamais stockés** (utilisés uniquement en mémoire PHP le temps de la collecte)
- La configuration SMTP (mot de passe inclus) est stockée en base de données, **pas dans le dépôt**
- Toutes les routes sont protégées par `auth` ; les routes d'administration par le middleware `admin`
- L'API d'ingestion agent (`POST /api/v1/agent/ingest`) dispose d'un middleware de signature HMAC (à compléter)

---

## Documentation agent

La spec complète du contrat API entre le serveur et l'agent Docker est dans [`docs/agent-api.md`](docs/agent-api.md).  
Ce fichier est la référence pour développer le repo [`SynoManager-Agent`](https://github.com/VOTRE-COMPTE/SynoManager-Agent).

---

## Licence

Usage privé.

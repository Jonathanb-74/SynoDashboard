# SynoManager — Contrat API Agent

Ce document est la référence pour développer un agent compatible SynoManager.  
Il décrit ce que le serveur expose, ce qu'il attend, ce qu'il retourne et le cycle de vie d'un NAS.

---

## Vue d'ensemble

```
[Agent Docker sur NAS]  ──POST──►  [SynoManager Server]
                                         │
                                    IngestionService
                                         │
                              ┌──────────┴──────────┐
                         nas_devices           nas_snapshots
                         (upsert)              (insert)
```

L'agent collecte les données du NAS via l'API DSM locale, puis les envoie au serveur central en une seule requête. Le serveur gère tout : création du NAS, historisation des snapshots, décodage des données pour l'affichage.

---

## Endpoint d'ingestion

```
POST /api/v1/agent/ingest
Content-Type: application/json
X-Agent-Signature: <hmac-sha256>   ← voir section Authentification
```

**URL complète :** `https://votre-serveur/api/v1/agent/ingest`

---

## Authentification

L'authentification repose sur **HMAC-SHA256**, avec une clé secrète **par NAS**.

### Principe

L'agent signe le corps brut de la requête (payload JSON complet, sans modification) avec sa clé secrète et envoie la signature dans le header `X-Agent-Signature`. Le serveur recompute la signature côté serveur et compare avec `hash_equals` (résistant aux timing attacks).

```
signature = HMAC-SHA256(raw_body, hmac_secret)
header    = "sha256=" + hex(signature)
```

### Cycle de vie de la clé

| Phase | Comportement serveur |
|-------|----------------------|
| **Premier envoi** (NAS inconnu) | Signature ignorée — NAS créé en `pending` |
| **NAS en attente sans clé** | Signature ignorée — en attente d'approbation admin |
| **Après approbation** | Clé générée automatiquement — **signature obligatoire dès lors** |
| **Clé absente ou invalide** | `401 Unauthorized` + `{"status":"error","message":"Invalid signature."}` |

### Récupérer la clé

La clé est générée lors de l'approbation du NAS dans l'interface SynoManager (**NAS → fiche NAS → Authentification HMAC**). Elle peut être régénérée à tout moment depuis cette même interface (l'ancienne est immédiatement invalidée).

### Exemple de signature (Python)

```python
import hmac, hashlib, json, requests

secret  = "votre-clé-hmac"
payload = json.dumps({...}, separators=(',', ':'))

sig = hmac.new(secret.encode(), payload.encode(), hashlib.sha256).hexdigest()

requests.post(
    "https://votre-serveur/api/v1/agent/ingest",
    data=payload,
    headers={
        "Content-Type":       "application/json",
        "X-Agent-Signature":  f"sha256={sig}",
    }
)
```

### Exemple de signature (PHP)

```php
$secret  = 'votre-clé-hmac';
$body    = json_encode($payload);
$sig     = hash_hmac('sha256', $body, $secret);

$client->post('/api/v1/agent/ingest', [
    'body'    => $body,
    'headers' => [
        'Content-Type'      => 'application/json',
        'X-Agent-Signature' => "sha256={$sig}",
    ],
]);
```

---

## Corps de la requête (payload)

```json
{
  "agent_version": "1.0.0",
  "collected_at":  "2024-01-15T10:30:00Z",

  "nas_identifier": {
    "serial":      "1920PDN123456",
    "model":       "DS923+",
    "server_name": "NAS-Bureau",
    "dsm_version": "DSM 7.2.2-72806"
  },

  "api_list": {
    "SYNO.API.Info":           { "path": "query.cgi",  "minVersion": 1, "maxVersion": 1  },
    "SYNO.API.Auth":           { "path": "auth.cgi",   "minVersion": 1, "maxVersion": 7  },
    "SYNO.Core.System":        { "path": "entry.cgi",  "minVersion": 1, "maxVersion": 7  },
    "SYNO.Core.Network":       { "path": "entry.cgi",  "minVersion": 1, "maxVersion": 2  },
    "SYNO.Storage.CGI.Storage":{ "path": "entry.cgi",  "minVersion": 1, "maxVersion": 1  },
    "SYNO.Core.Package":       { "path": "entry.cgi",  "minVersion": 1, "maxVersion": 2  },
    "SYNO.Core.Upgrade":       { "path": "entry.cgi",  "minVersion": 1, "maxVersion": 1  }
  },

  "responses": {
    "SYNO.Core.System": {
      "success": true,
      "data": {
        "cpu_vendor":      "Intel",
        "cpu_family":      "Intel(R) Core(TM) i3-8100",
        "ram_size":        20480,
        "serial":          "1920PDN123456",
        "model":           "DS923+",
        "sys_tempwarn":    false,
        "uptime":          "42:17:05",
        "firmware_ver":    "7.2.2-72806"
      }
    },
    "SYNO.Core.Network": {
      "success": true,
      "data": {
        "dns":        "192.168.1.1",
        "gateway":    "192.168.1.1",
        "interfaces": [...]
      }
    },
    "SYNO.Storage.CGI.Storage": {
      "success": true,
      "data": { ... }
    },
    "SYNO.Core.Package": {
      "success": true,
      "packages": [ ... ]
    },
    "SYNO.Core.Upgrade": {
      "success": true,
      "data": { ... }
    }
  }
}
```

---

## Description des champs

### Racine

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `agent_version` | string | Non | Version de l'agent (ex: `"1.0.0"`, `"docker-1.2.3"`) — stockée avec le snapshot |
| `collected_at` | string ISO 8601 | Non | Horodatage de la collecte. Si absent : heure de réception serveur |
| `nas_identifier` | object | **Oui** | Identité du NAS (voir ci-dessous) |
| `api_list` | object | Non | Carte de toutes les APIs disponibles sur ce NAS |
| `responses` | object | Non | Réponses des APIs collectées |

### `nas_identifier`

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `serial` | string | **Oui** | Numéro de série — clé d'identification unique du NAS |
| `model` | string | **Oui** | Modèle (ex: `"DS923+"`) |
| `server_name` | string | **Oui** | Nom configuré dans DSM |
| `dsm_version` | string | **Oui** | Version DSM complète (ex: `"DSM 7.2.2-72806"`) |

#### Source des champs dans les APIs DSM

L'agent doit appeler **deux APIs** pour construire `nas_identifier` (les deux nécessitent une authentification) :

| Champ `nas_identifier` | API DSM | Chemin dans la réponse |
|------------------------|---------|------------------------|
| `serial` | `SYNO.Core.System` method `info` | `data.serial` |
| `model` | `SYNO.Core.System` method `info` | `data.model` |
| `dsm_version` | `SYNO.Core.System` method `info` | `"DSM " + data.firmware_ver` |
| `server_name` | `SYNO.Core.Network` method `get` | `data.server_name` |

Exemple de réponse `SYNO.Core.Network` (champ utile) :

```json
{
  "success": true,
  "data": {
    "server_name": "nas-brea",
    "dns_primary": "192.168.1.1",
    "gateway": "192.168.1.1",
    "gateway_info": { "ifname": "ovs_eth0", "ip": "192.168.1.250", ... },
    "..."
  }
}
```

> Ces deux APIs doivent être appelées lors du **premier cycle** pour initialiser `nas_identifier`. Les valeurs peuvent être mises en cache en mémoire pour les cycles suivants (elles ne changent pas souvent). Si l'une des deux est indisponible sur le NAS, utiliser une valeur de fallback explicite (ex: `"unknown"`) plutôt que de bloquer l'enrôlement.

### `api_list`

Dictionnaire `{ nom_api: { path, minVersion, maxVersion } }`.  
Contient **toutes** les APIs disponibles sur ce NAS (sortie de `SYNO.API.Info`).  
Utilisé par le serveur pour créer/matcher automatiquement un modèle API.

| Sous-champ | Type | Description |
|------------|------|-------------|
| `path` | string | Chemin relatif de l'endpoint (ex: `"entry.cgi"`) |
| `minVersion` | integer | Version minimale supportée |
| `maxVersion` | integer | Version maximale supportée |

### `responses`

Dictionnaire `{ nom_api: réponse_brute }`.  
Ne contient que les **APIs effectivement appelées** (les APIs indisponibles sont simplement absentes).  
Le contenu de chaque réponse est la réponse DSM telle quelle — le serveur la stocke sans la modifier.

---

## Modèle de collecte

**L'agent ne décide pas ce qu'il collecte.** Le serveur est l'unique source de vérité sur les APIs à appeler.

### Premier contact (enrôlement)

L'agent envoie uniquement l'identification et la liste des APIs disponibles. Le champ `responses` doit être absent ou vide — le NAS n'est pas encore approuvé, ses données ne sont pas attendues.

```json
{
  "agent_version": "1.0.0",
  "collected_at":  "2024-01-15T10:30:00Z",
  "nas_identifier": { "serial": "...", "model": "...", "server_name": "...", "dsm_version": "..." },
  "api_list": { "SYNO.Core.System": {...}, "SYNO.Core.Network": {...}, "..." : {} }
}
```

La réponse contiendra `collection_config: null` tant que le NAS n'est pas approuvé et configuré.

### Contacts suivants

L'agent lit `collection_config` dans chaque réponse et s'en sert pour savoir quoi collecter au prochain cycle. Si `collection_config` est `null`, il renvoie uniquement l'identification (pas de `responses`).

```
Réponse reçue → lire collection_config → appeler les APIs listées → envoyer responses au cycle suivant
```

---

## Réponse du serveur

### Succès — `200 OK`

```json
{
  "status":      "ok",
  "nas_id":      42,
  "snapshot_id": 187,
  "is_new":      false,
  "collection_config": {
    "interval_seconds": 3600,
    "apis": [
      { "api": "SYNO.Core.System",         "method": "info",      "version": 7 },
      { "api": "SYNO.Storage.CGI.Storage", "method": "load_info", "version": 1 }
    ]
  }
}
```

| Champ | Type | Description |
|-------|------|-------------|
| `status` | string | Toujours `"ok"` en cas de succès |
| `nas_id` | integer | ID interne du NAS dans SynoManager |
| `snapshot_id` | integer | ID du snapshot créé |
| `is_new` | boolean | `true` si c'est la **première** réception pour ce numéro de série |
| `collection_config` | object \| null | Configuration de collecte pour le prochain cycle. `null` si le NAS est en attente ou sans modèle API configuré |

#### `collection_config`

| Sous-champ | Type | Description |
|------------|------|-------------|
| `interval_seconds` | integer | Intervalle de collecte en secondes (configuré dans SynoManager) |
| `apis` | array | Liste des APIs à appeler au prochain cycle |
| `apis[].api` | string | Nom de l'API DSM (ex: `"SYNO.Core.System"`) |
| `apis[].method` | string | Méthode DSM à appeler (ex: `"info"`) |
| `apis[].version` | integer | Version à utiliser — calculée comme `min(version_modèle, version_max_disponible_sur_le_NAS)` |
| `apis[].parameters` | object | *(optionnel)* Paramètres supplémentaires à passer à l'appel DSM. Absent si aucun paramètre configuré. |

Exemple avec paramètres :

```json
"collection_config": {
  "interval_seconds": 60,
  "apis": [
    {
      "api":        "SYNO.Backup.Task",
      "method":     "list",
      "version":    2,
      "parameters": { "additional": ["task_setting", "owner"] }
    },
    {
      "api":     "SYNO.Core.System",
      "method":  "info",
      "version": 3
    }
  ]
}
```

L'agent doit fusionner `parameters` avec les paramètres de base de l'appel DSM (`api`, `version`, `method`, `_sid`). Si `parameters` est absent, l'appel est fait sans paramètres supplémentaires.

Lorsque `is_new: true`, le NAS est en statut **`pending`** — `collection_config` sera `null` jusqu'à l'approbation et la configuration d'un modèle API dans l'interface.

### Erreur de validation — `422 Unprocessable Entity`

```json
{
  "status":  "error",
  "message": "Missing nas_identifier field: 'serial'."
}
```

Causes possibles :
- Corps de requête vide ou non-JSON
- `nas_identifier` absent
- Un des champs requis de `nas_identifier` manquant ou vide

### Erreur serveur — `500 Internal Server Error`

```json
{
  "status":  "error",
  "message": "Internal server error."
}
```

Le message est générique en production. Consulter les logs serveur (`storage/logs/`) pour le détail.

---

## Cycle de vie d'un NAS

```
1ère réception (serial inconnu)
    → NasDevice créé, status = "pending"
    → is_new = true dans la réponse
    → Le NAS apparaît dans "En attente" dans l'interface
    → Un admin doit l'approuver pour qu'il apparaisse dans le dashboard

Réceptions suivantes (serial connu)
    → NasDevice mis à jour (name, model, dsm_version, last_contact_at)
    → is_new = false
    → Nouveau snapshot créé à chaque appel
```

---

## Comportement automatique du serveur

À chaque ingestion, le serveur effectue automatiquement :

### 1. Synchronisation `api_list`

La table `nas_api_available` est **intégralement remplacée** à chaque appel.  
Elle reflète toujours l'état courant des APIs du NAS au moment de la dernière collecte.

### 2. Matching / création de modèle API

Le serveur compare l'`api_list` reçue avec les modèles API existants (comparaison exacte de l'ensemble `api_name|path|minVersion|maxVersion`).

- **Match trouvé** → le NAS est lié à ce modèle API existant
- **Aucun match** → un nouveau modèle API est créé automatiquement, nommé d'après la version DSM (ex: `"DSM 7.2.2-72806"`)

### 3. Propagation du décodeur

À chaque ingestion, le serveur re-compare l'`api_list` reçue avec tous les modèles API existants. Si le modèle qui correspond est **différent** de celui actuellement associé au NAS, le modèle API est mis à jour automatiquement — et le décodeur JSON lié à ce nouveau modèle est appliqué au NAS dans la foulée.

| Situation | Modèle API | Décodeur |
|-----------|-----------|---------|
| Pas de changement (même fingerprint) | inchangé | inchangé |
| Modèle différent trouvé ou créé | mis à jour | mis à jour si le nouveau modèle a un décodeur lié |

> Un changement de modèle API peut se produire si le NAS a été mis à jour (nouvelle version DSM, nouveaux packages) et expose une `api_list` différente.

### 4. Création du snapshot

Chaque appel crée un enregistrement `NasSnapshot` avec :
- `raw_json` : le payload complet tel que reçu (jamais modifié)
- `decoded_cache` : `null` à la création (calculé à la première consultation)
- `collected_at` : valeur du payload ou horodatage serveur

---

## Fréquence de collecte

L'intervalle de collecte est **dicté par le serveur** via `collection_config.interval_seconds` dans chaque réponse.  
L'agent doit utiliser cette valeur pour planifier son prochain cycle — il ne décide pas lui-même de la fréquence.

La fréquence est configurée dans l'interface SynoManager, fiche NAS, champ **Fréquence (minutes)**. Elle est convertie en secondes dans la réponse.

La variable `COLLECT_INTERVAL` (voir Variables d'environnement) sert uniquement de **valeur de démarrage** avant le premier contact approuvé, quand `collection_config` est encore `null`.

---

## Exemple d'appel curl

```bash
curl -X POST https://votre-serveur/api/v1/agent/ingest \
  -H "Content-Type: application/json" \
  -d '{
    "agent_version": "1.0.0",
    "collected_at":  "2024-01-15T10:30:00Z",
    "nas_identifier": {
      "serial":      "1920PDN123456",
      "model":       "DS923+",
      "server_name": "NAS-Bureau",
      "dsm_version": "DSM 7.2.2-72806"
    },
    "api_list": {
      "SYNO.Core.System": { "path": "entry.cgi", "minVersion": 1, "maxVersion": 7 }
    },
    "responses": {
      "SYNO.Core.System": {
        "success": true,
        "data": { "ram_size": 20480, "uptime": "42:17:05" }
      }
    }
  }'
```

Réponse attendue :
```json
{
  "status":      "ok",
  "nas_id":      1,
  "snapshot_id": 1,
  "is_new":      true,
  "collection_config": null
}
```

> `collection_config` est `null` ici car le NAS vient d'être créé (`is_new: true`) et est en statut `pending` — il n'a pas encore été approuvé.

---

## Variables d'environnement agent (prévues)

Ces variables seront à configurer dans le `docker-compose.yml` de l'agent :

| Variable | Description | Exemple |
|----------|-------------|---------|
| `SYNOMANAGER_URL` | URL du serveur SynoManager | `https://synomanager.exemple.fr` |
| `SYNOMANAGER_SECRET` | Clé HMAC du NAS — copiée depuis l'interface SynoManager | `a3f8c2d1e4b5...` (64 chars hex) |
| `NAS_HOST` | URL DSM locale | `https://192.168.1.10:5001` |
| `NAS_USER` | Utilisateur DSM | `admin` |
| `NAS_PASSWORD` | Mot de passe DSM | `***` |
| `NAS_SSL_VERIFY` | Vérifier le certificat SSL du NAS | `false` |
| `COLLECT_INTERVAL` | Intervalle de collecte en secondes — utilisé **uniquement** avant le premier contact approuvé (fallback quand `collection_config` est `null`) | `3600` |

> **Note :** `SYNOMANAGER_SECRET` peut être laissée vide lors du **premier envoi** (enrôlement). Dès que le NAS est approuvé dans l'interface, la clé est générée et doit être renseignée avant le prochain envoi.

> **Note :** une fois approuvé, l'agent doit utiliser `collection_config.interval_seconds` de la réponse serveur — pas `COLLECT_INTERVAL` — pour planifier ses cycles de collecte.

---

## Évolutions prévues

- [x] Signature HMAC-SHA256 (`X-Agent-Signature`) — clé par NAS, générée à l'approbation
- [x] `collection_config` dans la réponse — le serveur dicte exactement ce que l'agent doit collecter
- [ ] Endpoint de vérification de connectivité : `GET /api/v1/agent/ping`
- [ ] Notifications push vers l'agent (recollecte immédiate, mise à jour de config)

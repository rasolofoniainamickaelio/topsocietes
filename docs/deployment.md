# Déploiement natif — TOPsocietes.com

Installation native sur VPS Ubuntu (Nginx, PHP-FPM, PM2, Supervisor) — Docker interdit (CLAUDE.md §1). Ce document est rédigé au fil des phases ; seules les parties files de tâches/workers et blocage préprod sont couvertes pour l'instant.

## Préproduction — bloquer les robots (Phase 18, point critique)

Le volet applicatif est déjà en place dans le code (`RobotsController` côté backend, `middleware.ts` côté frontend) : dès que `APP_ENV` n'est pas `production`, `robots.txt` renvoie `Disallow: /` et toute réponse frontend porte `X-Robots-Tag: noindex, nofollow`. Ça suppose deux choses à faire une fois, manuellement, sur le VPS de préprod (CLAUDE.md §9 — configuration serveur, ne jamais automatiser sans validation) :

1. **Définir `APP_ENV` hors production** dans les `.env` réels de préprod (backend ET frontend) :
   ```
   # backend/.env et frontend/.env, sur le VPS de préprod uniquement
   APP_ENV=preprod
   ```

2. **Auth HTTP basique sur le vhost Nginx de préprod** — filet de sécurité en plus du noindex applicatif, seule protection réelle contre un accès direct (un moteur de recherche ignore parfois `robots.txt`/`X-Robots-Tag`) :
   ```bash
   # génère le fichier de mots de passe (une seule fois)
   sudo apt install apache2-utils   # fournit htpasswd
   sudo htpasswd -c /etc/nginx/.htpasswd-preprod <utilisateur>
   ```
   ```nginx
   # /etc/nginx/sites-available/topsocietes-preprod (vhost préprod uniquement — jamais sur le vhost production)
   server {
       # ... reste de la configuration du vhost préprod ...

       auth_basic "Préproduction — accès restreint";
       auth_basic_user_file /etc/nginx/.htpasswd-preprod;
   }
   ```
   ```bash
   sudo nginx -t && sudo systemctl reload nginx
   ```

## Files de tâches et workers (queues / Horizon)

Stack : Redis (déjà en place) + Laravel Horizon (`laravel/horizon`, installé et configuré dans `backend/`). Horizon gère plusieurs files séparées par domaine plutôt qu'une seule file `default`, pour isoler la charge et les priorités :

| File | Domaine | Particularité |
|---|---|---|
| `default` | divers | 1 tentative |
| `imports` | Domaine H (import de masse) | 1 tentative — la reprise se fait via `import_batches.checkpoint`, pas via les retries automatiques ; timeout long (1h) |
| `ai-generation` | Domaine G (pipeline IA) | jamais synchrone à l'affichage (CLAUDE.md §6.1) ; 3 tentatives, alignées sur `ai_generation_jobs.attempts` |
| `geo` | recalculs pré-calculés (`city_neighbors`, `company_nearby_pois`, compteurs) | 1 tentative, timeout 10 min |

Configuration : `backend/config/horizon.php` (un superviseur par file, `environments.production` dimensionné plus large que `environments.local`). Accès au tableau de bord (`/horizon`) restreint au rôle `super_admin` (`HorizonServiceProvider::gate()`, via `spatie/laravel-permission`).

### Prérequis Linux — `pcntl`

Horizon repose sur l'extension PHP `pcntl` (fork/signaux) pour superviser ses workers : elle n'existe que sous Linux/Unix, **jamais sous Windows**. Sur le VPS Ubuntu, s'assurer qu'elle est activée :

```bash
php -m | grep pcntl   # doit lister "pcntl" ; sinon : apt install php8.3-cli (l'inclut nativement)
```

### Supervisor — garder Horizon vivant

Horizon (`php artisan horizon`) est un processus PHP au premier plan : Supervisor le relance s'il crashe ou au redémarrage du serveur.

```ini
; /etc/supervisor/conf.d/topsocietes-horizon.conf
[program:topsocietes-horizon]
process_name=%(program_name)s
command=php /var/www/topsocietes/backend/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/topsocietes/backend/storage/logs/horizon.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start topsocietes-horizon
```

Avant un déploiement (nouveau code), toujours terminer proprement Horizon plutôt que le tuer (`horizon:terminate` attend la fin des jobs en cours avant de rendre la main à Supervisor, qui relance) :

```bash
php artisan horizon:terminate
```

### Développement local (Windows)

Horizon ne peut pas tourner sur Windows (pas de `pcntl`). En local, utiliser le worker standard — le code des Jobs est strictement identique, seul le superviseur diffère :

```bash
php artisan queue:work --queue=ai-generation,imports,geo,default
```

Nécessite Redis (ou Memurai, son équivalent Windows) démarré — voir `docs/DATABASE.md` §7 pour son installation locale.

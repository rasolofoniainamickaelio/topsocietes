# Déploiement natif — TOPsocietes.com

Installation native sur VPS Ubuntu (Nginx, PHP-FPM, PM2, Supervisor) — Docker interdit (CLAUDE.md §1). Ce document est rédigé au fil des phases ; seule la partie files de tâches / workers est couverte pour l'instant.

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

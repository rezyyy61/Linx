# Development Workflow Guide

This document explains **how to work locally** on this project: services, branches, env files, frontends/backends, URLs, commands, checks, and troubleshooting.

---

## 0) Overview

**Services**

* `php` → Laravel (user/public app) – code in `backend-app/`
* `php-admin` → Laravel (admin) – code in `backend-admin/`
* `frontend` → Vite/Vue (user/public app) – code in `frontend-app/`
* `frontend-admin` → Vite/Vue (admin) – code in `frontend-admin/`
* `nginx` → reverse proxy for local domains
* `db` → MySQL 8
* `redis` → Redis (queues/cache)
* `queue` / `scheduler` → Laravel workers
* `mailhog` → fake SMTP/web UI for emails

**Local domains (via Nginx)**

* Laravel app (backend): `http://localhost:8080`
* Vue app (frontend): `http://app.localhost:8080`
* Vue admin (frontend-admin): `http://admin.localhost:8080`

> Add these to `/etc/hosts`:

```
127.0.0.1 app.localhost
127.0.0.1 admin.localhost
```

---

## 1) Branch Strategy

* `main` → production-ready, always green.
* **Feature branches** (new features):

  ```bash
  git checkout -b feat/short-description
  ```
* **Fix branches** (bug fixes):

  ```bash
  git checkout -b fix/short-description
  ```

Keep branches small and focused. One PR per logical change.

---

## 2) Bringing the stack up

From project root:

```bash
# Build & start everything you need for dev
docker compose up -d

# If you changed Nginx config:
docker compose exec -T nginx nginx -t
docker compose restart nginx
```

**Health**

* MySQL: `docker compose ps` should show db as (healthy).
* PHP: `docker compose ps` should show php/php-admin as (healthy).
* Nginx healthcheck validates config with `nginx -t`.

---

## 3) First-time setup (.env, DB, keys)

### 3.1 Backend (App)

```bash
cp backend-app/.env.example backend-app/.env
docker compose exec -T php php artisan key:generate

# DB settings (already wired via docker-compose to service "db"):
# DB_HOST=db, DB_PORT=3306, DB_DATABASE=app, DB_USERNAME=app, DB_PASSWORD=app
# Run migrations (and seeders if any):
docker compose exec -T php php artisan migrate
# docker compose exec -T php php artisan db:seed
```

### 3.2 Backend (Admin)

```bash
cp backend-admin/.env.example backend-admin/.env
docker compose exec -T php-admin php artisan key:generate

# Use the same DB or a separate one.
# If you want a separate admin DB, create it once:
docker compose exec -T db mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS admin_db;"

# Then in backend-admin/.env set:
# DB_CONNECTION=mysql
# DB_HOST=db
# DB_PORT=3306
# DB_DATABASE=admin_db
# DB_USERNAME=app
# DB_PASSWORD=app

docker compose exec -T php-admin php artisan migrate
# docker compose exec -T php-admin php artisan db:seed
```

### 3.3 Backend testing env (no file writes during tests)

```bash
# Keep tests fast/clean
cat > backend-app/.env.testing <<'ENV'
APP_ENV=testing
APP_DEBUG=true
LOG_CHANNEL=stderr
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
ENV
```

(Do the same for `backend-admin` if you add tests there.)

---

## 4) Running the frontends

### 4.1 Frontend (user/public app)

```bash
# Install deps once (use run --rm to avoid CWD issues on some Docker versions)
docker compose run --rm -T frontend sh -lc 'cd /app && npm ci || npm i'

# Dev server (proxied by Nginx at http://app.localhost:8080)
docker compose run --rm -T frontend sh -lc 'cd /app && npm run dev -- --host'
```

### 4.2 Frontend (admin)

```bash
docker compose run --rm -T frontend-admin sh -lc 'cd /app && npm ci || npm i'
# Dev server (proxied by Nginx at http://admin.localhost:8080)
docker compose run --rm -T frontend-admin sh -lc 'cd /app && npm run dev -- --host'
```

> You can also use `docker compose exec`, but if you ever see:
> `current working directory is outside of container mount namespace`
> prefer `docker compose run --rm` + `cd /app`.

**Vite URLs shown in logs (5173/5174) are internal**. Access the frontends via Nginx:

* App: `http://app.localhost:8080`
* Admin: `http://admin.localhost:8080`

---

## 5) Useful URLs

* **App (Laravel)**: `http://localhost:8080`
* **Frontend (Vite)**: `http://app.localhost:8080`
* **Admin (Vite)**: `http://admin.localhost:8080`
* **Mailhog**: `http://localhost:8025`

**Database (from host, e.g. DBeaver):**

* Host: `127.0.0.1`
* Port: `3307` (host-mapped to container 3306)
* User: `app`
* Pass: `app`
* DB: `app` (or `admin_db` if you created it)

From inside the php containers:

```bash
docker compose exec -T db mysql -uapp -papp app
```

---

## 6) Day-to-day commands

### Enter containers

```bash
# Backends
docker compose exec php bash          # backend-app
docker compose exec php-admin bash    # backend-admin

# Frontends
docker compose exec frontend sh
docker compose exec frontend-admin sh

# DB & Redis
docker compose exec db sh
docker compose exec redis sh
```

### Laravel (backend-app)

```bash
docker compose exec -T php php artisan migrate
docker compose exec -T php php artisan db:seed
docker compose exec -T php php artisan queue:work
docker compose exec -T php composer require vendor/package
```

### Laravel (backend-admin)

```bash
docker compose exec -T php-admin php artisan migrate
docker compose exec -T php-admin php artisan db:seed
docker compose exec -T php-admin composer require vendor/package
```

### Vue (frontend-app / frontend-admin)

```bash
# Install
docker compose run --rm -T frontend        sh -lc 'cd /app && npm ci || npm i'
docker compose run --rm -T frontend-admin  sh -lc 'cd /app && npm ci || npm i'

# Dev
docker compose run --rm -T frontend        sh -lc 'cd /app && npm run dev -- --host'
docker compose run --rm -T frontend-admin  sh -lc 'cd /app && npm run dev -- --host'

# Build (if needed)
docker compose run --rm -T frontend        sh -lc 'cd /app && npm run build'
docker compose run --rm -T frontend-admin  sh -lc 'cd /app && npm run build'
```

---

## 7) Quality gates (local pre-push & CI)

We enforce quality both locally (Git **pre-push** hook) and in CI:

**Pre-push runs:**

* Frontend lint → `eslint`
* Frontend type-check → `vue-tsc`
* Frontend unit tests → `vitest`
* Backend code style → `pint`
* Backend static analysis → `phpstan`
* Backend tests → `phpunit`

**Run manually (backend-app):**

```bash
docker compose exec -T php ./vendor/bin/pint --test
docker compose exec -T php ./vendor/bin/phpstan analyse
docker compose exec -T php php artisan test --env=testing
```

**Run manually (frontend-app):**

```bash
docker compose run --rm -T frontend sh -lc 'cd /app && npm run lint:fix'
docker compose run --rm -T frontend sh -lc 'cd /app && npm run type-check'
docker compose run --rm -T frontend sh -lc 'cd /app && npm run test:unit'
```

> Mirror the same commands for `php-admin` and `frontend-admin` if/when you add checks there.

**CI (GitHub Actions)** runs similar steps on PRs/commits to `main`. Keep branches green.

---

## 8) Pull Requests & Merge

1. Push your branch:

   ```bash
   git push -u origin your-branch-name
   ```
2. Open PR into `main`.
3. All CI checks must pass.
4. At least one approval required.
5. Resolve all conversations before merging.
6. After merge:

   ```bash
   git checkout main
   git pull origin main
   ```

---

## 9) Troubleshooting

**Nginx shows “unhealthy”**

* Our healthcheck uses `nginx -t`. Validate and restart:

  ```bash
  docker compose exec -T nginx nginx -t
  docker compose restart nginx
  ```

**Can’t reach `app.localhost` / `admin.localhost`**

* Add to `/etc/hosts`:

  ```
  127.0.0.1 app.localhost
  127.0.0.1 admin.localhost
  ```
* Ensure `frontend`/`frontend-admin` dev servers are running.

**`current working directory is outside of container mount namespace`**

* Use `docker compose run --rm -T <service> sh -lc 'cd /app && <command>'`.

**File permission errors (Laravel storage/cache)**

```bash
docker compose exec -T php bash -lc 'chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache'
# Do the same inside php-admin if needed
```

**MySQL port already in use**

* We map container 3306 → host 3307. Stop other DBs on 3307 or change mapping.

**Mail not arriving**

* Check MailHog: `http://localhost:8025`

---

## 10) Conventions & Notes

* Keep **admin** and **app** code **separate** (both backend & frontend).
* Share code deliberately via packages/modules (don’t mix trees).
* For APIs:

    * `backend-app` → public/user endpoints
    * `backend-admin` → admin endpoints
* Auth: use Sanctum/JWT per app needs; keep guards distinct if both apps authenticate.
* Env secrets do **not** go to git. Use `.env`, `.env.testing`, and CI secrets.

---

## 11) Quick Reference

**Start/stop**

```bash
docker compose up -d
docker compose down
```

**Logs**

```bash
docker compose logs -f nginx
docker compose logs -f frontend
docker compose logs -f frontend-admin
docker compose logs -f php
docker compose logs -f php-admin
docker compose logs -f db
```

**Rebuild after Dockerfile changes**

```bash
docker compose build --no-cache php php-admin frontend frontend-admin
docker compose up -d
```

---

**Happy coding! 🚀**

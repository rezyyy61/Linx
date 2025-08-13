# Development Workflow Guide

This document explains the development workflow for our project, from setting up branches to running checks before pushing or merging.

---

## 1. Branch Strategy

- **main**: Production-ready code. Always stable.
- **feature branches**: Create a new branch for each task or feature.
  ```bash
  git checkout -b feat/short-description
  ```
- **fix branches**: For bug fixes.
  ```bash
  git checkout -b fix/short-description
  ```

---

## 2. Before Starting a Task

1. Make sure your local main branch is up to date:
   ```bash
   git checkout main
   git pull origin main
   ```
2. Create a new branch for your task (see branch strategy above).

---

## 3. Working with Containers

To enter backend container:
```bash
docker compose exec php sh
```

To enter frontend container:
```bash
docker compose exec frontend sh
```

To enter database container:
```bash
docker compose exec db sh
```

---

## 4. Database Access

**Inside container:**
```bash
mysql -uapp -papp app
```

**From host (port 3307):**
- Host: `127.0.0.1`
- Port: `3307`
- User: `app`
- Password: `app`
- Database: `app`

---

## 5. Before Commit & Push

Our project uses a **pre-push hook** that will run:

- Frontend lint (`eslint`)
- Frontend type-check (`vue-tsc`)
- Frontend unit tests (`vitest`)
- Backend code style check (`pint`)
- Backend static analysis (`phpstan`)
- Backend tests (`phpunit`)

Make sure all checks pass locally before pushing to avoid CI failures.

You can run checks manually:
```bash
# Backend
docker compose exec php ./vendor/bin/pint --test
docker compose exec php ./vendor/bin/phpstan analyse
docker compose exec php php artisan test --env=testing

# Frontend
docker compose exec frontend npm run lint
docker compose exec frontend npm run type-check
docker compose exec frontend npm run test:unit
```

---

## 6. Pull Requests & Merge

1. Push your branch:
   ```bash
   git push -u origin your-branch-name
   ```
2. Open a Pull Request (PR) to merge into `main`.
3. Wait for all CI checks to pass.
4. At least one approval is required before merging.
5. Resolve all conversations before merge.

---

## 7. After Merge

- Update your local main branch:
  ```bash
  git checkout main
  git pull origin main
  ```

---

## 8. Useful Commands

- **Start containers**:
  ```bash
  docker compose up -d
  ```
- **Stop containers**:
  ```bash
  docker compose down
  ```
- **View container logs**:
  ```bash
  docker compose logs -f service_name
  ```

---

**Happy coding! 🚀**
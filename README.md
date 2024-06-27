# API Backend Reddit Clone - Symfony vs. Laravel

A full-stack Reddit clone with a Vue 3 / TypeScript frontend, built twice with diffrent stack. Once with **Symfony 7 + API Platform**, and once with **Laravel 11**. Target of the project was to directly compare the two frameworks' developer experience on the same domain, the same endpoints, and the same database. 

API endpoints as well as whole frontend were designed and planned before starting development of backend.

The Symfony version was built first (`legacy/backend/symfony`) and later rebuilt from scratch in Laravel (`dev/backend`) as a deliberate side-by-side experiment, not a migration. Both implementations model the same core domain: users, subreddits/communities, threads/posts, comments, and membership, behind a custom token-authenticated REST API.

> This repo is a fork of [km385/Reddit-clone](https://github.com/km385/Reddit-clone). Frontend: Vue 3 + TypeScript ([source](https://github.com/km385/Reddit-clone)). Backend: this repo, on two separate branches.

| | |
|---|---|
| **Frontend** | branch [`dev/frontend`](./Frontend) |
| **Backend (Laravel)** | branch [`dev/backend`](../../tree/dev/backend/Backend) |
| **Backend (Symfony)** | branch [`legacy/backend/symfony`](../../tree/legacy/backend/symfony/Backend) |

---

## Why two backends?

Both frameworks are commonly recommended for "serious" PHP APIs, but they solve the same problems very differently, declarative resource metadata vs. explicit controllers, an ORM-driven API layer vs. a thin one. Rather than read about the trade-offs, this project builds the identical app twice to feel them directly: routing, auth, validation, serialization, filtering, testing, and Docker setup, done once in each framework.

## At a glance

| | Symfony backend | Laravel backend |
|---|---|---|
| Framework | Symfony 7.0 | Laravel 11 |
| API layer | API Platform 3.2 | Custom controllers |
| ORM | Doctrine ORM 2.9 | Eloquent |
| Auth | Custom `AccessToken` entity + `AccessController` | Laravel Sanctum, with custom token abilities (`access-api`, `refresh-token`) |
| API docs | Auto-generated OpenAPI/Swagger via API Platform | `dedoc/scramble` (auto-generated OpenAPI from routes/types) |
| Real-time | Mercure Hub bundled in | Not included |
| Test data | Foundry (factories) + Doctrine fixtures | Laravel factories + seeders |
| Testing | PHPUnit, API-focused test suite (`tests/Api/*`) | PHPUnit, feature tests organized by domain (`tests/Feature/Auth/*`, `tests/Feature/Resources/*`) |
| Container runtime | Custom Docker Compose setup | Laravel Sail |


## Quick start

### Frontend (either backend)

```sh
cd Frontend
npm run dev        # local dev server with hot-reload

# or, containerized:
docker build . -t reddit-clone-client
docker run -d -p 8080:80 --name reddit-clone-frontend reddit-clone-client
```

### Backend Laravel (`dev/backend`)

```sh
git checkout dev/backend
cd Backend
cp .env.example .env
bash vendor/bin/sail build --no-cache
bash vendor/bin/sail up -d
bash vendor/bin/sail artisan migrate
```

API docs: `http://127.0.0.1:8000/api/documentation`

Local (non-Sail) alternative:

```sh
composer install        # then point .env at localhost instead of pgsql
php artisan serve
php artisan migrate
```

### Backend Symfony (`legacy/backend/symfony`)

```sh
git checkout legacy/backend/symfony
cd Backend
cp .env.local.example .env.local
docker compose --env-file .env.local build --no-cache
docker compose --env-file .env.local up --wait
```

API: `https://localhost/api` · Profiler: `http://localhost/_profiler`

Local (non-Docker) alternative:

```sh
composer install         # point DATABASE_URL in .env.local at localhost
symfony serve -d          # or: php -S localhost:8000 -t public
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
```


## Team

| Who | What |
|---|---|
| [@Jakub F](https://github.com/km385) | Frontend |
| [@Mateusz C](https://github.com/MateuszCzz) | Symfony/API Platform  & Laravel implementation |
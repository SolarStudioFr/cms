# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project state

This is a Symfony 7.4 skeleton in its initial state: `src/Controller`, `src/Entity`, and `src/Repository` are empty (each holds only a `.gitignore`). No application code, routes, or entities exist yet. Treat architectural decisions (routing style, entity design, templating conventions) as open unless the user has already established a pattern in the current session.

## Stack

- PHP 8.2+ (containers run PHP 8.4), Symfony 7.4.*
- Doctrine ORM 3.x / DBAL, MySQL 8.4, migrations via `doctrine/doctrine-migrations-bundle`
- Twig for templates; JS/CSS is built with Webpack Encore (`symfony/webpack-encore-bundle`), compiled via npm — not AssetMapper
- React 19 (+ `react-router-dom`, `react-bootstrap`, `react-toastify`, `react-icons`), Bootstrap 5, Sass, axios for the frontend
- `symfony/security-bundle` is installed but unconfigured beyond an in-memory user provider — no auth flow exists yet
- PHPUnit 13 for tests

## Development environment

The full stack runs via Docker Compose (`compose.yaml` / `compose.override.yaml`), wrapped by a `Makefile`: Apache (`localhost:8000`) reverse-proxies PHP requests to a separate PHP-FPM container, MySQL (`localhost:3306`, data bind-mounted at `docker/db/data` so the project folder stays portable across machines), phpMyAdmin (`localhost:8080`), and Mailpit (`localhost:8025`). Config for each service lives under `docker/{apache,php,sql}`.

`DATABASE_URL` in `.env` defaults to `mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.4&charset=utf8mb4` — override in `.env.local` for real credentials, never edit `.env` itself with secrets.

## Common commands

```bash
make install    # composer install, npm install/build (host, no docker)
make init       # drop/recreate/migrate the database (host, requires `make start` already running)
make start      # docker compose up -d --build; prints the app/phpMyAdmin/Mailpit URLs
make stop       # docker compose down
make test       # run PHPUnit inside the php container
make log        # tail all container logs
make terminal   # shell into the php container at /var/www/html
make migrate    # run pending Doctrine migrations inside the php container
make clear      # cache:clear inside the php container

# Generate code (entities, controllers, etc.) via MakerBundle
php bin/console make:entity
php bin/console make:controller

# Single-test runs (inside the php container, e.g. via `make terminal`)
php bin/phpunit tests/Path/To/SomeTest.php       # single file
php bin/phpunit --filter testMethodName          # single test method
```

## Architecture notes

- Autoloading: `App\` maps to `src/`, `App\Tests\` maps to `tests/` (PSR-4, see `composer.json`).
- Entities are mapped via PHP attributes (not YAML/XML), auto-mapped from `src/Entity`, using Doctrine's underscore naming strategy for columns/tables.
- `config/packages/*.yaml` follows standard Symfony Flex recipe layout — one file per bundle, with `when@test` / `when@prod` blocks for environment-specific overrides in the same file rather than separate `config/packages/{env}/` files.
- The `dev` firewall in `security.yaml` disables auth for `_profiler`, `_wdt`, `assets`, `build` paths; the `main` firewall is lazy and currently has no authenticator configured.
- Frontend entrypoints are declared in `webpack.config.js` via `Encore.addEntry(...)` and built into `public/build/`. There are two React entrypoints: `assets/app/index.jsx` (public site) and `assets/adm/index.jsx` (admin backend) — `templates/base.html.twig` currently wires in `app` only via `encore_entry_link_tags`/`encore_entry_script_tags`; an admin-specific base template will need the `adm` entry.

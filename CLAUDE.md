# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project state

This is a Symfony 7.4 skeleton in its initial state: `src/Controller`, `src/Entity`, and `src/Repository` are empty (each holds only a `.gitignore`). No application code, routes, or entities exist yet. Treat architectural decisions (routing style, entity design, templating conventions) as open unless the user has already established a pattern in the current session.

## Stack

- PHP 8.2+, Symfony 7.4.*
- Doctrine ORM 3.x / DBAL, PostgreSQL 16, migrations via `doctrine/doctrine-migrations-bundle`
- Twig for templates, `symfony/asset-mapper` (no Node/webpack build step) for JS/CSS
- Stimulus (`symfony/stimulus-bundle`) + Turbo (`symfony/ux-turbo`) for frontend interactivity
- `symfony/security-bundle` is installed but unconfigured beyond an in-memory user provider — no auth flow exists yet
- PHPUnit 13 for tests

## Development environment

The database (Postgres) and a mail-catcher (Mailpit) run via Docker Compose (`compose.yaml` / `compose.override.yaml`). If the project uses Symfony CLI + the `symfony/orm-pack` docker integration, `symfony server:start` / `docker compose up -d` bring up the stack; otherwise start Postgres manually and set `DATABASE_URL` in `.env.local`.

`DATABASE_URL` in `.env` defaults to `postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8` — override in `.env.local` for real credentials, never edit `.env` itself with secrets.

## Common commands

```bash
# Install PHP dependencies
composer install

# Install/build frontend assets (asset-mapper, no bundler)
php bin/console importmap:install
php bin/console asset-map:compile   # for prod builds

# Run the dev server
symfony server:start
# or
php -S 127.0.0.1:8000 -t public

# Database / migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:diff       # generate a migration from entity changes
php bin/console doctrine:migrations:migrate

# Generate code (entities, controllers, etc.) via MakerBundle
php bin/console make:entity
php bin/console make:controller

# Tests
php bin/phpunit                                 # full suite
php bin/phpunit tests/Path/To/SomeTest.php       # single file
php bin/phpunit --filter testMethodName          # single test method

# Clear cache
php bin/console cache:clear
```

## Architecture notes

- Autoloading: `App\` maps to `src/`, `App\Tests\` maps to `tests/` (PSR-4, see `composer.json`).
- Entities are mapped via PHP attributes (not YAML/XML), auto-mapped from `src/Entity`, using Doctrine's underscore naming strategy for columns/tables.
- `config/packages/*.yaml` follows standard Symfony Flex recipe layout — one file per bundle, with `when@test` / `when@prod` blocks for environment-specific overrides in the same file rather than separate `config/packages/{env}/` files.
- The `dev` firewall in `security.yaml` disables auth for `_profiler`, `_wdt`, `assets`, `build` paths; the `main` firewall is lazy and currently has no authenticator configured.
- Frontend JS lives in `assets/` and is served through AssetMapper's importmap (`assets/controllers.json`, `importmap.php`) rather than a bundler — new Stimulus controllers go in `assets/controllers/` and get auto-registered.

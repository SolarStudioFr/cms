# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project state

Beyond the skeleton, the project now has: session-cookie authentication (`json_login` + persistent remember-me) with a `User` entity and a `ROLE_SUPER_ADMIN` fixture, a basic admin shell (dashboard + left nav), a `Page` CRUD feature exposed via API Platform and consumed by a dynamically-loaded Module Federation admin plugin (`plugin/page/`), and a public homepage listing published pages. See "Architecture notes" below for how these pieces fit together. Treat further architectural decisions as open unless the user has already established a pattern in the current session.

The project follows a WordPress/PrestaShop-style layout: `cms/` holds all Symfony/PHP core logic (including the admin backend), `template/` holds one or more swappable front-end themes, `plugin/` is a scaffolded (currently empty) directory for a future plugin system, and the **repository root itself is the web server document root** — there is no `public/` directory. An installer script tying `plugin/`/`template/` into the CMS is planned but out of scope for now.

## Stack

- PHP 8.2+ (containers run PHP 8.4), Symfony 7.4.*
- Doctrine ORM 3.x / DBAL, MySQL 8.4, migrations via `doctrine/doctrine-migrations-bundle` (multiple migration namespaces, see below)
- API Platform 4.x (`api-platform/core`) for JSON resources (`/api/...`); `doctrine/doctrine-fixtures-bundle` (dev/test) for fixtures
- Twig for templates; JS/CSS is built with Webpack Encore (`symfony/webpack-encore-bundle`), compiled via npm — not AssetMapper; Webpack 5 native Module Federation for the plugin system
- React 19 (+ `react-router-dom`, `react-bootstrap`, `react-toastify`, `react-icons`), Bootstrap 5, Sass, axios for the frontend
- `symfony/security-bundle`: entity-backed `json_login` (session cookie) + persistent (Doctrine-backed) remember-me — see "Authentication" below
- PHPUnit 13 for tests (functional tests run against a real MySQL `app_test` database, see "Common commands")

## Directory structure

```
index.php               # front controller — the only PHP entry point Apache routes to
cms/                     # Symfony core: composer.json, vendor/, src/, config/, migrations/,
                         # tests/, translations/, bin/, .env*, plus the admin app
                         # (cms/assets/adm, cms/templates/adm.base.html.twig)
  public/build/          # generated: Webpack Encore output (exposed at /build via Apache alias)
  public/bundles/        # generated: `assets:install` output for bundle public assets (/bundles alias)
plugin/                  # scaffolded, empty — future plugin system
template/
  default/
    assets/              # React source for the public site (Encore entry "default")
    templates/           # Twig views for the public site, loaded under the `@theme` namespace
var/                     # cache/log, kept at repo root (sibling of cms/) via Kernel overrides
upload/                  # user-uploaded files, publicly readable, PHP execution disabled
docker/, package.json, webpack.config.js, node_modules/, Makefile, compose.yaml  # stay at root
```

`cms/`, `var/`, `template/`, `plugin/`, `docker/`, and root-level tooling files are all denied direct HTTP access in `docker/apache/vhost.conf` — everything goes through `index.php` except the explicit `/build`, `/bundles`, and `upload/` exceptions. Keep this in mind when adding anything new at the root: if it needs to be fetched directly by a browser, it must live under `cms/public/`, `template/*/` (compiled output only), or `upload/`; otherwise add it to the deny list.

`cms/src/Kernel.php` overrides `getCacheDir()`/`getLogDir()` (rather than a `services.yaml` parameter, which the Kernel would just overwrite) so `var/cache/` and `var/log/` are written at the repo root instead of nesting under `cms/`.

## Development environment

The full stack runs via Docker Compose (`compose.yaml` / `compose.override.yaml`), wrapped by a `Makefile`: Apache (`localhost:8000`) reverse-proxies PHP requests to a separate PHP-FPM container, MySQL (`localhost:3306`, data bind-mounted at `docker/db/data` so the project folder stays portable across machines), phpMyAdmin (`localhost:8080`), and Mailpit (`localhost:8025`). Config for each service lives under `docker/{apache,php,sql}`. Both the `php` and `apache` containers bind-mount the entire repository root to `/var/www/html`; `docker/apache/vhost.conf` sets `DocumentRoot` to that same root and denies direct access to everything except the front controller, the Encore build output, and `upload/`.

`DATABASE_URL` in `cms/.env` defaults to `mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.4&charset=utf8mb4` — override in `cms/.env.local` for real credentials, never edit `cms/.env` itself with secrets.

## Common commands

```bash
make install    # composer install --working-dir=cms, npm install/build (host, no docker)
make init       # drop/recreate/migrate/seed (UserFixtures) the database (host, requires `make start` already running)
make start      # docker compose up -d --build; prints the app/phpMyAdmin/Mailpit URLs
make stop       # docker compose down
make test       # create/migrate/seed the app_test DB, then run PHPUnit inside the php container
make log        # tail all container logs
make terminal   # shell into the php container at /var/www/html
make migrate    # run pending Doctrine migrations inside the php container
make clear      # cache:clear inside the php container

# Generate code (entities, controllers, etc.) via MakerBundle
php cms/bin/console make:entity
php cms/bin/console make:controller

# Single-test runs (inside the php container, e.g. via `make terminal`)
php cms/bin/phpunit cms/tests/Path/To/SomeTest.php   # single file
php cms/bin/phpunit --filter testMethodName          # single test method
```

## Architecture notes

- Autoloading: `App\` maps to `cms/src/`, `App\Tests\` maps to `cms/tests/` (PSR-4, see `cms/composer.json`). `Kernel::getProjectDir()` resolves to `cms/` (it walks up from `cms/src/Kernel.php` to the nearest `composer.json`, which is `cms/composer.json`); the root `index.php` boots via `cms/vendor/autoload_runtime.php`, which resolves the same way independently.
- Entities are mapped via PHP attributes (not YAML/XML), auto-mapped from `cms/src/Entity`, using Doctrine's underscore naming strategy for columns/tables.
- `cms/config/packages/*.yaml` follows standard Symfony Flex recipe layout — one file per bundle, with `when@test` / `when@prod` blocks for environment-specific overrides in the same file rather than separate `config/packages/{env}/` files. Most `%kernel.project_dir%/...` references in these files needed no edits after the restructuring, since they now correctly resolve relative to `cms/`.
- The `dev` firewall in `security.yaml` disables auth for `_profiler`, `_wdt`, `assets`, `build` paths; the `main` firewall is lazy, entity-backed (`App\Entity\User`, identified by `email`), with `json_login` (`POST /api/login`, no CSRF option — not supported by `json_login` in this Symfony version, unlike `form_login`) and persistent (Doctrine-backed) `remember_me`. `access_control` gates `^/api/admin` behind `ROLE_SUPER_ADMIN`; per-resource rules for `/api/pages*` vs `/api/admin/pages*` live in API Platform's per-operation `security` attribute instead. `api_platform.yaml`'s `defaults.stateless` is explicitly `false` — this auth is session/cookie-based, and API Platform defaults to `stateless: true` which breaks any code that touches the session (breaks `is_granted()`/`getUser()`).
- The `rememberme_token` table (raw, not Doctrine-entity-mapped) is created by a hand-written migration, not `migrations:diff` — this Symfony version doesn't wire `DoctrineTokenProvider`'s schema into doctrine-bundle's schema tooling.
- MySQL DDL implicitly commits; every migration overrides `isTransactional(): false` to avoid a deprecated "transaction already committed" silencing path. The `app` MySQL user only has grants on `app`+`app_test` (see `docker/sql/init/`) — `app_test` is what `when@test` in `doctrine.yaml` connects to.
- Doctrine entities can be mapped from more than one directory: `cms/config/packages/doctrine.yaml` has a second `mappings` entry (`PluginPage`) pointing at `plugin/page/src/Entity`, and `cms/config/packages/doctrine_migrations.yaml` has a second `migrations_paths` namespace (`Plugin\Page\Migrations` → `plugin/page/migrations`) — this is how the Page plugin's backend code stays physically under `plugin/page/src/` while still being wired into the core app (PSR-4 in `cms/composer.json`, DI resource in `cms/config/services.yaml`) rather than living in `cms/src/`. There's no generic PHP plugin auto-loader — each plugin's backend wiring is added by hand, like installing a bundle.
- `cms/src/Service/PluginRegistry.php` scans `plugin/*/plugin.json` manifests (`{name, label, remoteEntry, exposedModule}`) and `GET /api/admin/plugins` exposes the active list — this is the *dynamic* part of the plugin system: the admin frontend (`cms/assets/adm/plugins/usePlugins.js` + `loadRemoteModule.js`) fetches this list after login and loads each plugin's Module Federation remote at runtime (script-injects `remoteEntry.js`, `__webpack_init_sharing__`/`container.init()`/`container.get()`) — no admin rebuild needed to pick up a newly installed plugin. `usePlugins(enabled)` must be gated on "user is authenticated" (the endpoint requires `ROLE_SUPER_ADMIN`) and re-fire right after login, not just on mount.
- The admin host declares `ModuleFederationPlugin` with empty `remotes` (`webpack.config.js`, via `Encore.addPlugin()`) purely to get the `shared`/`__webpack_init_sharing__` runtime; each plugin remote (e.g. `plugin/page/webpack.config.js`) is a **raw** (non-Encore) webpack config — Encore can't produce a MF "container" `filename`/`exposes`/custom `output.publicPath`. A remote's `ModuleFederationPlugin` `name` must match its `plugin.json` `name` (the host looks up `window[manifest.name]`). Remote output lands under `cms/public/build/plugins/<name>/`, reusing the existing `/build` Apache alias — `plugin/` itself is HTTP-denied, so this avoids any `vhost.conf` change per plugin.
- **Both** entry points (`adm` and `default`) are forced to the classic JSX runtime (`Encore.enableReactPreset((c) => c.runtime = 'classic')`, and the plugin remote's own babel-loader config) — the automatic runtime's `react/jsx-(dev-)runtime` module doesn't play well with a Module-Federation-shared React instance (`(0, e.jsxDEV) is not a function` at runtime). Every `.jsx` file already does `import React from 'react'`, which classic runtime requires.
- Any entry whose compilation includes `ModuleFederationPlugin` with `shared` deps needs an async boundary before those deps are imported, or webpack throws `Shared module is not available for eager consumption` at runtime — hence `cms/assets/adm/index.jsx` and `template/default/assets/index.jsx` are just `import('./bootstrap')`, with the real app code (and its `import React from 'react'`) moved to `bootstrap.jsx`.
- Frontend entrypoints are declared in `webpack.config.js` (root) via `Encore.addEntry(...)` and built into `cms/public/build/`, exposed publicly at `/build` via an Apache alias. There are two entrypoints: `template/default/assets/index.jsx` (public site, entry name `default`) and `cms/assets/adm/index.jsx` (admin backend, entry name `adm`). `template/default/templates/base.html.twig` wires in `default`; `cms/templates/adm.base.html.twig` wires in `adm`.
- `cms/config/packages/twig.yaml` registers `template/default/templates` under the `@theme` Twig namespace (e.g. `render('@theme/base.html.twig')`), alongside TwigBundle's implicit default path `cms/templates` (used for admin views). The `default` theme name is currently hardcoded; a config-driven active-theme switch is future installer work.
- Adding a second theme: create `template/<name>/{assets,templates}`, add a matching Encore entry/output convention, and extend the Twig `paths` config (today single-theme, will need to become dynamic once an active-theme setting exists).

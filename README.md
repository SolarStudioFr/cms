# CMS

Un CMS (système de gestion de contenu) personnel, construit sur Symfony 7.4.

Le projet est actuellement à l'état de squelette Symfony : aucune entité, contrôleur ou route métier n'a encore été ajouté.

## Stack

- PHP 8.2+ (PHP 8.4 dans les conteneurs), Symfony 7.4.*
- Doctrine ORM / DBAL, MySQL 8.4, migrations via `doctrine/doctrine-migrations-bundle`
- Twig pour les templates, Webpack Encore (`symfony/webpack-encore-bundle`) pour les assets JS/CSS
- React 19, React Router, React-Bootstrap, Bootstrap 5, Sass, axios
- PHPUnit 13 pour les tests

## Installation

```bash
make install   # composer install, npm install, npm run build
```

## Lancer le projet (Docker)

Toute la stack (Apache + PHP-FPM, MySQL, phpMyAdmin, Mailpit) tourne via Docker Compose, pilotée par le `Makefile` :

```bash
make start   # docker compose up -d --build
```

- App : http://localhost:8000
- phpMyAdmin : http://localhost:8080
- Mailpit : http://localhost:8025
- MySQL (accessible hors docker) : `localhost:3306`

`DATABASE_URL` par défaut (voir `.env`) : `mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.4&charset=utf8mb4`
Pour utiliser d'autres identifiants, surcharger dans `.env.local` (ne pas éditer `.env`).

Les données MySQL sont montées depuis `docker/db/data` : déplacer le dossier du projet sur une autre machine conserve la base.

```bash
make init      # drop / recreate / migrate la base (nécessite `make start` au préalable)
make migrate   # migrations seules
make stop      # arrêter les conteneurs
make log       # logs en direct
make terminal  # shell dans le conteneur php
make clear     # cache:clear (env=dev par défaut, ex: make clear env=prod)
```

## Tests

```bash
make test   # PHPUnit dans le conteneur php
```

## Documentation pour les agents

Voir [CLAUDE.md](CLAUDE.md) pour les commandes détaillées et l'architecture du projet.

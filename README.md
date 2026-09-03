# CMS

Un CMS (système de gestion de contenu) personnel, construit sur Symfony 7.4.

Le projet est actuellement à l'état de squelette Symfony : aucune entité, contrôleur ou route métier n'a encore été ajouté.

## Stack

- PHP 8.2+, Symfony 7.4.*
- Doctrine ORM / DBAL, PostgreSQL 16, migrations via `doctrine/doctrine-migrations-bundle`
- Twig pour les templates, `symfony/asset-mapper` pour les assets JS/CSS (pas de build Node)
- Stimulus (`symfony/stimulus-bundle`) + Turbo (`symfony/ux-turbo`) pour l'interactivité front
- PHPUnit 13 pour les tests

## Installation

```bash
composer install
php bin/console importmap:install
```

La base de données (Postgres) et un mail-catcher (Mailpit) tournent via Docker Compose :

```bash
docker compose up -d
```

`DATABASE_URL` par défaut (voir `.env`) : `postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8`
Pour utiliser d'autres identifiants, surcharger dans `.env.local` (ne pas éditer `.env`).

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Lancer le serveur

```bash
symfony server:start
# ou
php -S 127.0.0.1:8000 -t public
```

## Tests

```bash
php bin/phpunit
```

## Documentation pour les agents

Voir [CLAUDE.md](CLAUDE.md) pour les commandes détaillées et l'architecture du projet.

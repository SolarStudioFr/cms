# CMS

Un CMS (système de gestion de contenu) personnel, construit sur Symfony 7.4.

Le projet est actuellement à l'état de squelette Symfony : aucune entité, contrôleur ou route métier n'a encore été ajouté.

## Stack

- PHP 8.2+, Symfony 7.4.*
- Doctrine ORM / DBAL, PostgreSQL 16, migrations via `doctrine/doctrine-migrations-bundle`
- Twig pour les templates, Webpack Encore (`symfony/webpack-encore-bundle`) pour les assets JS/CSS
- React 19, React Router, React-Bootstrap, Bootstrap 5, Sass, axios
- PHPUnit 13 pour les tests

## Installation

```bash
composer install
npm install
npm run build   # ou npm run dev / npm run watch en développement
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

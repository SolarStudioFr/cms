# Étape 59 — Point d'entrée `public/` et fichiers d'environnement à la racine

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "Global").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Restructuration globale : point d'entrée et fichiers d'environnement
**Slug** : `bootstrap-public-dir-restructure`

## Objectif

- Déplacer `index.php` (front controller, actuellement à la racine du dépôt) dans un répertoire `public/`, et reconfigurer Docker/Apache (`docker/apache/vhost.conf`, `DocumentRoot`) en conséquence.
- Déplacer les fichiers d'environnement (`.env`, `.env.test`, et l'équivalent dev — voir "Notes ouvertes" de `../MAIN.md` sur l'intitulé exact "`.env.dev`") de `cms/` vers la racine du dépôt.

## Portée / dépendances

Changement structurel indépendant du reste de la refonte i18n (52-58) — aucune dépendance fonctionnelle, mais touche des fichiers de bootstrap partagés (`Kernel::getProjectDir()`, chargement Dotenv, `docker/apache/vhost.conf`) : à coordonner dans le temps pour éviter les conflits avec un autre chantier en cours plutôt qu'à cause d'un ordre imposé. Implique de revoir `cms/src/Kernel.php` (qui résout aujourd'hui `getProjectDir()` en remontant jusqu'à `cms/composer.json`) et la liste de répertoires refusés en accès direct HTTP dans `vhost.conf` (peut se simplifier une fois que seul `public/` est exposé par le `DocumentRoot`, au lieu de la racine du dépôt entière). `CLAUDE.md` devra être mis à jour une fois ce changement fait (architecture actuellement documentée : "le dépôt racine est le document root, il n'y a pas de répertoire `public/`").

## Suivi du protocole (section 3 de INIT_ETAPES.md)

Protocole générique documenté dans `../action/`. Cases à cocher pour cette étape une
fois le travail démarré :

- [ ] 1. Lire `docs/RELEASE.md`
- [ ] 2. Réaliser le travail
- [ ] 3. Commenter le code en anglais
- [ ] 4. Tester avec PHPUnit (aucune dépréciation)
- [ ] 5. Monter la version (`cms/composer.json` + `cms/.env`)
- [ ] 6. Journaliser dans `docs/RELEASE.md`
- [ ] 7. Commit puis push
- [ ] 8. Rapport à l'utilisateur + validation avant l'étape suivante

## Journal de l'étape

_(vide — à compléter pendant le déroulement de l'étape)_

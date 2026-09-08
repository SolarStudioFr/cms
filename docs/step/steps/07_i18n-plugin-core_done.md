# Étape 07 — Squelette du plugin multilangue + gestion des langues

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Issu du backlog en section 5 de `../../INIT_ETAPES.md`.
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Multilangue (plugin)
**Slug** : `i18n-plugin-core`

## Objectif

Squelette du plugin (suit la logique de `plugin/page/` : entity/API dans `plugin/i18n/src/`, UI admin en Module Federation). Entity Langue, menu admin pour ajouter/gérer les langues actives (FR + EN au démarrage).

## Portée / dépendances

Aucune dépendance amont. Bloquant pour 08 et, à terme, pour l'intégration multilangue de tous les autres plugins (voir note dans `MAIN.md`).

## Suivi du protocole (section 3 de INIT_ETAPES.md)

Protocole générique documenté dans `../action/`. Cases à cocher pour cette étape une
fois le travail démarré :

- [x] 1. Lire `docs/RELEASE.md`
- [x] 2. Réaliser le travail
- [x] 3. Commenter le code en anglais
- [x] 4. Tester avec PHPUnit (aucune dépréciation)
- [x] 5. Monter la version (`cms/composer.json` + `cms/.env`)
- [x] 6. Journaliser dans `docs/RELEASE.md`
- [x] 7. Commit puis push
- [x] 8. Rapport à l'utilisateur + validation avant l'étape suivante

## Journal de l'étape

Fait en version 0.7.0. Voir `docs/RELEASE.md`. Exécutée dans le cadre de l'enchaînement des étapes 06 à 16 autorisé par l'utilisateur (session du 2026-09-04).

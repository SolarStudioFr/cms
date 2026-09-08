# Étape 13 — Module Téléchargement

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Éditeur de contenu (drag & drop, page builder — plugin unique)
**Slug** : `builder-module-download`

## Objectif

Module « Télécharger un fichier » : bloc proposant un fichier à télécharger (PDF ou ZIP) dans le builder. L'ajout du fichier passe par le média picker (03) configuré pour n'afficher/n'accepter que les fichiers de type PDF et ZIP.

## Portée / dépendances

Dépend de 10 (`builder-core`) et 03 (`media-picker-component`, filtré PDF/ZIP). Fait partie du plugin unique du builder.

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

Fait en version 0.13.0. Voir `docs/RELEASE.md`. Exécutée dans le cadre de l'enchaînement des étapes 06 à 16 autorisé par l'utilisateur (session du 2026-09-04).

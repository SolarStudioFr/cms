# Étape 05 — Ré-optimisation de toutes les images

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Gestion des fichiers (pas un plugin — cms/*)
**Slug** : `file-reoptimize-images`

## Objectif

Bouton en admin, section gestionnaire de fichiers, permettant de ré-optimiser toutes les images (régénération des versions webp et des 4 miniatures — 256, 512, 1024, 2048 px — à partir du fichier source original).

## Portée / dépendances

Dépend de 01 (`file-storage-backend`). Contrairement à 04 (`file-cleanup-unused`), ne nécessite pas de connaître les usages du fichier : peut être exécutée dès que 01 est livrée, indépendamment de l'avancement des autres plugins.

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

Fait en version 0.5.0, livrée dans le même commit que l'étape 04 (dérogation documentée dans `docs/RELEASE.md`, section « Décisions »). Régénère webp + 4 miniatures en conservant les noms de fichiers déjà stockés. Exécutée dans le cadre de l'enchaînement des 5 premières étapes autorisé par l'utilisateur (session du 2026-09-04).

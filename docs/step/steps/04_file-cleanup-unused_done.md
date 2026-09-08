# Étape 04 — Suppression des images non utilisées

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Gestion des fichiers (pas un plugin — cms/*)
**Slug** : `file-cleanup-unused`

## Objectif

Bouton en admin, section gestionnaire de fichiers, permettant de supprimer toutes les images non utilisées (non référencées par un contenu).

## Portée / dépendances

Dépend de 01 (`file-storage-backend`). La détection fiable des fichiers « non utilisés » suppose que la majorité des plugins consommateurs de fichiers existent déjà (builder et modules, réalisations, actualités, accueil, config site) — à exécuter en pratique après ces étapes malgré son ID bas, dû au regroupement par fonctionnalité (voir note dans `MAIN.md`). Étape distincte de 05 (`file-reoptimize-images`) : deux boutons séparés, deux actions indépendantes.

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

Fait en version 0.5.0, livrée dans le même commit que l'étape 05 (dérogation documentée dans `docs/RELEASE.md`, section « Décisions »). Le mécanisme de détection d'usage (`FileUsageCheckerInterface`) est un point d'extension vide pour l'instant — aucun consommateur de fichiers n'existe encore, donc tout fichier compte actuellement comme non utilisé, conformément à la note déjà présente ci-dessus. Exécutée dans le cadre de l'enchaînement des 5 premières étapes autorisé par l'utilisateur (session du 2026-09-04).

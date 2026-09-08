# Étape 02 — Gestionnaire de fichiers en admin

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Issu du backlog en section 5 de `../../INIT_ETAPES.md`.
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Gestion des fichiers (pas un plugin — cms/*)
**Slug** : `file-manager-admin-ui`

## Objectif

Interface admin pour parcourir, uploader et supprimer les fichiers déjà envoyés, au-dessus du service générique de l'étape 01.

## Portée / dépendances

Dépend de 01 (`file-storage-backend`). Réutilise l'admin existant (dashboard/nav).

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

Fait en version 0.3.0. Voir l'entrée correspondante dans `docs/RELEASE.md` (dont un correctif de permissions important : `upload/` écrit par des uids différents selon le contexte, `www-data` vs `root`). Exécutée dans le cadre de l'enchaînement des 5 premières étapes autorisé par l'utilisateur (session du 2026-09-04).

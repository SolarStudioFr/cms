# Étape 03 — Modal de sélection/ajout de fichiers (média picker)

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Gestion des fichiers (pas un plugin — cms/*)
**Slug** : `media-picker-component`

## Objectif

Composant modal réutilisable en admin pour sélectionner un fichier déjà enregistré ou en ajouter un nouveau, utilisé par tout éditeur de contenu (builder, page, actualités, réalisations, accueil) pour l'insertion d'images ou de fichiers. Configurable par l'appelant : types de fichiers visibles et autorisés à l'upload (ex. images uniquement, ou PDF + ZIP uniquement pour un module « Télécharger un fichier »).

## Portée / dépendances

Dépend de 01 (`file-storage-backend`) et 02 (`file-manager-admin-ui`, pour la logique de listing/upload réutilisée). Bloquant pour tout module/plugin consommant des fichiers : `builder-module-image` (11), `builder-module-slider` (12), `builder-module-download` (13), `builder-module-text` (15, images inline), et les plugins de contenu (17, 19, 21).

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

Fait en version 0.4.0. Voir `docs/RELEASE.md` — composant livré et testé côté API/build, mais pas encore intégré à un vrai consommateur (aucun n'existe encore dans le backlog à ce stade) ni la question du partage vers les plugins MF résolue (traité au moment où un plugin en aura besoin). Exécutée dans le cadre de l'enchaînement des 5 premières étapes autorisé par l'utilisateur (session du 2026-09-04).

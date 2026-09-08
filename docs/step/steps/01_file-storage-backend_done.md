# Étape 01 — Backend générique de fichiers (entity + stockage + traitement d'image)

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Gestion des fichiers (pas un plugin — cms/*)
**Slug** : `file-storage-backend`

## Objectif

Entity `File` générique (`cms/src/Entity`), gérée par un service de stockage réutilisable par tout plugin ayant besoin de fichiers/images. Champs : `name` (utilisé comme alt pour les images), `description` (optionnelle, documentation interne du fichier), `uniqid`, `slug`, `size` (en octets), `width`, `height`, `source` (chemin depuis `/`), `file` (chemin depuis `/`), `thumbnail` (array de chemins depuis `/`), `type` (`img`, `pdf` ou `zip`), `created_at`, `modified_at`.

Arborescence de stockage :
- `./upload/img/source/{Y-m-d}_{h-i-s}_{slug}.{ext}` — image originale
- `./upload/img/webp/{Y-m-d}_{h-i-s}_{uniqid}_{slug}.webp` — version optimisée
- `./upload/img/thumbnail/{width}_{Y-m-d}_{h-i-s}_{uniqid}_{slug}.webp` — miniatures optimisées en 256, 512, 1024 et 2048 px de large
- `./upload/pdf/{Y-m-d}_{h-i-s}_{uniqid}_{slug}.pdf`
- `./upload/zip/{Y-m-d}_{h-i-s}_{uniqid}_{slug}.zip`

Pipeline de traitement à l'upload d'une image : conservation de l'original dans `source/`, génération d'une version `webp` optimisée, génération des 4 miniatures `webp`. Logique d'image par défaut affichée quand le fichier référencé n'existe pas/plus, côté public comme admin.

## Portée / dépendances

Pas un plugin — code dans `cms/src/*`. Aucune dépendance amont. Bloquant pour : `media-picker-component` (03), et plus généralement tout plugin manipulant des fichiers (builder, réalisations, actualités, accueil, config site pour logo/favicon).

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

Fait en version 0.2.0. Voir l'entrée correspondante dans `docs/RELEASE.md` pour le détail (fichiers créés, décisions, erreurs rencontrées). Exécutée dans le cadre d'un enchaînement des 5 premières étapes explicitement autorisé par l'utilisateur (session du 2026-09-04) — l'action 8 est donc journalisée mais n'a pas bloqué le démarrage de l'étape suivante.

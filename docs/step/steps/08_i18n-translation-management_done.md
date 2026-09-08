# Étape 08 — Gestion des traductions

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Multilangue (plugin)
**Slug** : `i18n-translation-management`

## Objectif

Gestion des traductions (contenu et/ou interface) directement depuis l'administration : outil d'édition/création de traductions (composant React dédié, backend Symfony/composer pour la lecture/écriture) + intégration dans le rendu public. Export et import des traductions au format standard **PO** (gettext), pour permettre un échange avec des outils de traduction externes.

## Portée / dépendances

Dépend de 07 (`i18n-plugin-core`). Nécessite une librairie composer de lecture/écriture PO (ex. le loader/writer PO de `symfony/translation`, ou une librairie dédiée type `gettext/gettext` — à choisir à l'implémentation).

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

Fait en version 0.8.0. Voir `docs/RELEASE.md`. Exécutée dans le cadre de l'enchaînement des étapes 06 à 16 autorisé par l'utilisateur (session du 2026-09-04).

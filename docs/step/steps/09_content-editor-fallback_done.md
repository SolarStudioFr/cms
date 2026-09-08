# Étape 09 — Éditeur de contenu générique (fallback)

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Éditeur de contenu — fallback générique (pas un plugin — cms/*)
**Slug** : `content-editor-fallback`

## Objectif

Éditeur de contenu simple (WYSIWYG type TinyMCE ou équivalent), utilisé par défaut par tout plugin ayant besoin d'éditer du contenu riche (page, réalisations, actualités, accueil) tant que le plugin builder (10+) n'est pas installé ou activé. Si le plugin builder est actif, il remplace cet éditeur simple — **aucun des deux ne doit être une dépendance bloquante** pour les plugins consommateurs.

## Portée / dépendances

Pas un plugin — `cms/src/*` (+ `cms/assets/adm` pour le composant React partagé). Dépend de 06 (`plugin-manager-admin`, pour connaître l'état actif/inactif du plugin builder) et bénéficie de 03 (`media-picker-component`) pour l'insertion d'images inline. Bloquant, en tant que solution par défaut, pour 17, 19, 21, et pour une future mise à jour de `plugin/page` tant que 16 n'est pas livrée.

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

Fait en version 0.9.0. Voir `docs/RELEASE.md` — a aussi résolu le partage de composants hôte → plugin en Module Federation, laissé en suspens à l'étape 03. Exécutée dans le cadre de l'enchaînement des étapes 06 à 16 autorisé par l'utilisateur (session du 2026-09-04).

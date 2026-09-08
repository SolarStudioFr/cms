# Étape 32 — Gestion admin des menus

> Statut : **done** (v0.28.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).

**Fonctionnalité** : Menus (pas un plugin — cms/*)
**Slug** : `menus-admin`

## Objectif

Gestion admin des menus : créer/nommer/organiser des menus, et les lier à un « hook » déclaré par le template actif. Chaque template déclare ses points d'accroche (hooks) sous un nom (ex. « header-menu », « footer-menu ») ; l'administration récupère cette liste de hooks disponibles et permet d'associer un menu créé à l'un d'entre eux.

**Ajout explicite de l'utilisateur, bundlé dans cette étape** (demandé en même temps que 32+33) : gestion de l'ordre d'affichage (et de séparateurs) des entrées du menu de navigation de l'admin CMS lui-même (`cms/assets/adm/layout/Sidebar.jsx`) — hors backlog initial, sans pendant côté rendu public donc traité entièrement ici plutôt que dans 33.

## Portée / dépendances

Pas un plugin. Dépend d'un mécanisme de déclaration de hooks côté template. Bloquant pour 33.

## Suivi du protocole (section 3 de INIT_ETAPES.md)

- [x] 1. Lire `docs/RELEASE.md`
- [x] 2. Réaliser le travail
- [x] 3. Commenter le code en anglais
- [x] 4. Tester avec PHPUnit (aucune dépréciation) — 102 tests verts
- [x] 5. Monter la version (`cms/composer.json` + `cms/.env`) — 0.28.0
- [x] 6. Journaliser dans `docs/RELEASE.md`
- [x] 7. Commit puis push
- [ ] 8. Rapport à l'utilisateur + validation avant l'étape suivante

## Journal de l'étape

Voir `docs/RELEASE.md` 0.28.0 pour le détail complet (entité `Menu`, `ThemeRegistry`/`theme.json`, UI admin, et la fonctionnalité additionnelle `AdminMenuConfig`/ordre du menu admin).

Décisions clés : `items` stocké en colonne `JSON` unique sur `Menu` plutôt qu'une entité `MenuItem` séparée (même raisonnement que le builder builder-core, étape 10) ; thème actif toujours codé en dur `"default"` (cohérent avec `twig.yaml`) ; l'ajout "ordre du menu admin" vit entièrement dans cette étape (pas de pendant public).

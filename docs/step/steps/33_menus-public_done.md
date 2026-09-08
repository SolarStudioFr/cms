# Étape 33 — Affichage public des menus

> Statut : **done** (v0.28.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).

**Fonctionnalité** : Menus (pas un plugin — cms/*)
**Slug** : `menus-public`

## Objectif

Rendu public : à l'endroit de chaque hook déclaré dans le template, affichage du menu qui lui a été accroché en admin (étape 32).

## Portée / dépendances

Dépend de 32 (`menus-admin`).

## Suivi du protocole (section 3 de INIT_ETAPES.md)

- [x] 1. Lire `docs/RELEASE.md`
- [x] 2. Réaliser le travail
- [x] 3. Commenter le code en anglais
- [x] 4. Tester avec PHPUnit (aucune dépréciation) — 102 tests verts (partagés avec l'étape 32, même commit)
- [x] 5. Monter la version (`cms/composer.json` + `cms/.env`) — 0.28.0 (partagée avec 32)
- [x] 6. Journaliser dans `docs/RELEASE.md`
- [x] 7. Commit puis push
- [ ] 8. Rapport à l'utilisateur + validation avant l'étape suivante

## Journal de l'étape

Voir `docs/RELEASE.md` 0.28.0 pour le détail complet : `useMenus.js` + `MenuHook.jsx` côté `template/default/assets/`, câblés dans `App.jsx` aux hooks `header-menu` (barre de nav) et `footer-menu` (pied de page). Aucun rendu si aucun menu n'est attaché à un hook, ou si le menu attaché est vide (principe « aucun plugin/fonctionnalité non-bloquant·e »).

L'ajout utilisateur « ordre du menu admin » (voir étape 32) n'a pas de pendant public — rien de spécifique à cette étape à son sujet.

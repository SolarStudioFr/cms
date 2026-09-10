# Étape 50 — Module Liste d'actualités

> Statut : **done** (v0.32.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Articles / Réalisation (deux bloks)", partie Articles).

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-news-list`

## Objectif

Affiche les dernières actualités mais sans titre ni description de section, juste une liste. Formulaire : nombre d'articles, ordre (liste : plus récent en premier / plus ancien en premier), filtre par catégorie (liste, optionnel).

## Portée / dépendances

Dépend de 10 (`builder-core`), du plugin Actualités (19-20), du mécanisme de rendu dynamique conçu à l'étape 44 (même contrainte que les blocks "dynamique" : la liste doit refléter les derniers articles à chaque affichage) et de 39 (`news-categories`) pour le filtre optionnel par catégorie.

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

Livrée avec 46 à 49, 51 en un seul commit/version (0.32.0) — voir `docs/RELEASE.md` pour le détail complet. Réutilise tel quel le marqueur `data-builder-feed="news"` de l'étape 44 (même hydratation), avec `data-order`/`data-category` en plus — `builderFeedHydrator.js` a été étendu en conséquence, sans changer le comportement des blocks 44/45 existants (attributs absents pour eux). Filtre catégorie et tri "plus ancien en premier" résolus côté client, aucun nouvel endpoint backend.

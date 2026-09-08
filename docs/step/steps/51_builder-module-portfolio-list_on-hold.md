# Étape 51 — Module Liste de réalisations

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Articles / Réalisation (deux bloks)", partie Réalisation).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-portfolio-list`

## Objectif

Affiche les dernières réalisations mais sans titre ni description de section, juste une liste. Formulaire : nombre de réalisations, ordre (liste : plus récent en premier / plus ancien en premier), filtre par tags (cases à cocher, optionnel).

## Portée / dépendances

Dépend de 10 (`builder-core`), du plugin Réalisations (17-18), du mécanisme de rendu dynamique conçu à l'étape 44 (même contrainte que les blocks "dynamique") et de 38 (`portfolio-tags`) pour le filtre optionnel par tags.

## Suivi du protocole (section 3 de INIT_ETAPES.md)

Protocole générique documenté dans `../action/`. Cases à cocher pour cette étape une
fois le travail démarré :

- [ ] 1. Lire `docs/RELEASE.md`
- [ ] 2. Réaliser le travail
- [ ] 3. Commenter le code en anglais
- [ ] 4. Tester avec PHPUnit (aucune dépréciation)
- [ ] 5. Monter la version (`cms/composer.json` + `cms/.env`)
- [ ] 6. Journaliser dans `docs/RELEASE.md`
- [ ] 7. Commit puis push
- [ ] 8. Rapport à l'utilisateur + validation avant l'étape suivante

## Journal de l'étape

_(vide — à compléter pendant le déroulement de l'étape)_

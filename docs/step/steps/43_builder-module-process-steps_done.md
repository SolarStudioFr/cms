# Étape 43 — Module "Notre approche"

> Statut : **done** (v0.31.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Notre approche").

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-process-steps`

## Objectif

Liste les méthodes/étapes de travail de l'entreprise. Formulaire : eyebrow, titre, 4 étapes fixes (chacune : libellé numéro, titre, description).

## Portée / dépendances

Dépend de 10 (`builder-core`). Fait partie du plugin builder existant. Nombre d'étapes fixe (4, contrairement aux listes dynamiques de 41/42/49) — pas de bouton d'ajout/suppression à prévoir.

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

Livrée avec 40 à 42, 44, 45 en un seul commit/version (0.31.0) — voir `docs/RELEASE.md` pour le détail complet. Les 4 étapes sont bien fixes, sans bouton d'ajout/suppression, comme prévu.

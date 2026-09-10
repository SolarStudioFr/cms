# Étape 49 — Module Tarifs

> Statut : **done** (v0.32.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Tarifs").

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-pricing`

## Objectif

Affiche trois cards de tarif sur le site. Formulaire (répété pour chacune des 3 cards) : titre, tarif, icône (recherche et liste), description, liste d'options incluses (bouton "Ajouter une option", chaque option = texte + bouton de suppression).

## Portée / dépendances

Dépend de 10 (`builder-core`). Fait partie du plugin builder existant. Nombre de cards fixe (3) mais liste d'options par card dynamique (ajout/suppression) — même pattern de sous-liste imbriquée que 41 (clients) et 42 (services), à concevoir de façon cohérente entre les trois si possible.

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

Livrée avec 46 à 48, 50, 51 en un seul commit/version (0.32.0) — voir `docs/RELEASE.md` pour le détail complet. 3 cards fixes (même convention que `ProcessStepsModule`, étape 43), chacune avec sa propre liste d'options imbriquée dynamique (même pattern que 41/42).

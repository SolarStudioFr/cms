# Étape 49 — Module Tarifs

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Tarifs").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-pricing`

## Objectif

Affiche trois cards de tarif sur le site. Formulaire (répété pour chacune des 3 cards) : titre, tarif, icône (recherche et liste), description, liste d'options incluses (bouton "Ajouter une option", chaque option = texte + bouton de suppression).

## Portée / dépendances

Dépend de 10 (`builder-core`). Fait partie du plugin builder existant. Nombre de cards fixe (3) mais liste d'options par card dynamique (ajout/suppression) — même pattern de sous-liste imbriquée que 41 (clients) et 42 (services), à concevoir de façon cohérente entre les trois si possible.

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

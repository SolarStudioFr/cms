# Étape 48 — Module Service (carte seule)

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Service (Carte seule)").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-service-card`

## Objectif

Comme "Grille des services" (42) mais avec une seule carte. Formulaire : couleur (liste : primary, secondary, success, warning, danger, info, light, dark), icône (recherche et liste), titre, description.

## Portée / dépendances

Dépend de 10 (`builder-core`) et de 42 (`builder-module-services-grid`) — même forme de carte (icône + titre + description), sous-composant partagé à évaluer à ce moment plutôt qu'une duplication (voir "Notes ouvertes" de `../MAIN.md`). Ajoute une couleur de fond (liste Bootstrap) que 42 n'a pas au niveau de la carte individuelle — à vérifier si 42 doit être aligné en retour.

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

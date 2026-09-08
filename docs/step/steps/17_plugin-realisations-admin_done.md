# Étape 17 — Plugin Réalisations — admin

> Statut : **done**. Fait partie du découpage documenté dans `../MAIN.md`. Issu du backlog en section 5 de `../../INIT_ETAPES.md`.

**Fonctionnalité** : Gestionnaires de contenu (plugins)
**Slug** : `plugin-realisations-admin`

## Objectif

CRUD admin du plugin Réalisations (portfolio de projets/réalisations), suivant la logique de `plugin/page/`.

## Portée / dépendances

**Les étapes 17 et 18 constituent un seul et même plugin Réalisations.** Utilise l'éditeur générique (09) par défaut, remplacé par le builder (10+) s'il est actif. Bloquant pour 18.

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
- [x] 8. Rapport à l'utilisateur + validation avant l'étape suivante (dérogation : lot 17-21 autorisé sans attente, voir mémoire)

## Journal de l'étape

Voir `docs/RELEASE.md` (0.17.0). CRUD admin complet, copie conforme du plugin Page + image de couverture. A aussi corrigé un bug pré-existant (fixtures purgeant les données seed de `lang`).

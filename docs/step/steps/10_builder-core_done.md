# Étape 10 — Cœur du builder drag & drop

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Issu du backlog en section 5 de `../../INIT_ETAPES.md`.
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Éditeur de contenu (drag & drop, page builder — plugin unique)
**Slug** : `builder-core`

## Objectif

Canvas drag & drop, modèle de données pour stocker les modules d'une page, registre de modules conçu pour permettre l'ajout d'autres modules plus tard sans réécrire le système.

## Portée / dépendances

Aucune dépendance amont stricte, mais bénéficie de 01/03 (fichiers) si des modules image sont prévus tôt. Bloquant pour 11-16. **Les étapes 10 à 16 constituent un seul et même plugin** (ex. `plugin/builder/`) — pas un plugin distinct par module, suivant la même logique que `plugin/page/`.

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

Fait en version 0.10.0. Voir `docs/RELEASE.md` — registre vide pour l'instant, vérification interactive complète différée à l'étape 16 (premier vrai point d'intégration). Exécutée dans le cadre de l'enchaînement des étapes 06 à 16 autorisé par l'utilisateur (session du 2026-09-04).

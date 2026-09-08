# Étape 34 — Collecte des métriques de base

> Statut : **done** (v0.29.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).

**Fonctionnalité** : Statistiques (plugin)
**Slug** : `stats-tracking-core`

## Objectif

Suivi des visites : navigateur, OS, langue, résolution d'écran, pages vues, temps moyen passé par page, pourcentage de scroll moyen, clics sur boutons/liens. Structure conçue pour être extensible (autres métriques plus tard). La récupération/envoi des données de tracking se fait **exclusivement en AJAX**, jamais lors du chargement initial de la page — le script de tracking est chargé en **defer** pour ne pas ralentir l'affichage.

## Portée / dépendances

Aucune dépendance amont stricte.

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

Réalisée avec 35 et 36 en un seul lot (v0.29.0) - voir `docs/RELEASE.md` pour le détail complet (entité `PageView`, `TrackingController`, script `tracker.js`).

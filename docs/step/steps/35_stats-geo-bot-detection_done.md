# Étape 35 — Géolocalisation + détection bots

> Statut : **done** (v0.29.0). Fait partie du découpage documenté dans `../MAIN.md`. Issu du backlog en section 5 de `../../INIT_ETAPES.md`.

**Fonctionnalité** : Statistiques (plugin)
**Slug** : `stats-geo-bot-detection`

## Objectif

Origine géographique (pays) et détection détaillée des crawlers/bots.

## Portée / dépendances

Dépend de 34 (`stats-tracking-core`, structure de collecte). Traitement (géolocalisation IP, détection bot) effectué côté serveur sur réception des appels AJAX de 34, sans impact sur le chargement de la page publique.

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

Réalisée avec 34 et 36 en un seul lot (v0.29.0) - voir `docs/RELEASE.md` pour le détail complet (`UserAgentAnalyzer` sur matomo/device-detector, `GeoIpResolver` sur ip-api.com).

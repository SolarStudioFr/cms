# Étape 23 — Plugin Newsletter — gestion des campagnes

> Statut : **done** (v0.23.0).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Newsletter (plugin, avec une brique générale)
**Slug** : `newsletter-plugin-admin`

## Objectif

Gestion des campagnes newsletter côté admin : liste des abonnés, création de campagne, déclenchement de l'envoi en masse.

## Portée / dépendances

Dépend de 22 (`mail-sending-service`). **Les étapes 23 à 25 constituent un seul et même plugin Newsletter.**

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

_(vide — à compléter pendant le déroulement de l'étape)_

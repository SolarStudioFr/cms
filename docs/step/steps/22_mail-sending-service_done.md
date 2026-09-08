# Étape 22 — Brique générale d'envoi d'email

> Statut : **done** (v0.23.0).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Newsletter (plugin, avec une brique générale)
**Slug** : `mail-sending-service`

## Objectif

Script d'envoi d'email générique (SMTP, etc.), réutilisable ailleurs — `cms/src/*`.

## Portée / dépendances

Pas un plugin. Bloquant pour 23/24 et pour `site-config-smtp` (29).

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

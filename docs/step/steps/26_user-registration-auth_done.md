# Étape 26 — Inscription publique + vérification email

> Statut : **done** (v0.24.0).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Utilisateurs (pas un plugin — cms/*)
**Slug** : `user-registration-auth`

## Objectif

Connexion, inscription, vérification d'adresse email côté public, au-dessus de l'auth session-cookie existante (`User` entity, `json_login`).

## Portée / dépendances

Pas un plugin. Réutilise l'auth existante (voir `security.yaml`). Bénéficie de `mail-sending-service` (22) pour l'envoi de l'email de vérification.

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

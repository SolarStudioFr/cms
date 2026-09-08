# Étape 31 — Vidage du cache

> Statut : **done** (v0.27.0).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Configuration du site (pas un plugin — cms/*)
**Slug** : `site-config-cache-clear`

## Objectif

Bouton en admin, section configuration, permettant de vider les caches du site (cache Symfony applicatif ; à évaluer à l'implémentation si d'autres caches doivent être inclus, ex. registre des plugins, traductions).

## Portée / dépendances

Pas un plugin. Aucune dépendance amont stricte.

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

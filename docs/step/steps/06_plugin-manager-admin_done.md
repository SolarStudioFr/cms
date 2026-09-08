# Étape 06 — Gestionnaire de plugins en admin

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Gestionnaire de plugins (pas un plugin — cms/*)
**Slug** : `plugin-manager-admin`

## Objectif

Page d'administration listant les plugins détectés (manifests `plugin/*/plugin.json` via `PluginRegistry`), avec pour chacun : statut, activation/désactivation, et suppression (avec confirmation).

## Portée / dépendances

Pas un plugin — code dans `cms/src/*` (extension de `PluginRegistry`) + `cms/assets/adm`. S'appuie sur `PluginRegistry` existant (`GET /api/admin/plugins`) : nécessite d'ajouter un état actif/inactif persistant (le manifeste `plugin.json` seul ne suffit pas) et une action de suppression physique du dossier du plugin. Un plugin désactivé ne doit plus être renvoyé par `GET /api/admin/plugins` (donc plus chargé en Module Federation par `usePlugins`). Bloquant, en pratique, pour toute logique d'autres étapes qui doit détecter si un plugin optionnel (ex. le builder, étape 10) est actif — voir principe « aucun plugin ne doit être bloquant » dans `MAIN.md`.

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

Fait en version 0.6.0. Voir `docs/RELEASE.md` — premier test de bout en bout dans un vrai navigateur (Playwright headless) sur ce projet. Exécutée dans le cadre de l'enchaînement des étapes 06 à 16 autorisé par l'utilisateur (session du 2026-09-04).

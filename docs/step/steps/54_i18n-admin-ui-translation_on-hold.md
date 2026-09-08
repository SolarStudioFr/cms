# Étape 54 — Traduction de l'interface admin

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "i18n").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Refonte du multilinguisme (i18n)
**Slug** : `i18n-admin-ui-translation`

## Objectif

Traduire l'interface de l'administration elle-même (menus, boutons, libellés) en français et en anglais, avec un bouton de changement de langue dans l'administration.

## Portée / dépendances

Dépend de 53 (`i18n-po-symfony-standard`, mécanisme de traduction) et de 52 (i18n dans `cms/*`). Concerne la **langue de l'interface**, réglage propre à l'admin connecté (à distinguer de la langue du contenu qu'il édite, voir 58). Couvre les pages statiques du cœur (`UserManager`, `SiteConfig`, `FileManager`, etc.) — les plugins restants sont couverts séparément par 57.

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

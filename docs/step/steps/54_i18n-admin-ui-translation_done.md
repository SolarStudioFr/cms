# Étape 54 — Traduction de l'interface admin

> Statut : **done**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "i18n").

**Fonctionnalité** : Refonte du multilinguisme (i18n)
**Slug** : `i18n-admin-ui-translation`

## Objectif

Traduire l'interface de l'administration elle-même (menus, boutons, libellés) en français et en anglais, avec un bouton de changement de langue dans l'administration.

## Portée / dépendances

Dépend de 53 (`i18n-po-symfony-standard`, mécanisme de traduction) et de 52 (i18n dans `cms/*`). Concerne la **langue de l'interface**, réglage propre à l'admin connecté (à distinguer de la langue du contenu qu'il édite, voir 58). Couvre les pages statiques du cœur (`UserManager`, `SiteConfig`, `FileManager`, etc.) — les plugins restants sont couverts séparément par 57.

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

Voir `docs/RELEASE.md` 0.35.0 pour le détail complet. Résumé : `TranslationProvider`/`useTranslator()`/`LocaleSwitcher` (React, domaine `admin`) + `cms/translations/admin.{fr,en}.po` (~140 clés) ; toutes les pages statiques du cœur traduites (Login, Dashboard, FileManager, PluginManager, UserManager, SiteConfig, MenuManager, MenuForm, AdminMenuSettings, PageList, PageForm + composants partagés). `LangManager.jsx` non traité ici, remplacé à l'étape 55.

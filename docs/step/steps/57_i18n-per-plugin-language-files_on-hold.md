# Étape 57 — Fichiers de langue par plugin

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "i18n", puce "Avoir les fichiers de langue dans chaque plugin...").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Refonte du multilinguisme (i18n)
**Slug** : `i18n-per-plugin-language-files`

## Objectif

Donner à chaque plugin restant ses propres fichiers de langue (FR + EN) pour ses chaînes d'interface (admin et, le cas échéant, rendu public) : Réalisations, Actualités, Accueil, Newsletter, Page builder, Statistiques.

## Portée / dépendances

Dépend de 53 (`i18n-po-symfony-standard`, mécanisme/convention à réutiliser tel quel, domaine de traduction par plugin). Ne concerne plus `page`/`i18n` une fois 52 fait — ces deux-là sont couverts par 54 (admin) et 56 (public) au même titre que le reste du cœur. Travail répétitif une fois le mécanisme en place (53) — un plugin par lot, pas de nouvelle conception par plugin.

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

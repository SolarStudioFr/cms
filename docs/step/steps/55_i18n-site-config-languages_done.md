# Étape 55 — Langues dans la Configuration du site

> Statut : **done**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "i18n").

**Fonctionnalité** : Refonte du multilinguisme (i18n)
**Slug** : `i18n-site-config-languages`

## Objectif

Ajouter dans la section admin Configuration (`SiteConfig`, étapes 29-31) une zone listant les langues disponibles et permettant de choisir celles activées côté site public.

## Portée / dépendances

Dépend de 52 (i18n dans `cms/*`, entité `Lang` existante). Voir "Notes ouvertes" de `../MAIN.md` : à clarifier au démarrage si cette section remplace la page dédiée `LangManager.jsx` (étape 07) ou coexiste avec elle (l'une ajoute/retire une langue disponible, l'autre pilote son activation publique). Alimente le menu public de changement de langue (56), qui ne doit lister/afficher que les langues activées ici.

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

Voir `docs/RELEASE.md` 0.36.0. Résumé initial (v0.36.0) : `LangManager.jsx` remplacé par `LanguagesSection.jsx`, montée dans `SiteConfig.jsx`, même backend `/admin/langs` inchangé.

**Correction le même jour** (voir `docs/RELEASE.md`, section "Correction (même jour) — Étape 55 revue") : l'utilisateur a précisé que l'interprétation initiale était incorrecte. `LangManager.jsx` restauré tel quel comme page dédiée ; Configuration ne gagne qu'un simple sélecteur "Langue par défaut" (nouveau champ `SiteConfig::$defaultLocale`), désormais consommé par les `TranslationContext` admin/public (étapes 54/56) comme repli avant "fr".

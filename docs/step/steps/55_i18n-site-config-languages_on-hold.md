# Étape 55 — Langues dans la Configuration du site

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "i18n").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Refonte du multilinguisme (i18n)
**Slug** : `i18n-site-config-languages`

## Objectif

Ajouter dans la section admin Configuration (`SiteConfig`, étapes 29-31) une zone listant les langues disponibles et permettant de choisir celles activées côté site public.

## Portée / dépendances

Dépend de 52 (i18n dans `cms/*`, entité `Lang` existante). Voir "Notes ouvertes" de `../MAIN.md` : à clarifier au démarrage si cette section remplace la page dédiée `LangManager.jsx` (étape 07) ou coexiste avec elle (l'une ajoute/retire une langue disponible, l'autre pilote son activation publique). Alimente le menu public de changement de langue (56), qui ne doit lister/afficher que les langues activées ici.

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

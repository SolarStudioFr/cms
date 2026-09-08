# Étape 56 — Traduction de l'interface publique + sélecteur de langue

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "i18n").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Refonte du multilinguisme (i18n)
**Slug** : `i18n-public-ui-translation`

## Objectif

Traduire l'interface du site public (menus, boutons, libellés du thème `default`) en français et en anglais. Ajouter un menu public de changement de langue — masqué automatiquement si une seule langue est active.

## Portée / dépendances

Dépend de 53 (`i18n-po-symfony-standard`) et de 55 (`i18n-site-config-languages`, pour la liste des langues activées qui pilote à la fois le contenu du menu et sa visibilité). Concerne la **langue de l'interface publique** (à distinguer de la langue du contenu affiché, non traitée ici).

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

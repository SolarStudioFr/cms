# Étape 53 — Format PO au standard Symfony

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "i18n", puce "Remplacer les fichier PO...").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Refonte du multilinguisme (i18n)
**Slug** : `i18n-po-symfony-standard`

## Objectif

Remplacer le format PO maison actuel (`TranslationPoConverter`, étape 08) par le standard Symfony : fichiers sous `translations/`, nommage `domaine.locale.format` (ex. `messages.fr.po`), chargés par `Symfony\Component\Translation\TranslatorInterface` plutôt que par un parseur/table ad hoc.

## Portée / dépendances

Dépend de 52 (`page-i18n-to-core`, i18n doit d'abord être dans `cms/*`). Concerne uniquement la **langue de l'interface** (chaînes statiques admin/public), pas la langue du contenu (voir la distinction posée en tête de la section "Refonte du multilinguisme" de `../MAIN.md`, traitée séparément à l'étape 58). Fondation pour 54 (admin), 56 (public) et 57 (par plugin), qui consomment tous ce mécanisme.

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

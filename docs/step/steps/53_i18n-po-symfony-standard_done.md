# Étape 53 — Format PO au standard Symfony

> Statut : **done**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "i18n", puce "Remplacer les fichier PO...").

**Fonctionnalité** : Refonte du multilinguisme (i18n)
**Slug** : `i18n-po-symfony-standard`

## Objectif

Remplacer le format PO maison actuel (`TranslationPoConverter`, étape 08) par le standard Symfony : fichiers sous `translations/`, nommage `domaine.locale.format` (ex. `messages.fr.po`), chargés par `Symfony\Component\Translation\TranslatorInterface` plutôt que par un parseur/table ad hoc.

## Portée / dépendances

Dépend de 52 (`page-i18n-to-core`, i18n doit d'abord être dans `cms/*`). Concerne uniquement la **langue de l'interface** (chaînes statiques admin/public), pas la langue du contenu (voir la distinction posée en tête de la section "Refonte du multilinguisme" de `../MAIN.md`, traitée séparément à l'étape 58). Fondation pour 54 (admin), 56 (public) et 57 (par plugin), qui consomment tous ce mécanisme.

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

Voir `docs/RELEASE.md` 0.34.0 pour le détail complet. Résumé : ancien système PO maison (`Translation` entity, `TranslationController`, `TranslationPoConverter`, `TranslationManager.jsx`) supprimé ; nouveau `App\Controller\I18nCatalogController` (`GET /api/i18n/{domain}/{locale}`) sert les fichiers `.po` standard Symfony sous `cms/translations/` (encore vide — le contenu réel arrive aux étapes 54/56/57) au format JSON pour les deux SPA React (admin/public), qui ne peuvent pas utiliser `trans()`/`{% trans %}` directement.

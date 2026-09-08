# Étape 39 — Catégorie pour les actualités

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../FORM_ADD_OPTION.md` (section "Spécification -- Article").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Formulaires enrichis — Page / Réalisations / Actualités
**Slug** : `news-categories`

## Objectif

Ajouter au formulaire Actualités (colonne de droite) : sélection d'une catégorie existante (liste) + création d'une nouvelle catégorie directement depuis le formulaire.

## Portée / dépendances

Dépend de 37 (réorganisation du formulaire en 2 colonnes, où la catégorie s'affiche à droite). Nouvelle entité `Category` (`name`, `slug`) + relation avec `NewsArticle` — la spec emploie "liste" au singulier (contrairement aux tags "à sélectionner" au pluriel de 38) : parti pris many-to-one, une seule catégorie par actualité (voir "Notes ouvertes" de `../MAIN.md`, à confirmer au démarrage si ambigu). Alimente également le filtre optionnel du block builder 50 (`builder-module-news-list`).

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

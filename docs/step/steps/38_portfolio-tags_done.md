# Étape 38 — Tags pour les réalisations

> Statut : **done** (v0.30.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../FORM_ADD_OPTION.md` (section "Spécification -- Réalisation").

**Fonctionnalité** : Formulaires enrichis — Page / Réalisations / Actualités
**Slug** : `portfolio-tags`

## Objectif

Ajouter au formulaire Réalisations (colonne de droite) : sélection de tags existants (multi-sélection) + création d'un nouveau tag directement depuis le formulaire.

## Portée / dépendances

Dépend de 37 (réorganisation du formulaire en 2 colonnes, où les tags s'affichent à droite). Nouvelle entité `Tag` (`name`, `slug`) + relation many-to-many avec `PortfolioItem` (voir "Notes ouvertes" de `../MAIN.md` sur le choix many-to-many vs many-to-one). Alimente également le filtre optionnel du block builder 51 (`builder-module-portfolio-list`).

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

Livrée avec 37 et 39 en un seul commit/version (0.30.0) — voir `docs/RELEASE.md` pour le détail complet, y compris un bug réel trouvé et corrigé (un PATCH partiel de type "archiver" aurait silencieusement vidé les tags d'un élément).

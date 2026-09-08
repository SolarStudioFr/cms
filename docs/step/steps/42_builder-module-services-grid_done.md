# Étape 42 — Module Grille des services

> Statut : **done** (v0.31.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Grille des services").

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-services-grid`

## Objectif

Affiche les services proposés par l'entreprise. Formulaire : eyebrow, titre de section, texte d'introduction (lead), liste des services ajoutée dynamiquement (bouton "Ajouter un service" : icône avec recherche, titre, description).

## Portée / dépendances

Dépend de 10 (`builder-core`) et du média picker/sélecteur d'icônes (recherche) à concevoir au démarrage. Fait partie du plugin builder existant. Partage potentiellement la forme de carte (icône + titre + description) avec 48 (`builder-module-service-card`) — un sous-composant commun sera évalué au démarrage de 48, une fois 42 livré (voir "Notes ouvertes" de `../MAIN.md`).

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

Livrée avec 40, 41, 43 à 45 en un seul commit/version (0.31.0) — voir `docs/RELEASE.md` pour le détail complet. Sélecteur d'icônes livré comme un composant `IconPicker` réutilisable (`plugin/page_builder/assets/modules/IconPicker.jsx`), pas un composant du média picker : une recherche texte sur une liste organisée d'icônes `react-icons/bs`, pas un fichier uploadé — la carte de service partagée avec 48 reste à trancher au démarrage de cette étape-là, comme prévu dans `../MAIN.md`.

# Étape 46 — Module Titre

> Statut : **done** (v0.32.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Titre").

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-heading`

## Objectif

Affiche un titre avec sélection du niveau H1 à H6. Formulaire : bouton de sélection du niveau de titre (H1 à H6), texte du titre.

## Portée / dépendances

Dépend de 10 (`builder-core`). Fait partie du plugin builder existant. Module simple, sans dépendance à un autre plugin de contenu.

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

Livrée avec 47 à 51 en un seul commit/version (0.32.0) — voir `docs/RELEASE.md` pour le détail complet. `level` (H1-H6) mis en liste blanche côté `render()` avant d'être interpolé dans le nom de la balise générée.

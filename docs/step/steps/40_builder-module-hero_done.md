# Étape 40 — Module Héro

> Statut : **done** (v0.31.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Héro").

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-hero`

## Objectif

Ajoute un héro sur le site. Formulaire : eyebrow (texte au-dessus du titre), titre principal (h1), texte d'accroche (lead), bouton principal (texte + URL), bouton secondaire (texte + URL).

## Portée / dépendances

Dépend de 10 (`builder-core`). Fait partie du plugin builder existant (`plugin/page_builder`), pas un nouveau plugin — même registre de modules que 11-15.

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

Livrée avec 41 à 45 en un seul commit/version (0.31.0), lot autorisé explicitement par l'utilisateur ("Fait les étapes de 40 à 45") — voir `docs/RELEASE.md` pour le détail complet.

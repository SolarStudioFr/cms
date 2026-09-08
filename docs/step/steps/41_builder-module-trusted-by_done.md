# Étape 41 — Module "Ils nous font confiance"

> Statut : **done** (v0.31.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Ils nous font confience").

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-trusted-by`

## Objectif

Ajoute une liste de clients. Formulaire : texte d'introduction, liste de clients/logos texte ajoutée dynamiquement (bouton "Ajouter un client").

## Portée / dépendances

Dépend de 10 (`builder-core`). Fait partie du plugin builder existant.

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

Livrée avec 40 et 42 à 45 en un seul commit/version (0.31.0) — voir `docs/RELEASE.md` pour le détail complet. "Clients/logos texte" lu comme une liste de noms de clients en texte brut (pas d'upload d'image via le média picker), conformément à la lecture actée dans `../MAIN.md`.

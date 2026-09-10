# Étape 48 — Module Service (carte seule)

> Statut : **done** (v0.32.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Service (Carte seule)").

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-service-card`

## Objectif

Comme "Grille des services" (42) mais avec une seule carte. Formulaire : couleur (liste : primary, secondary, success, warning, danger, info, light, dark), icône (recherche et liste), titre, description.

## Portée / dépendances

Dépend de 10 (`builder-core`) et de 42 (`builder-module-services-grid`) — même forme de carte (icône + titre + description), sous-composant partagé à évaluer à ce moment plutôt qu'une duplication (voir "Notes ouvertes" de `../MAIN.md`). Ajoute une couleur de fond (liste Bootstrap) que 42 n'a pas au niveau de la carte individuelle — à vérifier si 42 doit être aligné en retour.

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

Livrée avec 46, 47, 49 à 51 en un seul commit/version (0.32.0) — voir `docs/RELEASE.md` pour le détail complet. Le sous-composant partagé évoqué en "Portée / dépendances" a été extrait (`modules/serviceCard.js`) et `ServicesGridModule` (42) a été mis à jour pour le consommer, sortie HTML inchangée. 42 n'a pas été aligné en retour sur la couleur de fond ajoutée ici (non demandé pour 42).

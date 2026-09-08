# Étape 44 — Module Réalisations (dynamique)

> Statut : **done** (v0.31.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Réalisations (dynamique)").

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-portfolio-feed`

## Objectif

Affiche dynamiquement les dernières réalisations. Formulaire : eyebrow, titre de section, nombre à afficher, texte du lien "Tout voir", fond de section (liste : blanc ou gris doux).

## Portée / dépendances

Dépend de 10 (`builder-core`) et du plugin Réalisations (17-18, pour la liste des réalisations publiées). **Première étape à devoir résoudre le rendu dynamique du builder** : celui-ci produit aujourd'hui du HTML figé à l'enregistrement (`renderToHtml()`), incompatible avec un contenu qui doit refléter les *dernières* réalisations à chaque affichage de la page publique, pas seulement au moment où l'admin a sauvegardé — le mécanisme (ex. placeholder dans le HTML statique, hydraté côté public à l'affichage) est à concevoir au démarrage de cette étape (voir "Notes ouvertes" de `../MAIN.md`). 45/50/51 réutiliseront ensuite ce mécanisme.

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

Livrée avec 40 à 43, 45 en un seul commit/version (0.31.0) — voir `docs/RELEASE.md` pour le détail complet. Mécanisme de rendu dynamique conçu ici (placeholder `[data-builder-feed]` dans le HTML statique, hydraté côté thème public par `template/default/assets/builderFeedHydrator.js`/`BuilderContent.jsx`) — voir la section dédiée de `docs/RELEASE.md` pour le détail, y compris un bug de timing réel trouvé et corrigé pendant la vérification (référence de nœud DOM capturée avant un `await` devenue obsolète). Réutilisé tel quel par 45.

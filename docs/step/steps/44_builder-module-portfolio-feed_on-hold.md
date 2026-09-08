# Étape 44 — Module Réalisations (dynamique)

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Réalisations (dynamique)").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-portfolio-feed`

## Objectif

Affiche dynamiquement les dernières réalisations. Formulaire : eyebrow, titre de section, nombre à afficher, texte du lien "Tout voir", fond de section (liste : blanc ou gris doux).

## Portée / dépendances

Dépend de 10 (`builder-core`) et du plugin Réalisations (17-18, pour la liste des réalisations publiées). **Première étape à devoir résoudre le rendu dynamique du builder** : celui-ci produit aujourd'hui du HTML figé à l'enregistrement (`renderToHtml()`), incompatible avec un contenu qui doit refléter les *dernières* réalisations à chaque affichage de la page publique, pas seulement au moment où l'admin a sauvegardé — le mécanisme (ex. placeholder dans le HTML statique, hydraté côté public à l'affichage) est à concevoir au démarrage de cette étape (voir "Notes ouvertes" de `../MAIN.md`). 45/50/51 réutiliseront ensuite ce mécanisme.

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

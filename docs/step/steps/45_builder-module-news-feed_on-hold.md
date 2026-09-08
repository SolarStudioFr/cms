# Étape 45 — Module Actualités (dynamique)

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Actualités (Dynamique)").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-news-feed`

## Objectif

Affiche dynamiquement les dernières actualités. Formulaire : eyebrow, titre de section, nombre à afficher, texte du lien "Tout voir", fond de section (blanc ou gris doux).

## Portée / dépendances

Dépend de 10 (`builder-core`), du plugin Actualités (19-20, pour la liste des actualités publiées) et du mécanisme de rendu dynamique conçu à l'étape 44 (`builder-module-portfolio-feed`) — même besoin, à réutiliser tel quel plutôt qu'à reconcevoir.

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

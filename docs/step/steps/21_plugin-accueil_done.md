# Étape 21 — Page d'accueil

> Statut : **done**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée/ajustée sur demande explicite de l'utilisateur (hors backlog initial de `../../INIT_ETAPES.md`, ou description substantiellement précisée par rapport au backlog).

**Fonctionnalité** : Gestionnaires de contenu (plugins)
**Slug** : `plugin-accueil`

## Objectif

Configuration du contenu de la page d'accueil (probablement construite avec le builder si actif, sinon avec l'éditeur générique) **et son rendu public** — les deux font partie de cette même étape/plugin.

## Portée / dépendances

Dépend de 09 (éditeur générique) et bénéficie de 10 (`builder-core`) et des modules retenus pour la home si le builder est actif.

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
- [x] 8. Rapport à l'utilisateur + validation avant l'étape suivante (dérogation : lot 17-21 autorisé sans attente, voir mémoire)

## Journal de l'étape

Voir `docs/RELEASE.md` (0.21.0). Singleton `HomeContent` + rendu public sur `/`. A aussi trouvé et corrigé un vrai bug ApiPlatform (`ReadListener` qui saute le provider sur un `Patch` sans `{id}` dans l'URI).

# Étape 16 — Intégration du builder dans le plugin page

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Issu du backlog en section 5 de `../../INIT_ETAPES.md`.
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Éditeur de contenu (drag & drop, page builder — plugin unique)
**Slug** : `builder-page-integration`

## Objectif

Amélioration du plugin `page` existant (`plugin/page/`) pour qu'il utilise le builder à la place du formulaire titre/contenu actuel.

## Portée / dépendances

Dépend de 10 (`builder-core`) et des modules jugés indispensables pour le remplacement (parmi 11-15). Fait partie du plugin unique du builder. Tant que ce plugin n'est pas installé/activé, `plugin/page` continue d'utiliser l'éditeur générique (09).

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

Fait en version 0.16.0. Voir `docs/RELEASE.md` — premier vrai point d'intégration du builder, a validé rétroactivement les étapes 10-15 en navigateur réel et trouvé/corrigé deux bugs réels (formulaire imbriqué soumis par erreur, contenu HTML affiché en texte échappé côté public) plus un piège de cache d'environnement `test`. Dernière étape de l'enchaînement 06-16 autorisé par l'utilisateur (session du 2026-09-04).

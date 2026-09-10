# Étape 47 — Module Bouton

> Statut : **done** (v0.32.0). Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../NEW_BLOCK_PAGE_BUILDER.md` (section "Bouton").

**Fonctionnalité** : Page builder (plugin `page_builder`) — nouveaux blocks
**Slug** : `builder-module-button`

## Objectif

Ajoute un bouton. Formulaire : texte du bouton, URL, couleur (liste : primary, secondary, success, warning, danger, info, light, dark), taille (liste : petit, moyen, grand), cible (liste : même onglet, nouvel onglet).

## Portée / dépendances

Dépend de 10 (`builder-core`). Fait partie du plugin builder existant. Le rendu bouton (couleur/taille/cible) pourra être extrait en sous-composant réutilisable par d'autres modules du builder (Héro 40, CTA existant étape 14) — à évaluer au démarrage sans complexifier ce module lui-même s'il n'y a pas de gain clair.

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

Livrée avec 46, 48 à 51 en un seul commit/version (0.32.0) — voir `docs/RELEASE.md` pour le détail complet. Non extrait en sous-composant partagé avec le CTA (14)/Héro (40) comme envisagé dans "Portée / dépendances" ci-dessus : leurs schémas de style diffèrent trop pour qu'un partage simplifie quoi que ce soit. Utilise directement les classes Bootstrap natives (`btn btn-{couleur} btn-{sm,lg}`) plutôt qu'une classe `builder-*` maison.

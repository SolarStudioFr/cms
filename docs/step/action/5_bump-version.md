# Action 5 — Monter la version du projet

> Fait partie du protocole en 8 actions (section 3 de `../../INIT_ETAPES.md`), à
> appliquer identiquement pour chaque étape de `../MAIN.md`. Ce fichier documente le
> protocole lui-même, pas une instance pour une étape donnée.

## Description

Incrémenter la version du projet dans `cms/composer.json` (clé `version`) et `cms/.env` (`VERSION`). Les deux valeurs doivent rester synchronisées.

## Notes

Semver : incrément mineur pour une nouvelle fonctionnalité/étape, patch pour un correctif au sein d'une étape déjà livrée. Version actuelle au moment de la planification : 0.1.0.

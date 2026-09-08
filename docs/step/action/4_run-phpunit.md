# Action 4 — Tester avec PHPUnit

> Fait partie du protocole en 8 actions (section 3 de `../../INIT_ETAPES.md`), à
> appliquer identiquement pour chaque étape de `../MAIN.md`. Ce fichier documente le
> protocole lui-même, pas une instance pour une étape donnée.

## Description

Faire passer la suite PHPUnit. Aucune dépréciation tolérée — cohérent avec `failOnDeprecation` déjà activé dans `cms/phpunit.dist.xml`.

## Notes

Commandes : `make test` (crée/migre/seed la base `app_test` puis lance PHPUnit dans le conteneur php), ou en ciblé depuis `make terminal` : `php cms/bin/phpunit cms/tests/Path/To/SomeTest.php` ou `php cms/bin/phpunit --filter testMethodName`.

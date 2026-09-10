# Étape 58 — Sélecteur de langue dans les formulaires de contenu

> Statut : **done**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "i18n", puce "Ajouter dans les formulaires du site, le multi language"). **Point explicitement signalé par l'utilisateur comme sujet à question** ("Il est important que i18n n'est pas de dépendance avec un plugin ou qu'un plugin est une dépendance avec i18n. Si tu as le moindre doute pose moi des questions.") — design confirmé avec l'utilisateur (stockage overlay générique + sélecteur public lié à l'interface) avant de démarrer, voir `docs/RELEASE.md` 0.39.0.

**Fonctionnalité** : Refonte du multilinguisme (i18n)
**Slug** : `i18n-content-language-picker`

## Objectif

Permettre de choisir une langue dans les formulaires d'ajout de contenu (Page, Réalisations, Actualités, Accueil), pour éditer ce contenu dans plusieurs langues — c'est la **langue du contenu**, pas celle de l'interface (voir la distinction posée en tête de la section "Refonte du multilinguisme" de `../MAIN.md`). Contrainte explicite : ne pas implémenter cela dans chaque plugin, mais via un menu/composant partagé ; i18n ne doit dépendre d'aucun plugin, et aucun plugin ne doit dépendre d'i18n.

## Portée / dépendances

Dépend de 52 (i18n dans `cms/*`, entité `Lang`). **Mécanisme exact non tranché, à concevoir avec l'utilisateur au démarrage** (voir "Notes ouvertes" de `../MAIN.md`) — piste de départ : un composant partagé exposé par l'admin host via Module Federation (même principe que `MediaPicker`/`RichTextEditor`, étape 06), associé à un stockage générique côté i18n indexé par `(type d'entité, id, champ, locale)` plutôt que des tables spécifiques par plugin de contenu — à valider avant d'écrire le moindre code. Indépendante des étapes 53-57 (langue de l'interface) : aucune dépendance fonctionnelle entre les deux sujets.

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

Voir `docs/RELEASE.md` 0.39.0. Résumé : `App\Entity\ContentTranslation` (overlay générique) + `App\Service\ContentTranslator` (appelé par chaque provider public avec sa propre liste de champs) + `ContentTranslationPanel.jsx` (composant partagé via Module Federation, intégré dans les 4 formulaires de contenu) + sélecteur public déjà existant (étape 56) qui pilote désormais aussi la langue du contenu affiché.

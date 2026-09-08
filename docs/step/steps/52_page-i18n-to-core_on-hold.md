# Étape 52 — Page et i18n rejoignent le cœur

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../REFORGE_PLUGINS_AND_I18N.md` (section "Plugin Page et i18n").
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Restructuration — Page et i18n rejoignent le cœur (`cms/*`)
**Slug** : `page-i18n-to-core`

## Objectif

`plugin/page` et `plugin/i18n` cessent d'être des plugins et rejoignent `cms/*` : plus de `plugin.json`, plus de remote Module Federation, plus de build Webpack dédié. Leur UI admin devient une page statique du cœur (même famille que `UserManager`/`SiteConfig`), chargée avec le reste de l'entrée `adm` plutôt que dynamiquement.

## Portée / dépendances

À adapter pour les deux plugins :
- `cms/composer.json` (autoload PSR-4 `Plugin\Page\`/`Plugin\I18n\` → suppression, code déplacé sous `App\` dans `cms/src/`).
- `cms/config/packages/doctrine.yaml` (mappings `PluginPage`/`PluginI18n` → fusionnés dans le mapping `App` par défaut).
- `cms/config/packages/doctrine_migrations.yaml` (namespaces `Plugin\Page\Migrations`/`Plugin\I18n\Migrations` → migrations déplacées vers `cms/migrations`, en conservant l'historique déjà appliqué en base — ne pas recréer les tables `page`/`lang`/`translation`).
- `cms/config/services.yaml` (suppression des imports `Plugin\Page\`/`Plugin\I18n\`, déjà couverts par `App\`).
- Suppression de `plugin/page/plugin.json` + `webpack.config.js`, `plugin/i18n/plugin.json` + `webpack.config.js` ; retrait de `plugin/page`/`plugin/i18n` de `package.json` (`build:plugins`).
- Admin : `PageForm.jsx`/`PageList.jsx` et `LangManager.jsx`/`TranslationManager.jsx` deviennent des pages statiques de `cms/assets/adm/pages/`, routées en dur dans `App.jsx`/nav (comme `UserManager`), au lieu d'être chargées via `usePlugins.js`.
- Le formulaire Page consomme toujours le builder (`plugin/page_builder`, qui reste un vrai plugin, non concerné par cette étape) via le remote Module Federation existant — à vérifier que cette consommation continue de fonctionner une fois Page elle-même statique plutôt que dynamiquement chargée.
- Voir "Notes ouvertes" de `../MAIN.md` : interprétation retenue ("déplacer dans CMS" = fusion complète dans le namespace `App\`) à confirmer au démarrage.

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

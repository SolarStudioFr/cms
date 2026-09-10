# Journal des releases

Une section par version, dans l'ordre chronologique. Voir `docs/step/action/6_log-release.md` pour le protocole.

## 0.2.0 — Étape 01 : `file-storage-backend`

Backend générique de fichiers (`cms/src/*`, pas un plugin) : entity `File`, pipeline d'image (GD), stockage sur disque.

**Réalisé**
- `App\Entity\File` (`cms/src/Entity/File.php`) + `App\Entity\FileType` (enum `img`/`pdf`/`zip`), `FileRepository`, migration `Version20260904120000` (table `file`).
- `App\Service\ImageProcessor` : conversion webp + génération de 4 miniatures (256/512/1024/2048px) via l'extension GD, sans upscale au-delà de la largeur source.
- `App\Service\FileUploadService::upload()` : détection du type par mime, arborescence `upload/img/{source,webp,thumbnail}`, `upload/pdf`, `upload/zip`, nommage `{Y-m-d}_{h-i-s}_{slug}.{ext}` (source) / `{Y-m-d}_{h-i-s}_{uniqid}_{slug}.webp` (webp/miniatures) / `{Y-m-d}_{h-i-s}_{uniqid}_{slug}.{ext}` (pdf/zip), conforme à `docs/step/steps/01_file-storage-backend_on-hold.md`.
- `FileUploadService::resolveUrl()` : logique de repli vers une image par défaut (`/build/images/placeholder.svg`, copiée par Encore depuis `cms/assets/images/`) quand le fichier référencé n'existe plus sur disque.
- Nouveau paramètre `upload.dir` (`services.yaml`), lié à `%kernel.project_dir%/../upload`.
- Tests PHPUnit (`cms/tests/Service/FileUploadServiceTest.php`) : upload image (webp + 4 miniatures, pas d'upscale), upload PDF, type non supporté rejeté, fallback placeholder.

**Changements structurels**
- `docker/php/Dockerfile` : ajout de l'extension `gd` (compilée avec `--with-webp --with-jpeg --with-freetype`) — l'image PHP de base n'avait ni GD ni Imagick. Nécessite un rebuild du conteneur `php` (`docker compose build php`) pour quiconque a déjà l'environnement en place.
- `webpack.config.js` (racine) : ajout de `.copyFiles({ from: './cms/assets/images', to: 'images/[path][name].[ext]' })` pour publier l'image de repli sous `/build/images/`, indépendamment des deux entrées JS.

**Erreurs rencontrées**
- `exif_imagetype()` n'est pas disponible sans l'extension `exif` (non installée) : remplacé par `getimagesize()[2]` (constante `IMAGETYPE_*`), qui ne dépend que de GD.
- `FileUploadService` n'ayant encore aucun consommateur (pas d'API/contrôleur avant l'étape 02), le compilateur de service Symfony le retire comme inutilisé même en environnement de test — `self::getContainer()->get(FileUploadService::class)` échoue avec « removed or inlined ». Contournement : le test construit le service directement (`new FileUploadService(...)`) plutôt que de le résoudre depuis le container.

**Décisions**
- GD plutôt qu'Imagick : suffisant pour webp + redimensionnement, évite une dépendance supplémentaire.
- `size` sur `File` correspond à la taille du fichier original envoyé (pas à la taille du webp optimisé généré).
- Le dossier `upload/img/original/` déjà présent (vide, non suivi par git) n'a pas été supprimé mais n'est pas utilisé — l'arborescence réelle suit la spec (`upload/img/source/`).
- Étape volontairement limitée au backend : pas d'endpoint API ni d'UI admin (prévus aux étapes 02/03).

## 0.3.0 — Étape 02 : `file-manager-admin-ui`

Interface admin (liste / upload / suppression) au-dessus du backend de l'étape 01.

**Réalisé**
- `App\Controller\Admin\FileController` (`GET|POST /api/admin/files`, `DELETE /api/admin/files/{id}`) : contrôleur simple (pas une ressource API Platform — l'upload multipart s'y prête mal), même schéma que `PluginController`, protégé par la règle `access_control` existante `^/api/admin` (`ROLE_SUPER_ADMIN`), pas de sécurité par route à ajouter.
- `FileUploadService::remove()` : supprime les fichiers sur disque (source/webp/miniatures) puis l'entity — réutilisé tel quel par le futur bouton de nettoyage (étape 04).
- Page admin React `cms/assets/adm/pages/FileManager.jsx` (grille de miniatures, upload, suppression), route `/files` + entrée de nav statique dans `Sidebar.jsx`/`App.jsx` — pas un plugin, donc ajoutée directement dans `cms/assets/adm` comme `Dashboard`, sans Module Federation.
- Tests : `cms/tests/Api/FileAdminApiTest.php` (cycle complet upload/liste/suppression en HTTP réel via `WebTestCase`, rejet 422 sur type non supporté, 401 anonyme).
- Vérification manuelle en HTTP réel (curl, en plus des tests fonctionnels) : login, upload, listing, accès public au fichier servi, suppression, et chargement de `/adm/files` + du bundle JS compilé.

**Erreurs rencontrées**
- Upload à 500 (« Unable to write in the .../img/source directory ») en conditions réelles (apache→php-fpm) alors que les tests PHPUnit passaient : les workers php-fpm tournent en `www-data`, alors que `docker compose exec` (utilisé pour lancer les tests/migrations) tourne en `root` — les sous-dossiers `upload/img/{source,webp,thumbnail}` créés pendant les tests appartenaient donc à `root` sans droit d'écriture pour `www-data`. `FileUploadService`/`ImageProcessor::ensureDirectory()` force maintenant `chmod 0777` sur les dossiers qu'elle vient de créer (uniquement ceux-là, pas les dossiers déjà existants — un `chmod` sur un dossier dont on n'est pas propriétaire échoue). Dossiers déjà présents corrigés une fois à la main (`chmod -R 0777 upload/`). Point de vigilance pour les étapes suivantes : toute nouvelle sous-arborescence sous `upload/` doit passer par ce même `ensureDirectory()`.

**Décisions**
- Le JSON retourné par `FileController` résout déjà chaque chemin stocké en URL utilisable (via `FileUploadService::resolveUrl()`), miniatures incluses — le frontend n'a pas à reconstruire cette logique.

## 0.4.0 — Étape 03 : `media-picker-component`

Modal réutilisable de sélection/ajout de fichier, paramétrable par type.

**Réalisé**
- `GET /api/admin/files` accepte désormais un paramètre `type` (ex. `?type=img` ou `?type=pdf,zip`) — `FileRepository::findAllOrderedByCreatedAt()` prend une liste optionnelle de `FileType`. Testé en HTTP réel (`FileAdminApiTest::testListingCanBeFilteredByType`) et via `curl`.
- Extraction de deux composants React partagés entre la page plein écran (étape 02) et la nouvelle modal : `components/FileGrid.jsx` (grille de vignettes + action par carte, injectée par l'appelant) et `components/FileUploadForm.jsx` (formulaire d'envoi, `accept` HTML dérivé des types autorisés). `pages/FileManager.jsx` a été réécrite pour les réutiliser plutôt que dupliquer sa propre logique de grille/upload.
- `components/MediaPicker.jsx` : modal react-bootstrap contrôlée (`show`/`onHide`, comme l'API standard de `Modal`), paramètres `types` (ex. `['img']` ou `['pdf', 'zip']`) et `onSelect(file)`. Upload et sélection d'un fichier existant appellent tous deux `onSelect` puis ferment la modal.

**Non fait / reporté**
- Pas de consommateur réel intégré dans cette étape : les futurs consommateurs listés dans `MAIN.md` (modules du builder — étapes 10-16 — et plugins de contenu — 17/19/21) n'existent pas encore. `MediaPicker` a donc été vérifié par revue de code, compilation Webpack réussie, et test bout-en-bout du filtrage côté API, mais **pas cliqué dans un navigateur** faute d'écran réel où l'ouvrir — à vérifier visuellement dès son premier vrai point d'intégration.
- Partage du composant avec les plugins chargés en Module Federation (`plugin/*`) non traité : `MediaPicker` vit dans `cms/assets/adm/components/` (bundle admin), pas exposé via le remote MF de l'hôte. À concevoir quand un plugin en aura réellement besoin (probablement : ajouter `exposes` au `ModuleFederationPlugin` de l'hôte dans `webpack.config.js`, comme le fait déjà chaque plugin remote côté `exposes`).

## 0.5.0 — Étapes 04 + 05 : `file-cleanup-unused` et `file-reoptimize-images`

Deux boutons/actions admin distincts (conformément à `MAIN.md`), livrés dans un même commit — voir « Décisions » ci-dessous pour la justification de cette dérogation au protocole (un commit par étape).

**Réalisé — 04 (`file-cleanup-unused`)**
- `App\Service\FileUsageCheckerInterface` (`getUsedFileIds(): iterable<int>`), taguée `app.file_usage_checker` via `#[AutoconfigureTag]` — point d'extension pour tout futur plugin référençant des fichiers (builder, actualités, réalisations, accueil, config site). Aucune implémentation n'existe encore dans le code, conformément à la note de `MAIN.md` : « la détection fiable suppose la présence de la plupart des consommateurs de fichiers ». Résultat actuel assumé : **tout fichier compte aujourd'hui comme non utilisé**, tant qu'aucun checker n'est enregistré.
- `App\Service\FileCleanupService::removeUnused()` : union des ids rapportés par les checkers enregistrés (`#[AutowireIterator('app.file_usage_checker')]`), supprime tout le reste via `FileUploadService::remove()`.
- `POST /api/admin/files/cleanup-unused` (`FileController::cleanupUnused()`) → `{deleted: N}`. Bouton « Supprimer les fichiers non utilisés » dans `FileManager.jsx` (confirmation navigateur avant envoi).

**Réalisé — 05 (`file-reoptimize-images`)**
- `App\Service\ImageReoptimizeService::reoptimizeAll()` : régénère le webp + les 4 miniatures de chaque image à partir de son `source`, **en conservant les noms de fichiers déjà stockés** (pas de nouveau `uniqid`/timestamp) pour ne pas casser les URLs déjà distribuées ailleurs. Images dont le `source` est manquant sur disque : ignorées, comptées à part (`skipped`).
- `FileUploadService::toAbsolutePath()` passée de `private` à `public` pour être réutilisée par ce service (2 appelants désormais — seuil qui justifie l'accès partagé plutôt qu'une duplication).
- `POST /api/admin/files/reoptimize-images` → `{reoptimized: N, skipped: N}`. Bouton « Ré-optimiser toutes les images » dans `FileManager.jsx`.

**Tests**
- `FileAdminApiTest::testCleanupUnusedDeletesEveryFileSinceNoUsageCheckerIsRegisteredYet` et `::testReoptimizeImagesRegeneratesMissingThumbnails` (HTTP réel, `WebTestCase`).
- Vérification manuelle en HTTP réel (curl) : upload → reoptimize-images → cleanup-unused → listing vide, sur l'app réellement démarrée (pas seulement le client de test Symfony).

**Erreurs rencontrées**
- `ImageReoptimizeService` : oubli du `use App\Repository\FileRepository;` — le type-hint non qualifié résolvait vers `App\Service\FileRepository` (inexistant) dans le namespace du fichier, détecté par `cache:clear` (« Cannot autowire... this class was not found »).

**Décisions**
- **Dérogation au protocole** : 04 et 05 livrés dans un seul commit/une seule entrée de version alors que ce sont deux étapes distinctes (`MAIN.md` : « deux boutons/actions distincts, pas une seule étape »). Justification : elles partagent les mêmes fichiers (`FileController.php`, `FileManager.jsx`, `FileAdminApiTest.php`) de façon entrelacée au niveau des lignes ; les séparer aurait exigé un staging partiel artificiel sans bénéfice réel (aucune des deux ne dépend fonctionnellement de l'autre, ni sur le plan technique ni du point de vue de l'utilisateur qui verra les deux boutons apparaître ensemble). Les deux restent documentées et testées séparément dans cette même entrée.
- Le mécanisme de checkers d'usage (04) est délibérément livré vide/inerte : c'est le point d'extension prévu, pas un report de travail — le brancher pour de vrai n'a de sens qu'une fois un plugin consommateur de fichiers construit (builder, réalisations, actualités, accueil...).

## 0.6.0 — Étape 06 : `plugin-manager-admin`

Page d'administration listant les plugins détectés, avec activation/désactivation et suppression.

**Réalisé**
- `App\Entity\PluginState` (id, `name` unique, `enabled`) + migration `Version20260904140000` — un plugin sans ligne est considéré actif par défaut (`PluginRegistry::isEnabled()`), pour ne pas exiger de seed au premier scan.
- `PluginRegistry` étendu : `getAllPlugins()` (manifeste + état, pour l'admin), `getActivePlugins()` (inchangé côté contrat consommé par `usePlugins.js` — actifs uniquement, sans le champ `enabled`), `setEnabled()`, `delete()` (suppression récursive du dossier du plugin + de sa ligne d'état ; `basename()` sur le nom + re-vérification d'un vrai `plugin.json` pour rejeter un traversal de chemin via le paramètre de route).
- `PluginController` étendu : `GET /api/admin/plugins/all`, `PATCH /api/admin/plugins/{name}` (`{"enabled": bool}`), `DELETE /api/admin/plugins/{name}`.
- Page admin React `pages/PluginManager.jsx` (statique, pas un plugin), route `/plugins` + entrée de nav.

**Tests**
- `PluginAdminApiTest` (HTTP réel) : bascule actif/inactif + effet sur le listing filtré, suppression d'un plugin (dossier jetable créé/détruit par le test — **jamais** le vrai plugin `page`), 401 anonyme.
- **Vérification en navigateur réel** (Playwright/Chromium headless, installé localement pour cette session — voir « Outillage » ci-dessous) : login → nav « Plugins » → « Pages » listé Actif → clic Désactiver → passe à Désactivé → clic Activer → repasse à Actif. Premher vrai test de bout en bout cliqué dans un navigateur sur ce projet (les étapes précédentes s'arrêtaient à la compilation Webpack + tests API).

**Outillage**
- `playwright-core` + un binaire Chromium installés localement (`npm install --no-save`, `npx playwright install chromium`) pour permettre une vérification UI réelle plutôt que la seule compilation — pas ajouté à `package.json`/`package-lock.json` (installation non committée, propre à cette session de travail). Redevient nécessaire de le réinstaller (`npx playwright install chromium`) dans un nouvel environnement si une vérification similaire est souhaitée plus tard.

**Décisions**
- Suppression physique implémentée telle que demandée (`MAIN.md`) malgré le caractère destructif : gardée derrière `ROLE_SUPER_ADMIN` + confirmation navigateur, jamais exercée sur un vrai plugin pendant les tests/vérifications (dossier jetable dédié).

## 0.7.0 — Étape 07 : `i18n-plugin-core`

Squelette du plugin multilangue, suivant la même logique que `plugin/page/` (entité/API dans `plugin/i18n/src/`, UI admin en Module Federation).

**Réalisé**
- `Plugin\I18n\Entity\Lang` (`code`, `label`, `active`) + `LangRepository`, `ApiResource` admin (`/admin/langs`, CRUD complet, `ROLE_SUPER_ADMIN`) et publique (`GET /langs`, actives uniquement, via `ActiveLangCollectionProvider` — même schéma que `PublishedPageCollectionProvider` du plugin Page).
- Migration `plugin/i18n/migrations/Version20260904150000` : crée `lang` **et** insère FR + EN (actives) — seed demandé par `MAIN.md` fait directement en migration plutôt que via une fixture (les fixtures ne tournent qu'en dev/test, la migration s'applique partout).
- Câblage plugin (comme `plugin/page/`) : mapping Doctrine (`PluginI18n`), namespace de migrations (`Plugin\I18n\Migrations`), autoload PSR-4 composer + `composer update --lock` (le hash du lock dépend aussi du champ `version` de `composer.json`, pas seulement de `require` — a nécessité de refaire `--lock` après le bump de version), service DI (`Plugin\I18n\: resource: ...`).
- UI admin `plugin/i18n/assets/LangManager.jsx` (liste + ajout inline + activer/désactiver + suppression — un seul composant, l'entité est trop simple pour justifier un flux liste/formulaire séparé comme `Page`), remote MF `plugin/i18n/webpack.config.js` (copie du gabarit `plugin/page/webpack.config.js`, nom `i18n`), `plugin.json`. `package.json` `build:plugins` étendu pour builder aussi ce remote.

**Tests**
- `LangAdminApiTest` (HTTP réel) : seed FR/EN présent, cycle CRUD admin complet, listing public filtré aux langues actives, 401 anonyme.
- **Vérification en navigateur réel** (Playwright) : login → nav « Langues » (chargée dynamiquement depuis le nouveau remote MF, donc valide aussi le pipeline de découverte de plugin de bout en bout) → fr/en listées → ajout « de » → désactivation → suppression → retour à fr/en.

**Décisions**
- Pas de champ « langue par défaut » sur `Lang` : rien dans l'étape 07 ni 08 n'en a explicitement besoin pour l'instant (PO travaille par locale, pas par rapport à une langue source stockée en base) ; à ajouter si un besoin concret apparaît.

## 0.8.0 — Étape 08 : `i18n-translation-management`

Gestion des traductions en admin (contenu et/ou interface, `domain` libre) + export/import PO + intégration Twig côté rendu public.

**Réalisé**
- `Plugin\I18n\Entity\Translation` (`lang` FK vers `Lang`, `domain` par défaut `messages`, `messageKey` — renommé pour éviter le mot réservé SQL `key`, `value`), contrainte unique `(lang_id, domain, message_key)`. Migration `Version20260904160000` (table + FK).
- `Plugin\I18n\Service\TranslationPoConverter` : s'appuie sur `Symfony\Component\Translation\Loader\PoFileLoader` / `Dumper\PoFileDumper` (déjà présents via `symfony/translation`, aucune nouvelle dépendance composer) — export vers une chaîne PO, import depuis un chemin de fichier vers un tableau clé => valeur.
- `TranslationController` (`plugin/i18n/src/Controller/Admin/`) : `GET/POST /api/admin/translations` (liste par lang+domain, upsert), `DELETE /api/admin/translations/{id}`, `GET /api/admin/translations/export` (fichier `.po` téléchargeable), `POST /api/admin/translations/import` (upload multipart `.po`).
- **Intégration rendu public** : `Plugin\I18n\Twig\TranslationExtension`, fonction Twig `i18n_trans(key, lang, domain='messages')` — repli sur la clé elle-même si aucune traduction n'existe. Auto-enregistrée comme extension Twig par le compiler pass de TwigBundle (aucune config supplémentaire, `Plugin\I18n\` est déjà autoconfiguré dans `services.yaml`). Volontairement minimal : pas de détection/routage de langue courante côté site public (non demandé par les étapes 07/08) — le gabarit appelant doit fournir explicitement la langue.
- UI admin `plugin/i18n/assets/TranslationManager.jsx` (sélection langue/domaine, édition inline avec sauvegarde au blur, ajout, suppression, export/import) — accessible depuis `LangManager` via un bouton « Traductions » plutôt qu'une deuxième entrée de nav (le plugin n'expose qu'un seul point d'entrée de nav).

**Tests**
- `TranslationAdminApiTest` (HTTP réel) : upsert (création + mise à jour idempotente), liste, suppression, export (contenu PO vérifié), import (PO multi-entrées vérifié en base), 401 anonyme. `TranslationExtensionTest` : repli sur la clé si absente, résolution correcte si présente.
- **Vérification en navigateur réel** (Playwright) : login → Langues → Traductions → ajout `hello`/`Bonjour` → édition inline vers `Salut` → rechargement de page (confirme la persistance, pas juste l'état React local) → export → fichier `.po` téléchargé et son contenu (`msgstr "Salut"`) vérifié.

**Décisions**
- `domain` est un champ texte libre côté admin (pas une liste fermée) : "messages" par défaut pour l'interface, mais un plugin de contenu peut choisir son propre domaine (ex. `page` ou `actualites`) sans modification du backend i18n — cohérent avec l'objectif « contenu et/ou interface » de l'étape.
- Pas de service de détection de la langue courante du visiteur (session/cookie/Accept-Language) : hors périmètre explicite de 07/08, à traiter si/quand un besoin de bascule de langue publique apparaît.

## 0.9.0 — Étape 09 : `content-editor-fallback`

Éditeur WYSIWYG simple, utilisé par défaut par tout plugin de contenu (pour l'instant : `plugin/page`) tant que le builder n'est pas actif. Première étape qui fait vraiment consommer à un plugin les composants partagés de l'hôte admin (`MediaPicker`, étape 03) — ce qui a nécessité de résoudre le partage inter-remotes Module Federation resté en suspens depuis l'étape 03.

**Réalisé**
- `cms/assets/adm/components/RichTextEditor.jsx` : wrapper fin autour de **Quill vanilla** (`npm install quill`, dépendance réelle) plutôt que `react-quill` — `react-quill` s'appuie sur `findDOMNode`, supprimé de React 19, et casserait au runtime. Barre d'outils volontairement modeste (titres, gras/italique/souligné, listes, citation, lien, image) pour rester un éditeur « simple », distinct du futur module Texte complet de l'étape 15. Le bouton image ouvre `MediaPicker` (filtré `img`) au lieu du prompt de fichier brut de Quill.
- **Partage host → plugin via Module Federation** (résout la limite notée dans l'entrée 0.4.0) : `webpack.config.js` (racine) donne à son `ModuleFederationPlugin` un `filename` (`admHostRemoteEntry.js`) et `exposes` (`MediaPicker`, `RichTextEditor`) — l'hôte devient aussi un conteneur MF exposé, pas seulement un hôte consommateur. `plugin/page/webpack.config.js` déclare `remotes: { adm_host: 'adm_host@/build/admHostRemoteEntry.js' }` (remote statique par URL, indépendant du chargement dynamique de plugins) ; `PageForm.jsx` consomme `adm_host/RichTextEditor` via `React.lazy`/`Suspense` (résolution de remote intrinsèquement asynchrone).
- `plugin/page/assets/PageForm.jsx` : le `<textarea>` du champ contenu est remplacé par `RichTextEditor`. `RichTextEditor` ne se monte qu'une fois `loading=false` (déjà le cas dans `PageForm`), donc sa valeur initiale est toujours la bonne — pas besoin de re-synchroniser `value` après le montage.

**Tests**
- Pas de nouveau code PHP : suite PHPUnit existante revérifiée verte (42 tests) sans ajout.
- **Vérification en navigateur réel** (Playwright), seule façon crédible de tester ce genre d'intégration : création d'une page → saisie + mise en forme (gras) dans l'éditeur → enregistrement → contenu HTML vérifié en base (`<p>Hello from Quill.<strong> Bold part.</strong></p>`) → réouverture de la page → contenu rechargé correctement dans l'éditeur → clic sur le bouton image de la barre d'outils → `MediaPicker` s'ouvre bien (preuve que le partage cross-remote fonctionne pour les deux composants, `RichTextEditor` **et**, en cascade, `MediaPicker` qu'il utilise en interne).
- Piège rencontré pendant la vérification : lancer `npx encore production` seul (plutôt que `npm run build` au complet) déclenche `cleanupOutputBeforeBuild()` qui vide tout `cms/public/build/`, effaçant au passage les remotes déjà construits des autres plugins (`i18n` a temporairement disparu, provoquant une 404/erreur JS sans rapport avec le vrai correctif) — toujours utiliser `npm run build` en entier pour vérifier un changement touchant webpack, jamais un build partiel.

**Décisions**
- `quill` ajouté comme dépendance npm normale (pas de flag `--no-save`) : contrairement à `playwright-core` (outillage de vérification propre à la session), c'est une dépendance runtime réelle du produit livré.

## 0.10.0 — Étape 10 : `builder-core`

Cœur du plugin builder (`plugin/builder/`) : canvas drag & drop, modèle de données JSON, registre de modules extensible. Pas de backend PHP du tout — voir « Décisions ».

**Réalisé**
- `plugin/builder/assets/modules/registry.js` : registre vide pour l'instant (`[]`), rempli par les étapes 11-15 — chaque entrée `{type, label, defaultProps, Edit, render}` associe un composant React d'édition et une fonction pure `render(props): string` (HTML public), sans rien changer au canvas ou au rendu quand un module est ajouté.
- `BuilderCanvas.jsx` : liste ordonnée de blocs, ajout (menu dérivé du registre), suppression, édition inline via le composant `Edit` du module, réordonnancement par glisser-déposer (API HTML5 `draggable`/`onDragOver`/`onDrop` native, pas de nouvelle dépendance npm). **Même contrat que `RichTextEditor`** (étape 09) : `value`/`onChange(string)` — un plugin de contenu peut le substituer à l'éditeur de secours sans changement de forme (préparé pour l'étape 16).
- `renderToHtml.js` : fonction pure (aucune dépendance React/DOM) qui convertit la valeur JSON du canvas en HTML, en déléguant à `render()` de chaque module présent dans le registre.
- Câblage plugin : `plugin.json`, `webpack.config.js` (remote MF `builder`, expose `AdminModule`/`BuilderCanvas`/`renderToHtml`, et déclare déjà `remotes: { adm_host }` pour que les futurs modules 11-13 puissent consommer `MediaPicker`), `build:plugins` étendu.
- `AdminModule.jsx` intentionnellement minimal (`navItem: null, routes: []`) : le builder est une bibliothèque consommée par d'autres plugins, pas une section admin autonome — mais reste un plugin détectable/activable/désactivable comme les autres (nécessaire pour la détection « plugin actif » requise par l'étape 16 et le principe « aucun plugin non-bloquant »).

**Tests**
- Pas de PHP : suite PHPUnit revérifiée verte (42 tests) sans ajout.
- **Vérification en navigateur réel** (Playwright) : le plugin est bien détecté et actif dans le gestionnaire de plugins (étape 06), sans erreur console au chargement de l'admin (le remote se charge silencieusement comme les autres plugins actifs, conformément à `usePlugins.js`).
- **Limite assumée** : le registre étant vide à ce stade, il n'y a encore rien à glisser-déposer ni éditer dans le canvas — impossible de vérifier `BuilderCanvas` de bout en bout dans un navigateur avant qu'un vrai module existe (étape 11) et qu'un vrai point d'intégration existe (étape 16, `plugin/page`). Vérification complète et interactive prévue à l'étape 16, qui exercera rétroactivement 10 à 15 ensemble.

**Décisions**
- **Aucune entité/table dédiée au builder** : le "modèle de données" demandé par l'étape est la structure JSON `{"builder": true, "modules": [...]}` elle-même, stockée dans le champ texte que le plugin consommateur possède déjà (ex. `Page.content`) — pas une table `builder_content` séparée. Le builder convertit vers du HTML pur au moment de la sauvegarde (`renderToHtml`), donc le champ stocké reste toujours du HTML affichable tel quel côté public, que le contenu vienne de `RichTextEditor` ou du builder : **aucun changement du rendu public n'est nécessaire**. Une seconde donnée (le JSON brut, pour ré-éditer plus tard) devra être stockée séparément par le plugin consommateur — traité à l'étape 16 pour `plugin/page`.
- Glisser-déposer en HTML5 natif plutôt qu'une librairie (`dnd-kit`, etc.) : suffisant pour une liste à réordonner à plat, évite une dépendance supplémentaire.

## 0.11.0 — Étape 11 : `builder-module-image`

Premier module du registre : Image.

**Réalisé**
- `plugin/builder/assets/modules/ImageModule.jsx` : `defaultProps: {fileUrl, alt}`, `Edit` ouvre `MediaPicker` (filtré `img`, consommé en `adm_host/MediaPicker` via `React.lazy`/`Suspense` — même prudence que pour `RichTextEditor` à l'étape 09 vis-à-vis de la consommation cross-remote asynchrone) + champ texte alternatif. `render(props)` produit `<img src="..." alt="..." class="builder-image" />` (ou une chaîne vide si aucune image), via le nouvel utilitaire partagé `modules/htmlEscape.js` (échappement HTML pour attributs — 2 appelants déjà avec ce module, seuil suffisant pour l'extraire plutôt que dupliquer).
- Ajouté au registre (`registry.js`) — aucun changement requis dans `BuilderCanvas`/`renderToHtml`.

**Tests**
- Pas de PHP : suite PHPUnit revérifiée verte (42 tests) sans ajout.
- `render()` étant une fonction pure sans dépendance React/DOM, sa logique d'échappement a été vérifiée directement avec `node -e` (guillemets/chevrons dans l'alt correctement échappés, chaîne vide si pas d'image) — même esprit qu'un test unitaire, sans introduire de runner de tests JS non demandé par le protocole (qui ne prescrit que PHPUnit).
- Build Webpack du remote `builder` vérifié (le module distant `adm_host/MediaPicker` se résout bien).
- **Vérification interactive en navigateur toujours différée à l'étape 16** (cf. étape 10) : pas encore de formulaire hôte où glisser ce module.

## 0.12.0 — Étape 12 : `builder-module-slider`

Deuxième module : Slider (carrousel d'images).

**Réalisé**
- `plugin/builder/assets/modules/SliderModule.jsx` : `defaultProps: {images: []}`, `Edit` liste les images ajoutées (vignette + texte alternatif + retrait individuel) avec un bouton « Ajouter une image » ouvrant `MediaPicker` (mêmes précautions `lazy`/`Suspense` que le module Image). `render(props)` produit une bande d'images en défilement horizontal (`overflow-x` en CSS), **sans JS de carrousel Bootstrap** : le thème public n'embarque pas `bootstrap.js` (react-bootstrap gère son propre comportement en JS, pas de bundle Bootstrap global), donc un balisage `data-bs-*` serait inerte côté public. Slider volontairement simple (glissement horizontal, pas de flèches/indicateurs animés) plutôt que de tirer une dépendance JS de carrousel supplémentaire.

**Tests**
- Pas de PHP : suite PHPUnit revérifiée verte (42 tests) sans ajout.
- Build Webpack du remote `builder` vérifié.
- Vérification interactive en navigateur toujours différée à l'étape 16.

## 0.13.0 — Étape 13 : `builder-module-download`


Troisième module : « Télécharger un fichier » (PDF/ZIP).

**Réalisé**
- `plugin/builder/assets/modules/DownloadModule.jsx` : `defaultProps: {fileUrl, fileName, label}`, `Edit` ouvre `MediaPicker` filtré `['pdf', 'zip']` (conforme à la spec de l'étape) + champ texte du bouton. `render(props)` produit `<a href="..." download="...">Label</a>` (chaîne vide si aucun fichier).

**Tests**
- Pas de PHP : suite PHPUnit revérifiée verte (42 tests) sans ajout.
- Build Webpack du remote `builder` vérifié.
- Vérification interactive en navigateur toujours différée à l'étape 16.

## 0.14.0 — Étape 14 : `builder-module-cta`

Quatrième module : Appel à l'action (CTA).

**Réalisé**
- `plugin/builder/assets/modules/CtaModule.jsx` : `defaultProps: {text, url, style}` (`style` ∈ `primary`/`secondary`/`outline`, classes CSS `builder-cta-*` laissées à la charge du thème public). `render(props)` produit `<a href="..." class="builder-cta builder-cta-{style}">Texte</a>` (chaîne vide si pas de texte).

**Tests**
- Pas de PHP : suite PHPUnit revérifiée verte (42 tests) sans ajout.
- Build Webpack du remote `builder` vérifié.
- Vérification interactive en navigateur toujours différée à l'étape 16.

## 0.15.0 — Étape 15 : `builder-module-text`

Cinquième et dernier module avant l'intégration : Texte, éditeur WYSIWYG complet — le plus complexe, comme prévu par la spec.

**Réalisé**
- Plutôt que dupliquer un second wrapper Quill, `cms/assets/adm/components/RichTextEditor.jsx` (étape 09) gagne une prop `full` qui bascule sa barre d'outils vers un jeu complet (titres 1-3, gras/italique/souligné/barré, couleur texte/fond, **police** — format `font` natif de Quill, polices par défaut sans/serif/monospace, aucun enregistrement supplémentaire nécessaire —, alignement, listes, citation, bloc de code, lien, image, nettoyage) au lieu du jeu restreint (étape 09). Seuil de duplication atteint (2 appelants : éditeur de secours et module Texte) justifiant l'extraction plutôt qu'un deuxième composant.
- `plugin/builder/assets/modules/TextModule.jsx` : `Edit` consomme `adm_host/RichTextEditor` (déjà exposé, déjà utilisé par le plugin `page`) avec `full`, `defaultProps: {html: ''}`. `render(props)` restitue le HTML tel quel (enveloppé `<div class="builder-text">...</div>`) — **pas** d'échappement HTML ici, contrairement aux autres modules : c'est du contenu HTML légitime destiné à être affiché tel quel, même contrat que `Page.content` aujourd'hui.

**Tests**
- Pas de PHP : suite PHPUnit revérifiée verte (42 tests) sans ajout.
- Build complet (hôte + 3 plugins) vérifié, `adm_host/RichTextEditor` se résout bien dans le remote `builder`.
- **Non-régression vérifiée en navigateur réel** (Playwright) : la barre d'outils de l'éditeur de secours (`plugin/page`) reste bien la version simple après ce changement (pas de bouton police, pas de titre niveau 1) — la prop `full` par défaut à `false` ne casse pas l'étape 09.
- Vérification interactive du module Texte lui-même toujours différée à l'étape 16 (comme les modules précédents).

## 0.16.0 — Étape 16 : `builder-page-integration`

Intégration du builder (étapes 10-15) dans le plugin `page` existant, à la place du formulaire titre/contenu simple — point d'intégration réel qui a enfin permis une vérification complète, interactive, en navigateur du builder. A mis au jour et corrigé deux bugs réels.

**Réalisé**
- `Plugin\Page\Entity\Page` : nouveau champ `builderData` (TEXT nullable, groupes `page:read`/`page:write`) — stocke le JSON brut du builder pour permettre sa ré-édition. `content` reste **toujours** du HTML public tel quel, que la page ait été éditée via l'éditeur de secours (étape 09) ou le builder (`renderToHtml()`, étape 10) : **aucun changement de rendu public n'était nécessaire** pour le contenu lui-même (voir cependant le bug de rendu ci-dessous, préexistant et sans rapport direct). Migration `Version20260904170000` (`ALTER TABLE page ADD builder_data`).
- `plugin/page/assets/PageForm.jsx` : détecte si le plugin `builder` est actif (`GET /api/admin/plugins/all`) et affiche soit `BuilderCanvas` (consommé en `builder/BuilderCanvas`, remote statique ajouté dans `plugin/page/webpack.config.js`) soit `RichTextEditor` comme avant — jamais les deux, aucun blocage si le builder est désinstallé/désactivé (principe « aucun plugin non-bloquant »). À l'enregistrement, `builder/renderToHtml` (import dynamique simple, pas `React.lazy` — ce n'est pas un composant) convertit la valeur JSON du canvas en HTML avant l'envoi ; `content` et `builderData` sont envoyés ensemble.

**Bugs réels trouvés et corrigés grâce à la vérification en navigateur**
1. **Formulaire imbriqué qui soumettait la page entière** : uploader une image depuis le sélecteur de médias *à l'intérieur* du builder (lui-même à l'intérieur du formulaire de page) déclenchait l'enregistrement de toute la page. Cause : `Modal` de react-bootstrap rend son contenu via un portail DOM (`document.body`), mais React fait toujours remonter les événements `submit` synthétiques le long de l'**arbre de composants React**, pas de l'arbre DOM — le `submit` du `FileUploadForm` imbriqué remontait donc jusqu'au `onSubmit` du formulaire englobant. Correctif : `event.stopPropagation()` dans `FileUploadForm::handleSubmit` (composant partagé, un seul correctif suffit pour tous les points d'usage : gestionnaire de fichiers, tout média picker, insertion d'image dans l'éditeur de secours, tous les modules du builder).
2. **Contenu affiché en texte brut échappé sur le site public** : `template/default/assets/PageList.jsx` affichait `{page.content}` (texte React échappé) au lieu de rendre le HTML — préexistant depuis le scaffold initial, resté invisible tant que le contenu était du texte simple, mais rendait tout le travail des étapes 09/10-16 invisible côté public. Corrigé en `dangerouslySetInnerHTML` (le contenu vient d'un admin authentifié `ROLE_SUPER_ADMIN`, pas d'un visiteur — même modèle de confiance que n'importe quel CMS affichant son propre contenu admin).
3. **Cache Symfony d'environnement `test` obsolète** : après avoir ajouté `builderData` à l'entité, `cache:clear` (environnement `dev` par défaut) ne suffisait pas — les tests PHPUnit tournent en `APP_ENV=test`, avec son propre cache compilé (métadonnées de sérialisation), resté périmé. Un nouveau champ apparaissait `null` malgré un `POST` correct. Correctif : `cache:clear --env=test` en plus du `cache:clear` par défaut à chaque changement touchant une entité/le DI.

**Tests**
- `PageAdminApiTest::testBuilderDataRoundTripsAlongsideContent` / `::testBuilderDataDefaultsToNullForTheFallbackEditor` (HTTP réel).
- **Vérification complète en navigateur réel** (Playwright), la plus poussée de toute la session : création d'une page avec le builder actif → ajout de 3 modules (Texte, CTA, Image — upload réel via le sélecteur) → **réordonnancement par glisser-déposer réel** (`locator.dragTo()`, qui simule un vrai geste de drag HTML5 — `page.mouse` seul ne suffit pas, ne déclenche pas `dragstart`/`dragover`/`drop`) → enregistrement → **réouverture** de la page (le canvas reconstruit fidèlement les 3 modules dans le nouvel ordre, avec la miniature image, à partir de `builderData`) → publication → **site public réellement chargé dans le navigateur** : image, lien CTA et texte formatés apparaissent comme de vrais éléments DOM, aucune balise échappée visible en texte.
- Cette étape valide rétroactivement les étapes 10 à 15 (registre, canvas, drag & drop, chacun des 5 modules) dans un contexte d'intégration réel, comme prévu/annoncé dans leurs entrées respectives.

**Décisions**
- `renderToHtml` importé en `import()` dynamique simple plutôt qu'en `React.lazy` : c'est une fonction pure, pas un composant — `React.lazy` n'a de sens que pour un élément rendu sous `Suspense`.

## 0.17.0 — Étape 17 : `plugin-realisations-admin`

CRUD admin du plugin Réalisations (portfolio de projets), copie conforme de `plugin/page/` (même schéma d'étape 16, avec `builderData` dès le départ) plus une image de couverture.

**Réalisé**
- `Plugin\Realisations\Entity\Realisation` (`plugin/realisations/src/Entity/`) : `title`, `content`, `builderData` (nullable, même contrat que `Page::$builderData`), `status` (enum `RealisationStatus`, mêmes valeurs `draft`/`published`/`archived`), `slug` (auto, `AsciiSlugger`), `coverImageUrl`/`coverImageAlt` (nullable, string simple — même convention que les modules builder Image/Download : une URL choisie via le média picker, pas de relation vers l'entité `File`), `createdAt`. `RealisationRepository`, `ApiResource` admin CRUD complet (`/admin/realisations*`, `ROLE_SUPER_ADMIN`) et publique lecture seule (`/realisations*`, statut publié uniquement, via `PublishedRealisationCollectionProvider`/`PublishedRealisationItemProvider` — mêmes classes que le plugin Page). Migration `Version20260904180000` (table `realisation`, colonnes équivalentes à `page` + les deux colonnes de couverture).
- Câblage plugin (identique à `plugin/page`/`plugin/i18n`) : mapping Doctrine (`PluginRealisations`), namespace de migrations (`Plugin\Realisations\Migrations`), autoload PSR-4 composer, service DI (`Plugin\Realisations\: resource: ...`), chemin ajouté à `api_platform.yaml` (`mapping.paths`), `package.json` `build:plugins` étendu.
- UI admin (`plugin/realisations/assets/`) : `RealisationList.jsx` (liste + vignette de couverture + archiver/supprimer) et `RealisationForm.jsx` (titre, sélection d'image de couverture via `adm_host/MediaPicker` filtré `img`, éditeur bascule builder/éditeur de secours comme `PageForm`, statut) — même détection `GET /api/admin/plugins/all` que `Page`. `plugin.json`, `webpack.config.js` (nom `realisations`, remotes `adm_host` + `builder`) copiés du gabarit `plugin/page`.

**Tests**
- `RealisationAdminApiTest` (HTTP réel) : cycle CRUD complet, aller-retour de l'image de couverture, `builderData` à `null` par défaut pour l'éditeur de secours, 401 anonyme (écriture et liste admin).
- Suite complète revérifiée verte (49 tests) après correction d'une régression latente sans rapport (voir ci-dessous).

**Bug pré-existant trouvé et corrigé (sans rapport avec Réalisations)**
- `make test` échouait sur `LangAdminApiTest`/`TranslationAdminApiTest`/`TranslationExtensionTest` (étapes 07/08) : `doctrine:fixtures:load` purge **toutes** les tables mappées Doctrine par défaut (`ORMPurger`), pas seulement celles touchées par les fixtures chargées — il effaçait donc les lignes FR/EN insérées par la migration `Version20260904150000` juste avant, à chaque exécution de `make test`/`make init`. Latent depuis l'étape 07, resté invisible tant que personne n'avait relancé `make test` sur une base `app_test` déjà migrée avant cette étape. Corrigé en ajoutant `--purge-exclusions=lang` aux deux cibles du `Makefile` (`init` et `test`) : `lang` est une table de données de référence semées par migration (donc censée survivre au rechargement des fixtures), pas une table de fixtures.

**Décisions**
- Image de couverture stockée comme deux champs simples (`coverImageUrl`/`coverImageAlt`), pas une relation vers `File` : cohérent avec la façon dont les modules du builder (Image, Download) référencent déjà un fichier choisi via le média picker.

## 0.18.0 — Étape 18 : `plugin-realisations-public`

Rendu public du plugin Réalisations (fin du plugin ouvert à l'étape 17).

**Réalisé**
- `template/default/assets/RealisationList.jsx` (grille de vignettes, image de couverture + titre, lien vers le détail) et `RealisationDetail.jsx` (titre, image de couverture, `content` en HTML via `dangerouslySetInnerHTML` — même modèle de confiance que `PageList`) consomment `GET /api/realisations` et `GET /api/realisations/{id}` (étape 17, déjà filtrés aux réalisations publiées).
- `template/default/assets/App.jsx` : nouvelles routes `/realisations` (liste) et `/realisations/:id` (détail), lien de nav « Réalisations ». La page d'accueil (`Home`) n'est pas modifiée ici — son contenu configurable est le périmètre de l'étape 21.

**Tests**
- `RealisationPublicApiTest` (HTTP réel) : seules les réalisations publiées apparaissent dans le listing public, une réalisation en brouillon n'est pas joignable via l'endpoint public par id (404), une réalisation publiée l'est.
- Suite complète revérifiée verte (52 tests).
- Build Webpack complet (hôte `default`/`adm` + remote `realisations`) vérifié sans erreur.
- Vérification interactive en navigateur non refaite à cette étape (même contenu/flux que `PageList`, déjà validé en navigateur à l'étape 16) — à revisiter si un souci apparaît en usage réel.

## 0.19.0 — Étape 19 : `plugin-actualites-admin`

CRUD admin du plugin Actualités (articles de blog/news), copie conforme du plugin Réalisations (étape 17) — même structure, mêmes champs (y compris l'image de couverture), aucune divergence fonctionnelle volontaire entre les deux à ce stade.

**Réalisé**
- `Plugin\Actualites\Entity\Actualite` (`plugin/actualites/src/Entity/`) : mêmes champs que `Realisation` (`title`, `content`, `builderData`, `status` via l'enum `ActualiteStatus`, `slug`, `coverImageUrl`/`coverImageAlt`, `createdAt`). `ActualiteRepository`, `ApiResource` admin CRUD complet (`/admin/actualites*`) et publique lecture seule (`/actualites*`, statut publié uniquement). Migration `Version20260904190000` (table `actualite`).
- Câblage plugin identique aux précédents : mapping Doctrine (`PluginActualites`), namespace de migrations (`Plugin\Actualites\Migrations`), autoload PSR-4, service DI, chemin `api_platform.yaml`, `package.json` `build:plugins` étendu.
- UI admin (`plugin/actualites/assets/`) : `ActualiteList.jsx` et `ActualiteForm.jsx`, copies conformes de `RealisationList`/`RealisationForm` (vocabulaire adapté : « actualité »). `plugin.json`, `webpack.config.js` (nom `actualites`).

**Tests**
- `ActualiteAdminApiTest` (HTTP réel) : mêmes scénarios que `RealisationAdminApiTest` (cycle CRUD, image de couverture, `builderData` nul par défaut, 401 anonyme).
- Suite complète revérifiée verte (57 tests).
- Build Webpack du remote `actualites` vérifié sans erreur.

**Décisions**
- Aucune distinction de modèle entre Réalisations et Actualités à ce stade (les deux backlogs — section 5/6 de `INIT_ETAPES.md` — ne décrivent pas de champ spécifique à l'un ou l'autre, par ex. pas de « catégorie » ni de « date de publication » distincte de `createdAt`) : à faire évoluer au cas par cas si un besoin concret apparaît, plutôt que d'anticiper.

## 0.20.0 — Étape 20 : `plugin-actualites-public`

Rendu public du plugin Actualités (fin du plugin ouvert à l'étape 19), copie conforme de l'étape 18.

**Réalisé**
- `template/default/assets/ActualiteList.jsx` (grille de vignettes, image de couverture + titre + date) et `ActualiteDetail.jsx` (titre, date, image de couverture, `content` en HTML) consomment `GET /api/actualites` et `GET /api/actualites/{id}` (étape 19).
- `template/default/assets/App.jsx` : nouvelles routes `/actualites` (liste) et `/actualites/:id` (détail), lien de nav « Actualités ».

**Tests**
- `ActualitePublicApiTest` (HTTP réel) : mêmes scénarios que `RealisationPublicApiTest`.
- Suite complète revérifiée verte (60 tests).
- Build Webpack complet (hôte `default`/`adm`) vérifié sans erreur.

## 0.21.0 — Étape 21 : `plugin-accueil`

Page d'accueil : configuration du contenu **et** rendu public, dans la même étape/plugin (contrairement à Réalisations/Actualités) — dernière étape du lot 17-21.

**Réalisé**
- `Plugin\Accueil\Entity\HomeContent` (`plugin/accueil/src/Entity/`) : **singleton**, pas une liste comme les autres plugins de contenu — un seul enregistrement, sans `title`/`status`/`slug`. Champs `content`/`builderData` avec le même contrat que `Page` (`content` toujours l'HTML public, `builderData` le JSON brut du builder pour ré-édition), `updatedAt`. `ApiResource` : `GET`/`PATCH /admin/home` (`ROLE_SUPER_ADMIN`) et `GET /home` (public), tous les trois routés vers `HomeContentProvider` (aucun `{id}` dans l'URI : la ressource est toujours « la même »). Migration `Version20260904200000` (table `home_content`).
- `Plugin\Accueil\State\HomeContentProvider` : renvoie l'unique ligne, en la créant vide au premier accès (`findSingleton()` puis `persist()`/`flush()` si absente) — garantit qu'un `PATCH` a toujours quelque chose à fusionner, même avant toute sauvegarde admin.
- Câblage plugin identique aux précédents (mapping Doctrine `PluginAccueil`, namespace de migrations, autoload PSR-4, service DI, chemin `api_platform.yaml`, `package.json` `build:plugins`).
- UI admin `plugin/accueil/assets/AccueilForm.jsx` : formulaire unique (pas de liste/nouveau, un seul point d'entrée de nav « Accueil » directement vers le formulaire), même bascule builder/éditeur de secours que les autres plugins de contenu, `PATCH /admin/home` à l'enregistrement. Confirmation de sauvegarde par une alerte locale (pas de navigation de retour possible ici, contrairement aux autres formulaires qui redirigent vers une liste après enregistrement) plutôt que `react-toastify` (dépendance déjà présente dans `package.json` mais son `ToastContainer` n'est monté nulle part dans l'admin — l'introduire pour ce seul formulaire aurait été disproportionné).
- **Rendu public** : `template/default/assets/Home.jsx` (nouveau) remplace le contenu de la page d'accueil (`/`) — il consomme `GET /api/home` et affiche `content` en HTML (repli sur un message de bienvenue si vide). La liste des pages (`PageList`, précédemment affichée sur `/`) est déplacée sur sa propre route `/pages` avec un lien de nav « Pages », puisque `/` affiche désormais le contenu configuré par ce plugin.

**Bug réel trouvé et corrigé**
- `HomeContentProvider::provide()` n'était **jamais appelé** pour l'opération `Patch` : `ApiPlatform\Symfony\EventListener\ReadListener::onKernelRequest()` calcule `read = getUriVariables() || isMethodSafe()` quand l'attribut `read` de l'opération vaut `null` (valeur par défaut) — `/admin/home` n'a pas d'`{id}` dans son URI (`uriVariables` vide) et `PATCH` n'est pas une méthode « sûre », donc `read` valait `false` : le provider n'était jamais invoqué, la désérialisation `merge-patch` créait un tout nouvel objet vide au lieu de fusionner dans l'unique ligne existante — **chaque `PATCH` insérait une nouvelle ligne** au lieu de mettre à jour la précédente. Reproduit en HTTP réel (`curl`, logs Doctrine) avant d'être compris. Corrigé en forçant explicitement `read: true` sur l'opération `Patch`. Piège spécifique aux ressources « singleton » sans identifiant dans l'URI — sans rapport avec `Page`/`Realisation`/`Actualite`, qui ont toutes un `{id}`.

**Tests**
- `HomeContentApiTest` (HTTP réel) : auto-création vide au premier `GET` admin, un second `PATCH` met à jour la **même** ligne (`id` inchangé, régression du bug ci-dessus), lecture publique reflète la dernière valeur, 401 anonyme (lecture admin et écriture).
- Suite complète revérifiée verte (65 tests).
- Build Webpack complet (hôte `default`/`adm` + remote `accueil`) vérifié sans erreur.
- Vérification manuelle en HTTP réel (`curl`, contournant le navigateur faute de suite Playwright dans cette passe) : connexion admin, double `PATCH /api/admin/home` confirmant la persistance sur la même ligne, lecture publique `/api/home` reflétant la valeur enregistrée, `GET /api/admin/plugins/all` confirmant la détection dynamique du plugin `accueil`.

**Décisions**
- Page d'accueil modélisée comme un singleton dédié plutôt que réutiliser `Page` avec un slug spécial (ex. `home`) : évite tout risque de collision de slug et documente explicitement, au niveau du schéma, qu'il ne peut exister qu'un seul contenu d'accueil — cohérent avec l'objectif de l'étape 21 (« configuration du contenu de la page d'accueil »).

## 0.21.1 — Correctif : bugs JS admin (build wipé + warning React Router)

Suite à un signalement utilisateur (« beaucoup de bugs JS sur le site »), investigation en navigateur réel (Playwright/Chromium, `Claude in Chrome` n'étant pas disponible dans cette session CLI) plutôt qu'une nouvelle étape du roadmap.

**Bugs réels trouvés et corrigés**
1. **Tous les remotes de plugins admin sauf le dernier construit avaient disparu** (`page`, `i18n`, `builder`, `realisations`, `actualites` — seul `accueil` survivait) : chaque plugin échouait au chargement avec `Remote container "X" was not found on window`. Cause : `Encore.cleanupOutputBeforeBuild()` (`webpack.config.js` racine) utilise `output.clean` natif de Webpack 5, qui **vide tout `cms/public/build/`** par défaut à chaque build de l'hôte (`encore dev`/`encore production`) — y compris `plugins/**`, où vivent les remotes construits séparément par `npm run build:plugins` (`plugin/*/webpack.config.js`, configs webpack brutes, pas gérées par Encore). N'importe quel `encore dev`/`npm run watch` lancé après un `build:plugins` efface donc silencieusement tous les remotes de plugins, sans erreur ni avertissement au moment du build — seul `npm run build` (production) enchaîne les deux dans le bon ordre et n'est pas concerné par cette régression. Corrigé en passant `options.keep = /^plugins\//` au callback de `cleanupOutputBeforeBuild()`, qui préserve ce sous-dossier lors du nettoyage. Vérifié en reproduisant exactement le scénario cassé (build hôte → build de tous les plugins → nouveau build hôte) : les 6 remotes survivent désormais.
2. **Avertissement React Router à chaque lien direct/rechargement vers une route de plugin** (`No routes matched location "/pages"`, etc., x4 dans la console) : `cms/assets/adm/App.jsx` rendait `<Routes>` avant que `usePlugins()` ait fini de résoudre les remotes MF, avec les routes de plugins ajoutées seulement `!pluginsLoading &&` — donc pendant la fenêtre de chargement, un accès direct à une URL possédée par un plugin (ex. rechargement de `/adm/pages`) ne correspondait à aucune route connue à cet instant. Corrigé en affichant un état "Chargement..." tant que `pluginsLoading` est vrai, avant de monter `<Routes>` avec la table complète (même logique déjà appliquée à `loading` de l'authentification, juste au-dessus). Bug mineur (aucune casse fonctionnelle, juste du bruit console + un flash de 404 interne), mais visible sur quasi tous les rechargements admin — cohérent avec le « beaucoup de bugs JS » remonté.

**Tests**
- Suite complète revérifiée verte (65 tests, aucune régression).
- Vérification en navigateur réel (Playwright/Chromium) : parcours admin complet post-correctif (connexion, tous les liens de nav — Pages/Réalisations/Actualités/Accueil/Fichiers/Plugins/Langues — cycle CRUD complet sur une Réalisation avec sélecteur de média et canvas builder, formulaire d'édition d'une Page existante) sans aucune erreur/avertissement console ; rendu public (`/`, `/pages`, `/realisations`, `/realisations/:id`, `/actualites`) sans erreur ; rechargement direct (navigation dure, pas SPA) de 6 URLs de plugins admin sans plus aucun avertissement React Router.

**Décisions**
- Correctif traité comme un patch (0.21.1), pas une nouvelle étape : bug transverse à l'ensemble de l'admin (build), sans rapport avec le contenu fonctionnel d'une étape précise du roadmap.
- `Claude in Chrome` (extension navigateur) demandé par l'utilisateur n'est pas exposé comme outil dans cette session Claude Code CLI — investigation faite via Playwright + Chromium headless (déjà utilisés pour la vérification de l'étape 16), équivalent fonctionnel pour ce diagnostic (accès DOM/console/réseau réel).

## 0.22.0 — Renommage des plugins (demande explicite utilisateur)

Renommage de quatre plugins (répertoire, `plugin.json` `name`/`label`, et — sur choix explicite de l'utilisateur entre deux options proposées — namespace PHP et classes/composants internes pour rester cohérent avec la convention du projet) :

| Ancien répertoire | Nouveau répertoire | `name` | `label` |
|---|---|---|---|
| `plugin/accueil` | `plugin/homepage` | `homepage` | Gestion de la page d'accueil |
| `plugin/actualites` | `plugin/news` | `news` | Blog et actualité |
| `plugin/builder` | `plugin/page_builder` | `page_builder` | Constructeur de page (Drag & Drop) |
| `plugin/realisations` | `plugin/portfolio` | `portfolio` | Réalisations |

**Réalisé**
- **Portfolio** (ex-Réalisations) : entité `Plugin\Realisations\Entity\Realisation` renommée `Plugin\Portfolio\Entity\PortfolioItem` (+ `PortfolioItemStatus`/`PortfolioItemRepository`/`Published*Provider`), table `realisation` → `portfolio_item`, groupes de sérialisation `realisation:*` → `portfolio:*`, routes `/admin/realisations*`/`/realisations*` → `/admin/portfolio*`/`/portfolio*`. Composants admin `RealisationForm`/`RealisationList` → `PortfolioItemForm`/`PortfolioItemList`, composants publics (`template/default/assets/`) idem.
- **News** (ex-Actualités) : entité `Actualite` → `Plugin\News\Entity\NewsArticle` (même traitement : `NewsArticleStatus`/`NewsArticleRepository`/`Published*Provider`), table `actualite` → `news_article`, groupes `actualite:*` → `news:*`, routes `/admin/actualites*`/`/actualites*` → `/admin/news*`/`/news*`. Composants `ActualiteForm`/`ActualiteList` → `NewsArticleForm`/`NewsArticleList` (admin + public).
- **Homepage** (ex-Accueil) : entité `HomeContent` inchangée (déjà nommée indépendamment de « Accueil »), seul le namespace change (`Plugin\Accueil` → `Plugin\Homepage`) ; routes `/admin/home`/`/home` → `/admin/homepage`/`/homepage` (la route publique du thème reste `/`, seule l'URL de l'API de contenu change). `AccueilForm.jsx` → `HomepageForm.jsx`.
- **Page Builder** (ex-Builder) : aucun backend PHP, juste `plugin.json`/`webpack.config.js` renommés. Étant consommé **par nom** en Module Federation par les quatre autres plugins de contenu (`plugin/page`, `plugin/portfolio`, `plugin/news`, `plugin/homepage`), chacun de leurs `webpack.config.js` (`remotes: { builder: ... }` → `{ page_builder: ... }`) et formulaires (`import('builder/BuilderCanvas')`/`import('builder/renderToHtml')` → `page_builder/...`, `'builder' === plugin.name` → `'page_builder' === plugin.name`) a dû être mis à jour en même temps — sans quoi le renommage aurait cassé l'intégration builder partout, pas seulement dans le plugin lui-même.
- Câblage central mis à jour pour les trois plugins PHP : `doctrine.yaml` (alias/`dir`/`prefix`), `doctrine_migrations.yaml` (namespace/chemin), `composer.json` (autoload PSR-4), `services.yaml` (resource), `api_platform.yaml` (mapping path). `package.json` `build:plugins` et le remote `page_builder` mis à jour partout où il était référencé.
- Migrations existantes (`Version20260904180000` pour Portfolio, `Version20260904190000` pour News, `Version20260904200000` pour Homepage) réécrites en place (namespace + SQL de création directement sous le nouveau nom de table) plutôt que layered avec une migration de renommage : projet pré-1.0, aucune donnée réelle à préserver, cohérent avec le choix de l'utilisateur de renommer aussi les classes PHP. Base `app` (dev) et `app_test` intégralement recréées (`doctrine:database:drop`/`create`/`migrate`/fixtures) plutôt que de tenter de réconcilier `doctrine_migration_versions` avec les anciens FQCN.
- Tests renommés en conséquence : `RealisationAdminApiTest`/`RealisationPublicApiTest` → `PortfolioItemAdminApiTest`/`PortfolioItemPublicApiTest`, `ActualiteAdminApiTest`/`ActualitePublicApiTest` → `NewsArticleAdminApiTest`/`NewsArticlePublicApiTest`, `HomeContentApiTest` conservé (entité inchangée) avec ses endpoints mis à jour vers `/homepage`.
- Les **labels de navigation** affichés dans la barre latérale admin (`navItem.label` de chaque `AdminModule.jsx` : « Réalisations », « Actualités », « Accueil ») et dans le thème public (liens « Réalisations »/« Actualités »/« Accueil ») sont **volontairement inchangés** — la demande portait explicitement sur le répertoire et les champs `name`/`label` de `plugin.json` (utilisés par la page Plugins), pas sur ce texte d'interface, distinct et déjà en français.

**Tests**
- Suite complète revérifiée verte (65 tests) après recréation complète des bases dev/test.
- Build Webpack complet vérifié : hôte (`default`/`adm`/`adm_host`) + les 6 remotes de plugins (`page`, `i18n`, `page_builder`, `portfolio`, `news`, `homepage`), tous compilent sans erreur, `page_builder` correctement résolu comme remote externe par les quatre autres.
- Vérification en navigateur réel (Playwright/Chromium) : rendu public (`/`, `/pages`, `/portfolio`, `/news`) sans erreur console ; connexion admin, tous les liens de nav (inchangés à l'écran) chargent bien leurs plugins renommés sans erreur ; `GET /api/admin/plugins/all` confirme les 6 `name`/`label` exactement conformes à la demande ; cycle complet créer/lister/supprimer un item Portfolio via `/adm/portfolio/new` → `/adm/portfolio`.

**Décisions**
- Renommage complet (namespaces PHP + classes/fichiers, pas seulement l'identité externe du plugin) choisi explicitement par l'utilisateur après clarification (deux options proposées) — cohérent avec la convention du projet où le namespace mirror le répertoire (`Plugin\Page` ↔ `plugin/page`, etc.).
- Nom de classe `PortfolioItem` (pas `Portfolio`, qui désignerait plutôt la collection/le plugin lui-même) et `NewsArticle` (pas `News`) : évite la confusion entre le plugin et une entité représentant un seul élément de contenu.
- Routes API calquées sur le nouveau nom de plugin plutôt que sur un pluriel dérivé de l'entité (`/admin/portfolio` plutôt que `/admin/portfolio-items`, `/admin/news` plutôt que `/admin/news-articles`) : plus court, cohérent avec `/admin/pages` (Page) et `/admin/langs` (i18n) — un seul segment de route par plugin.

## 0.23.0 — Étapes 22 à 25 : envoi d'email + plugin Newsletter

Lot autorisé explicitement par l'utilisateur ("Fait les étapes 22 à 31", enchaînement sans validation intermédiaire). Livré en un seul commit/version : le plugin Newsletter (23-25) dépend directement de la brique mail (22) livrée dans la même passe, aucun état intermédiaire réellement utilisable n'existerait entre les deux (même raisonnement que le regroupement 04+05 en 0.5.0).

**Réalisé — 22 (`mail-sending-service`)**
- `App\Service\MailService` (`cms/src/Service/`) : enveloppe fine autour de `Symfony\Component\Mailer\MailerInterface`, méthode unique `send(to, subject, htmlBody, replyTo=null)` construisant un `Email` Mime. Délibérément sans moteur de gabarit (Twig) - les corps nécessaires jusqu'ici (lien de vérification, campagne newsletter, email de test SMTP) sont tous de petits blocs HTML déjà construits par l'appelant.
- `MAILER_FROM`/`MAILER_FROM_NAME` (nouvelles variables d'env, `cms/.env`), injectées via `services.yaml` (`bind: string $fromAddress`/`$fromName`).
- `MAILER_DSN` changé de `null://null` vers `smtp://mailer:1025` : le stack Docker a déjà un conteneur Mailpit (`compose.override.yaml`, `make start` affiche son URL) resté inutilisé jusqu'ici faute de code émetteur - l'envoi réel y transite désormais en dev.
- **Décision structurelle** : `Symfony\Component\Mailer\Messenger\SendEmailMessage` retiré du routing Messenger (`messenger.yaml`) plutôt que routé vers le transport `async` existant - aucun worker `messenger:consume` ne tourne dans `compose.yaml`, un routage async aurait empilé les messages dans `messenger_messages` sans jamais les envoyer. `MailService` envoie donc de façon synchrone.
- Tests : `MailServiceTest` (`KernelTestCase` + `MailerAssertionsTrait`) - email HTML construit et envoyé, `Reply-To` posé quand fourni.

**Réalisé — 23+24 (`newsletter-plugin-admin` + `newsletter-bulk-send-ui`)**
- Plugin `plugin/newsletter/` (câblage identique aux plugins précédents : mapping Doctrine `PluginNewsletter`, namespace de migrations, autoload PSR-4, service DI, `api_platform.yaml`, `package.json` `build:plugins`). Migration `Version20260904210000` (tables `newsletter_subscriber`, `newsletter_campaign`, `newsletter_campaign_send`).
- `Plugin\Newsletter\Entity\Subscriber` : `email` (unique, `Assert\Email`), `subscribedAt`. `ApiResource` admin (`GetCollection`/`Delete` sur `/admin/newsletter/subscribers`, liste + suppression - étape 23) et publique (`Post` sur `/newsletter/subscribers` - étape 25, voir plus bas).
- `Plugin\Newsletter\Entity\Campaign` : `subject`, `content` (HTML, `RichTextEditor` seul - **pas d'intégration du page builder**, voir Décisions), `status` (enum `Draft`/`Sending`/`Sent`), `totalRecipients` (snapshot pris au démarrage de l'envoi), `createdAt`/`sentAt`. `ApiResource` admin CRUD complet sur `/admin/newsletter/campaigns*`.
- `Plugin\Newsletter\Entity\CampaignSend` : ligne de suivi (pas un `ApiResource`, contrainte unique `(campaign_id, subscriber_id)`) - une par email réellement envoyé, ce qui rend l'envoi en masse **reprenable** après interruption.
- `Plugin\Newsletter\Controller\Admin\CampaignSendController` (`POST /api/admin/newsletter/campaigns/{id}/send-next`, étape 24) : envoie **un seul** email au prochain abonné sans `CampaignSend` pour cette campagne (requête avec `LEFT JOIN ... WITH` + `IS NULL`, `CampaignSendRepository::findNextPendingSubscriber()`), enregistre le `CampaignSend`, et répond `{sentCount, total, done}`. Premier appel sur une campagne `Draft` : bascule `Sending` + fige `totalRecipients`. Plus aucun abonné en attente : bascule `Sent` + `sentAt`. Un appel sur une campagne déjà `Sent` répond `done: true` immédiatement, sans re-vérifier ni ré-envoyer.
- UI admin (`plugin/newsletter/assets/`) : `SubscriberList` (liste + suppression), `CampaignList` (liste + statut + liens Éditer/Envoyer/Supprimer), `CampaignForm` (sujet + `RichTextEditor` seul), `CampaignSend` (étape 24 - barre de progression React-Bootstrap, bouton "Démarrer l'envoi" qui boucle `fetch` → `send-next` jusqu'à `done: true`, reprenable si la page est rechargée en cours de route).

**Réalisé — 25 (`newsletter-public-signup`)**
- `Plugin\Newsletter\State\SubscriberSignupProcessor` : traite le `Post` public - un email déjà abonné est traité comme un succès idempotent (renvoie la ligne existante) plutôt qu'une violation de contrainte unique en base (qui remonterait en 500) : un visiteur qui soumet deux fois le même email ne voit pas d'erreur pour quelque chose qui n'en est pas une de son point de vue.
- `template/default/assets/NewsletterSignup.jsx` : petit formulaire (email + bouton), monté dans un nouveau `<footer>` de `App.jsx`, visible sur toutes les pages publiques.

**Tests**
- `NewsletterSubscriberAdminApiTest`, `NewsletterSubscriberPublicApiTest` (inscription anonyme, idempotence sur doublon, email invalide → 422), `NewsletterCampaignAdminApiTest` (CRUD complet), `NewsletterCampaignSendTest` (cycle `send-next` complet sur 2 abonnés : 2 envois réels puis réponses `done` stables, statut final `sent`, ré-appel après complétion sans nouvel envoi).
- Suite complète revérifiée verte (76 tests).
- Build Webpack complet vérifié (hôte + 7 remotes de plugins, `newsletter` inclus, résout bien `adm_host/RichTextEditor`).

**Piège rencontré en écrivant les tests**
- `NewsletterCampaignSendTest` échouait initialement avec "0 emails sent" malgré des envois bien réels (confirmés indépendamment via `var_dump`/Mailpit) : `framework.test: true` enregistre un `ResetListener` qui appelle `reset()` sur tout service `ResetInterface` - dont `mailer.message_logger_listener` - sur **chaque** `kernel.terminate`, donc après **chaque** requête `$client->request()`, pas seulement au reboot du kernel. Un test qui enchaîne plusieurs requêtes puis fait une seule assertion `assertEmailCount()` à la fin ne voit que les emails de la toute dernière requête (ici zéro, puisque la dernière était une requête `GET` sans envoi). Corrigé en faisant l'assertion mailer immédiatement après chaque requête qui envoie réellement un email, pas après coup. Point de vigilance pour tout futur test HTTP réel couvrant plusieurs requêtes et voulant vérifier des emails.

**Décisions**
- Pas d'intégration du plugin `page_builder` pour le contenu des campagnes (contrairement à Page/Portfolio/News/Homepage) : le HTML d'un email doit rester simple/inlineable, et des modules comme le Slider ne produisent pas un balisage adapté à l'email. `RichTextEditor` seul suffit et reste cohérent avec le principe « aucun plugin non-bloquant » (rien ici ne dépend du builder).
- Pas de double opt-in / confirmation d'inscription : non demandé par le backlog, ajoutable plus tard si un besoin réel apparaît.
- `CampaignSend` comme table de suivi dédiée plutôt qu'un simple compteur sur `Campaign` : rend l'envoi en masse réellement reprenable après une interruption (fermeture d'onglet, erreur réseau) sans jamais ré-envoyer à un abonné déjà traité.

## 0.24.0 — Étape 26 : inscription publique + vérification email

**Réalisé**
- `App\Entity\User` étendu (`cms/src/*`, pas un plugin) : `verified` (bool), `verificationToken` (string nullable unique), `verifiedAt` (nullable), `createdAt` (nouveau, utile aussi à l'étape 28). Migration `Version20260904220000` (`ALTER TABLE user ADD ...`, `created_at` avec `DEFAULT CURRENT_TIMESTAMP` pour la ligne admin déjà existante). `UserFixtures` met désormais `admin@cms.dev` en `verified = true` directement.
- `App\Controller\Security\RegistrationController` (`POST /api/register`, public) : validation manuelle (email via `filter_var`, mot de passe ≥ 8 caractères, email déjà utilisé → 409), hash via `UserPasswordHasherInterface`, génère un `verificationToken` (`random_bytes(32)`), envoie l'email de vérification via `MailService` (étape 22) avec un lien `{DEFAULT_URI}/verify-email/{token}`.
- `App\Controller\Security\EmailVerificationController` (`GET /api/verify-email/{token}`, public) : marque `verified = true`, fige `verifiedAt`, efface le token. Token inconnu → 404.
- Frontend public (`template/default/assets/`) : `auth/AuthContext.jsx` (copie conforme de `cms/assets/adm/auth/AuthContext.jsx` - mêmes endpoints `/login`/`/logout`/`/me`, `json_login` n'est pas spécifique à l'admin), `Login.jsx`, `Register.jsx`, `VerifyEmail.jsx`. `App.jsx` : `AuthProvider` englobant, nouvelles routes `/login`/`/register`/`/verify-email/:token`, nav droite conditionnelle (Connexion/Inscription si anonyme, email + Déconnexion sinon).
- `MAILER_DSN` mis à `null://null` dans `cms/.env.test` (nouveau) : les tests asserted via `mailer.message_logger_listener`, pas via une vraie livraison - inutile de faire transiter chaque test par le vrai conteneur Mailpit, plus rapide et plus hermétique. `cms/.env` (dev) garde `smtp://mailer:1025`.

**Tests**
- `RegistrationTest` (email envoyé et capturé, email invalide/mot de passe court → 422, email déjà utilisé → 409), `EmailVerificationTest` (token valide vérifie le compte, token inconnu → 404).
- Suite complète revérifiée verte (82 tests).
- Vérification manuelle en HTTP réel (curl) : inscription réelle, email effectivement livré dans Mailpit (confirmé via son API `GET /api/v1/messages`).
- Build Webpack complet vérifié sans erreur.

**Piège rencontré en écrivant les tests**
- `EmailVerificationTest` échouait avec `Entity ... is not managed` : l'entité `User` récupérée avant l'appel HTTP de vérification devenait détachée après le reboot de kernel qui accompagne la requête suivante (même mécanisme de reset-par-requête que le piège rencontré à l'étape 23-25, mais côté Doctrine cette fois plutôt que Mailer). Corrigé en re-récupérant l'utilisateur via le repository après la requête plutôt qu'en rafraîchissant l'instance déjà détachée.

**Décisions**
- La connexion ne bloque pas un compte non vérifié : `verified` n'est utilisé nulle part encore pour restreindre l'accès - seulement posé pour que l'étape 27 (profil) puisse afficher un bandeau, et l'étape 28 (admin) puisse filtrer/afficher la colonne. Un besoin réel de blocage pourrait être ajouté plus tard sans changement de schéma.
- Validation manuelle (`filter_var`, `strlen`) plutôt que le composant Validator : cohérent avec le style déjà utilisé par les contrôleurs classiques du projet (`FileController`, `PluginController`), évite d'introduire un nouveau mécanisme pour un besoin aussi simple.

## 0.25.0 — Étape 27 : profil membre public

**Réalisé**
- `App\Controller\Security\ProfileController` (`PATCH /api/profile`) : changement de mot de passe pour l'utilisateur actuellement connecté (`Security::getUser()`, 401 si anonyme - même style que `SessionController`/`/api/me`, pas de règle `access_control` dédiée). Exige `currentPassword` (vérifié via `UserPasswordHasherInterface::isPasswordValid()`) avant d'accepter un `newPassword` (≥ 8 caractères).
- `SessionController`/`LoginController` renvoient désormais aussi `verified` (déjà stocké sur `User` depuis l'étape 26) - nécessaire pour que `Profile.jsx` puisse afficher un bandeau "email non vérifié" sans requête supplémentaire.
- `template/default/assets/Profile.jsx` : email en lecture seule, formulaire de changement de mot de passe, bandeau si `!user.verified`. Redirige vers `/login` si non connecté. Nav publique : le lien "Connexion/Inscription" devient l'email de l'utilisateur (lien vers `/profile`) + "Déconnexion" une fois authentifié ; `Login.jsx` redirige vers `/profile` après connexion (étape 26 redirigeait vers `/` faute de destination).

**Tests**
- `ProfileTest` : changement de mot de passe réussi (avec restauration du mot de passe d'origine pour ne pas perturber les autres tests qui dépendent des identifiants de la fixture admin), mot de passe actuel incorrect → 422, accès anonyme → 401.
- Suite complète revérifiée verte (85 tests).
- Build Webpack complet vérifié sans erreur.

**Décisions**
- Pas de changement d'email depuis le profil : `email` est l'identifiant de connexion (`User::getUserIdentifier()`) - le permettre exigerait son propre flux de re-vérification (étape 26), non demandé ici. Affiché en lecture seule pour l'instant.

## 0.26.0 — Étape 28 : administration des utilisateurs

**Réalisé**
- `App\Controller\Admin\UserController` (`cms/src/*`, contrôleur classique comme `FileController`/`PluginController` - pas un `ApiResource`, le hash de mot de passe à la création et le garde-fou "impossible de se supprimer soi-même" restent plus simples en contrôleur qu'en processor `ApiResource` sur mesure) : `GET /api/admin/users` (liste), `POST /api/admin/users` (création - **auto-vérifié**, un admin qui crée un compte s'en porte garant, pas de round-trip email), `PATCH /api/admin/users/{id}` (`roles`/`verified`), `DELETE /api/admin/users/{id}` (refuse sur soi-même, 400).
- Page admin statique `cms/assets/adm/pages/UserManager.jsx` (pas un plugin, comme `FileManager`/`PluginManager`) : formulaire de création rapide, liste avec bascule `ROLE_SUPER_ADMIN` (switch) et `verified` (badge cliquable), suppression (désactivée sur son propre compte). Route `/users` + entrée de nav "Utilisateurs".

**Tests**
- `UserAdminApiTest` (HTTP réel) : cycle complet création/liste/rôles/verified/suppression, suppression de son propre compte rejetée (400), email déjà utilisé rejeté (409), accès anonyme rejeté (401).
- Suite complète revérifiée verte (89 tests).
- Build Webpack complet vérifié sans erreur.

**Décisions**
- Contrôleur classique plutôt qu'`ApiResource` (contrairement à Page/Portfolio/News/Homepage) : `User` porte une logique d'écriture non triviale (hash de mot de passe à la création, garde-fou anti-auto-suppression) qui s'exprime plus simplement à la main qu'avec un state processor `ApiResource` dédié - même raisonnement que `FileController` pour l'upload multipart.

## 0.27.0 — Étapes 29 à 31 : configuration du site (SMTP, identité, cache)

Lot livré en un seul commit/version : `MAIN.md` regroupe déjà 29-31 sous une même section "Configuration du site" et les trois s'appuient sur la même entité singleton `SiteConfig` - les séparer aurait exigé un découpage artificiel de la même page admin (même raisonnement que 04+05 et 22-25).

**Réalisé — 29 (`site-config-smtp`)**
- `App\Entity\SiteConfig` (`cms/src/*`, pas un plugin) : singleton (même schéma que `Plugin\Homepage\Entity\HomeContent`, mais fetch/create via `SiteConfigRepository::findOrCreate()` plutôt qu'un provider `ApiPlatform` - ce contrôleur est classique, voir plus bas) - `siteName`, `logoUrl`/`faviconUrl` (étape 30), `smtpHost`/`smtpPort`/`smtpUser`/`smtpPassword`/`smtpEncryption` (étape 29, `smtpEncryption` **affichage seulement** - le transport `smtp` de Symfony déduit déjà le TLS du port), `updatedAt`. Migration `Version20260904230000`.
- **`App\Service\MailService` retravaillé** : ne consomme plus directement `MailerInterface` (câblé une fois pour toutes à la compilation du conteneur depuis `MAILER_DSN`) mais construit son propre `Transport`/`Mailer` à chaque envoi (`Transport::fromDsn()`), avec le DSN dérivé de `SiteConfig` si un `smtpHost` est enregistré, sinon replié sur `MAILER_DSN` (Mailpit en dev, étape 22). Le dispatcher d'événements du conteneur (`Psr\EventDispatcher\EventDispatcherInterface`, autowiré) est passé explicitement à `Transport::fromDsn()` pour que `mailer.message_logger_listener` continue de capter les envois dans les tests malgré ce changement de câblage.
- `App\Controller\Admin\SiteConfigController` (contrôleur classique, comme `UserController`/`FileController`/`PluginController` - le fetch/create singleton, le test d'envoi et l'appel sous-processus de vidage de cache ne collent pas à des opérations CRUD `ApiResource`) : `GET`/`PATCH /api/admin/site-config`, `POST /api/admin/site-config/test-mail` (`{to}`, envoie un vrai email avec les réglages actuellement enregistrés, capture `TransportExceptionInterface` → `{success:false, error}`).

**Réalisé — 30 (`site-config-general`)**
- `PATCH /api/admin/site-config` couvre aussi `siteName`/`logoUrl`/`faviconUrl` (logo/favicon choisis via le `MediaPicker` existant, filtré `img` - mêmes conventions que les images de couverture Portfolio/News). `App\Controller\SiteConfigPublicController` (`GET /api/site-config`, public, sous-ensemble name/logo/favicon **uniquement** - jamais les champs SMTP).
- Public : `template/default/assets/useSiteConfig.js` (hook, un seul fetch au montage) applique `document.title` + le `<link rel="icon">` en JS - le site public étant une SPA, pas de round-trip serveur par page ; `App.jsx` utilise `siteName`/`logoUrl` dans `Navbar.Brand` (repli sur "Solar CMS" tant que le fetch n'a pas résolu).

**Réalisé — 31 (`site-config-cache-clear`)**
- `POST /api/admin/site-config/clear-cache` : lance `php bin/console cache:clear --env=<kernel.environment>` via `Symfony\Component\Process\Process` (projet déjà dépendant de `symfony/process`), renvoie `{success, error?}` sur base du code de sortie.

**UI admin**
- Page statique `cms/assets/adm/pages/SiteConfig.jsx` (pas un plugin, comme `UserManager`/`FileManager`) regroupant les trois sections (Général/SMTP/Cache) sur un seul écran, route `/settings` + entrée de nav "Configuration".

**Tests**
- `SiteConfigAdminApiTest` (HTTP réel) : auto-création du singleton avec valeurs par défaut, `PATCH` qui met à jour la même ligne (vérifié en relisant après coup, même vigilance que le bug de l'étape 21 sur les ressources singleton - ici pas de piège équivalent puisque ce n'est pas un provider `ApiPlatform`), lecture publique limitée aux trois champs autorisés (`assertArrayNotHasKey('smtpHost', ...)`), endpoint de test d'envoi (succès en environnement de test, transport `null://null`), accès anonyme rejeté (401).
- Suite complète revérifiée verte (93 tests).
- Build Webpack complet vérifié sans erreur.
- Vérification manuelle en HTTP réel (curl) : connexion, lecture/écriture de la config, envoi de test réellement livré, **vidage de cache réellement exécuté** (`clear-cache` → `{"success":true}`) suivi d'un contrôle que le site public et l'admin répondent toujours normalement juste après.

**Décisions**
- Pas de test automatisé dédié au vidage de cache (vérifié manuellement à la place) : lancer un vrai sous-processus `cache:clear` dans la suite PHPUnit fonctionne (vérifié manuellement) mais ajoute un aller-retour disque coûteux à chaque exécution pour un gain de couverture marginal (la commande elle-même est déjà testée par Symfony) - jugé disproportionné pour ce protocole.
- `smtpPassword` renvoyé en clair par `GET /api/admin/site-config` : accès déjà réservé à `ROLE_SUPER_ADMIN`, cohérent avec le niveau de confiance déjà accordé à cet écran (ex. les mots de passe applicatifs ne sont stockés nulle part ailleurs en clair, mais ce champ n'est lu que par l'admin qui l'a lui-même saisi).

## 0.28.0 — Étapes 32+33 : menus (admin + rendu public) + ordre du menu admin

Lot autorisé explicitement par l'utilisateur ("Fait les étapes 32 et 33", chaînage explicite de ces deux étapes uniquement - pas au-delà), avec une demande additionnelle explicite bundlée dans le même lot : gestion de l'ordre (et de séparateurs) du menu de navigation **de l'admin CMS lui-même** (hors backlog initial de `INIT_ETAPES.md`), qui n'a pas de pendant public donc vit entièrement dans 32.

**Réalisé — 32 (`menus-admin`)**
- `App\Entity\Menu` (`cms/src/*`, pas un plugin) : `name`, `hookName` (nullable, nom du hook du thème actif auquel ce menu est attaché), `items` (colonne `JSON`, liste ordonnée de `{id, type: 'link'|'separator', label?, url?, target?}`) - même raisonnement que le builder (étape 10) : pas d'entité/table séparée pour les éléments, c'est une liste ordonnée de petits blocs typés qui n'a jamais besoin d'être requêtée indépendamment de son menu parent. `ApiResource` : CRUD admin complet (`/admin/menus*`, `ROLE_SUPER_ADMIN`) + `GetCollection` publique (`/menus`, via `PublicMenuCollectionProvider` - ne renvoie que les menus réellement attachés à un hook, un menu non attaché est un brouillon admin, pas destiné au rendu). Migration `Version20260904240000`.
- **Déclaration des hooks côté thème** (mécanisme laissé ouvert par `MAIN.md` à la conception de cette étape) : `template/default/theme.json` (`{"hooks": [{"name", "label"}, ...]}`), lu par `App\Service\ThemeRegistry` (thème actif codé en dur `"default"`, même hypothèse que `twig.yaml`'s namespace `@theme` - voir CLAUDE.md). Exposé en admin via `GET /api/admin/theme/hooks` (`ThemeController`, contrôleur classique). Deux hooks déclarés au lancement : `header-menu`, `footer-menu`.
- UI admin (`cms/assets/adm/pages/MenuManager.jsx` liste, `MenuForm.jsx` nom+hook+éléments) : `components/MenuItemsEditor.jsx` réordonne les éléments par glisser-déposer HTML5 natif, même technique que `BuilderCanvas` (étape 10) mais dupliquée ici plutôt que partagée cross-remote - les Menus ne sont pas un plugin et ne consomment pas de Module Federation. Ajout de lien / séparateur, édition inline (libellé/URL/cible), suppression. Route `/menus(/new|/:id/edit)` + entrée de nav "Menus".

**Réalisé — 33 (`menus-public`)**
- `template/default/assets/useMenus.js` (un seul `GET /api/menus` par chargement de page, partagé par tous les hooks) + `MenuHook.jsx` (rend, pour un nom de hook donné, le menu qui lui est attaché - ou rien du tout si aucun menu ne lui est attaché ou que le menu attaché est vide, conformément au principe "aucun plugin/fonctionnalité non-bloquant·e"). Un élément dont l'URL commence par `/` est routé en client-side (`Link`), sinon rendu en `<a>` classique avec `target` respecté ; un séparateur est une simple règle verticale (`.vr` Bootstrap).
- Câblage dans `template/default/assets/App.jsx` : `<MenuHook name="header-menu">` ajouté à la barre de navigation (après les liens statiques Pages/Portfolio/News existants), `<MenuHook name="footer-menu">` ajouté au-dessus du formulaire d'inscription newsletter dans le `<footer>`.

**Réalisé — ajout hors backlog : ordre du menu admin (`AdminMenuConfig`)**
- `App\Entity\AdminMenuConfig` (`cms/src/*`) : singleton (même schéma que `SiteConfig`/`HomeContent`, `AdminMenuConfigRepository::findOrCreate()`), un seul champ `items` (JSON, liste ordonnée de `{type: 'item', key}` ou `{type: 'separator'}`). `key` est soit une clé statique (`dashboard`, `files`, `plugins`, `menus`, `users`, `settings`, `admin-menu` - voir `cms/assets/adm/layout/staticNavItems.js`) soit `plugin:<nom>` pour un plugin chargé dynamiquement (le nom du manifeste est désormais propagé par `usePlugins.js`, qui ne renvoyait auparavant que le module distant sans le nom du plugin). `AdminMenuConfigController` (contrôleur classique, `GET`/`PATCH /api/admin/admin-menu-config`) - un remplacement intégral du tableau, pas une opération CRUD ApiPlatform. Migration `Version20260904250000`.
- `cms/assets/adm/pages/AdminMenuSettings.jsx` (route `/admin-menu`, entrée de nav "Menu admin") : deux zones - éléments "disponibles" (non encore placés, menu déroulant) et liste ordonnée réordonnable par glisser-déposer, avec bouton "Ajouter un séparateur". Enregistrement via `PATCH`.
- `cms/assets/adm/layout/resolveNavOrder.js` : fusionne l'ordre sauvegardé avec l'ensemble complet des éléments connus (statiques + plugins actifs) - tout élément connu absent de l'ordre sauvegardé (jamais configuré, ou plugin installé après coup) est ajouté en fin de liste à sa position par défaut, jamais silencieusement perdu ; une clé de l'ordre sauvegardé qui ne correspond plus à rien (ex. plugin supprimé) est ignorée. `Sidebar.jsx` l'utilise pour son rendu ; `AdminMenuSettings.jsx` l'utilise pour distinguer "disponible" de "déjà placé".

**Tests**
- `MenuAdminApiTest` (HTTP réel) : cycle CRUD complet, aller-retour du champ `items` (assertion `assertEquals`, pas `assertSame` - le type `JSON` natif MySQL ne préserve pas l'ordre d'insertion des clés d'un objet, seulement son contenu), listing public filtré aux seuls menus attachés à un hook, 401 anonyme (écriture et liste admin).
- `AdminMenuConfigApiTest` : auto-création vide, `PATCH` qui remplace et persiste sur la même ligne, 401 anonyme.
- `ThemeHooksApiTest` : la réponse reflète exactement `template/default/theme.json`, 401 anonyme.
- Suite complète revérifiée verte (102 tests, aucune dépréciation).
- Build Webpack complet vérifié (hôte `default`/`adm`/`adm_host` + les 8 remotes de plugins) sans erreur.
- **Vérification en navigateur réel** (Playwright/Chromium) : connexion admin → création d'un menu (2 liens + 1 séparateur) attaché au hook `header-menu` → page "Menu admin" → ajout de Dashboard + séparateur + Menus dans l'ordre, enregistrement, confirmation affichée → rechargement de l'admin, séparateur (`<hr>`) bien rendu dans la sidebar → site public : les deux liens du menu (dont celui via une URL externe, séparés par la règle verticale) apparaissent bien dans la barre de navigation. Aucune erreur console au-delà d'un `401` préexistant et sans rapport (`/api/me` côté site public anonyme, déjà présent avant ce lot).

**Décisions**
- `Menu.items` en colonne `JSON` unique plutôt qu'une entité `MenuItem` séparée : cohérent avec le choix déjà fait pour le builder (étape 10) - liste ordonnée de blocs, jamais interrogée indépendamment de son parent.
- Pas de couverture de test dédiée au rendu public des menus (React) : la même logique (`MenuHook`) est exercée par le seul test navigateur manuel pour les deux hooks (header vérifié en détail, footer par le même composant) - cohérent avec la couverture déjà acceptée pour `PageList`/`PortfolioItemList` (étape 18 : "même flux déjà validé, non revérifié").
- Fonctionnalité "ordre du menu admin" traitée comme faisant partie de l'étape 32 (elle n'a aucun pendant côté rendu public, donc rien à faire dans 33 à son sujet), bien que la demande utilisateur ait mentionné les deux étapes - documenté ici plutôt que dupliqué dans l'entrée du fichier `steps/33_...`.

## 0.29.0 — Étapes 34+35+36 : plugin Statistiques (collecte, géoloc/bots, dashboard admin)

Lot autorisé explicitement par l'utilisateur : demande initiale ambiguë ("finalise les deux dernières étapes") alors qu'il restait en réalité trois étapes `on-hold` (34, 35, 36) - clarifiée via question explicite, l'utilisateur a confirmé vouloir les trois. Livré en un seul commit/version : les trois étapes forment un unique plugin `plugin/stats/` avec une seule entité de collecte (`PageView`) que 35 enrichit et que 36 agrège - les séparer aurait exigé un découpage artificiel du même flux de données (même raisonnement que 22-25 Newsletter et 32-33 Menus).

**Réalisé — 34 (`stats-tracking-core`)**
- `Plugin\Stats\Entity\PageView` (`plugin/stats/src/Entity`, table `stats_page_view`) : un enregistrement par vue de page (chargement initial ou navigation SPA), champs scalaires fixes plutôt qu'un blob JSON (contrairement au builder/menus) - la structure de métriques attendue par la feuille de route est connue et fixe, et le dashboard (36) doit pouvoir `GROUP BY`/`AVG` dessus en SQL, ce qu'un JSON rendrait plus difficile plutôt que plus extensible. `Plugin\Stats\Entity\ClickEvent` (table `stats_click_event`, FK `page_view_id` CASCADE) : une ligne par clic bouton/lien, table séparée car un nombre arbitraire de clics peut survenir sur une même vue - c'est la partie réellement "extensible" du schéma. Migration `Version20260908090000`.
- `Plugin\Stats\Controller\TrackingController` (contrôleur classique, public - tout `/api` est `PUBLIC_ACCESS` par défaut hors `/api/admin`, aucune entrée `access_control` supplémentaire nécessaire) : `POST /api/stats/pageview` (crée la vue, ne fait confiance au client que pour `url`/`referrer`/`screenWidth`/`screenHeight` - tout le reste est résolu côté serveur), `POST /api/stats/pageview/{id}/heartbeat` (met à jour `timeOnPageSeconds`/`maxScrollPercent` par fusion `max()`, jamais par écrasement - un beacon tardif/dupliqué ne peut jamais faire baisser une valeur déjà enregistrée), `POST /api/stats/click`.
- **Script de tracking public** (`plugin/stats/assets/tracker.js`) : vanille JS sans dépendance, **son propre bundle Webpack** (`plugin/stats/tracker.webpack.config.js`, pas de Module Federation ni de `shared` - volontairement isolé du remote admin pour rester minimal), chargé via `<script src="/build/plugins/stats/tracker.js" defer>` dans `template/default/templates/base.html.twig` - jamais via `encore_entry_script_tags`, pour ne jamais faire partie du bundle `default` ni de son coût de parsing/chargement, conformément à la contrainte explicite de la feuille de route. Le site public étant une SPA, le script ré-arme le suivi à chaque `pushState`/`replaceState`/`popstate` (patch de l'API History), avec dé-duplication sur les navigations no-op (ex. `replaceState` vers la même URL). Le temps passé et le scroll max sont envoyés via `navigator.sendBeacon` sur `pagehide`/`visibilitychange`, plus fiable qu'un `fetch` classique à ce moment du cycle de vie de la page.

**Réalisé — 35 (`stats-geo-bot-detection`)**
- `Plugin\Stats\Service\UserAgentAnalyzer` : enveloppe `matomo/device-detector` (nouvelle dépendance Composer) plutôt qu'une table de regex maison - la détection de bots "détaillée" demandée par la feuille de route est le point fort de cette bibliothèque (base de signatures large et activement maintenue). Résout `browserName`/`browserVersion`/`osName`/`osVersion`/`deviceType`/`isBot`/`botName` à partir du header `User-Agent`, appelé une seule fois à la création de la vue.
- `Plugin\Stats\Service\GeoIpResolver` : résout le pays (ISO 3166-1 alpha-2) via l'API gratuite et sans clé `ip-api.com`, plutôt qu'une base MaxMind GeoLite2 embarquée - celle-ci exige désormais un compte (gratuit mais avec clé de licence) non disponible dans cet environnement, alors qu'ip-api.com ne demande rien et tient en un seul appel HTTP. Le trafic de ce projet reste largement sous sa limite de débit (~45 req/min) ; si cela devenait un problème, une base `.mmdb` locale pourrait remplacer l'implémentation derrière la même interface sans changement côté appelant. **L'IP brute n'est jamais persistée** - seul le pays résolu atteint `PageView`. `filter_var(..., FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)` court-circuite tout appel réseau pour une IP privée/locale (donc systématiquement en dev/test, où les requêtes viennent de `127.0.0.1`) - la suite PHPUnit et le développement local restent donc hors ligne pour cette fonctionnalité.

**Réalisé — 36 (`stats-admin-dashboard`)**
- `Plugin\Stats\Repository\PageViewRepository`/`ClickEventRepository` : agrégations DQL (`COUNT`/`AVG`/`GROUP BY`) - total de vues, temps moyen par page, scroll moyen, top pages, répartitions navigateur/OS/langue/pays/type d'appareil, compteur bot vs humain, top bots, clics les plus fréquents.
- `Plugin\Stats\Controller\Admin\StatsDashboardController` (`GET /api/admin/stats/summary?from=&to=`, `ROLE_SUPER_ADMIN` via la règle `access_control` existante) : un seul endpoint agrégé plutôt qu'un par métrique, le dashboard les affichant tous ensemble sur la même période.
- UI admin : plugin Module Federation classique (`plugin/stats/webpack.config.js`, `AdminModule.jsx` + `Dashboard.jsx`, mêmes conventions que `plugin/portfolio`) - cartes de synthèse (vues, temps moyen, scroll moyen, bot vs humain), tableau des pages les plus vues, tableaux de répartition, tableau des clics. Pas encore de sélecteur de plage de dates côté UI (le backend l'accepte déjà) - laissé à une itération ultérieure, rien dans la feuille de route ne demandait une UI de plage précise dès cette étape.

**Tests**
- `UserAgentAnalyzerTest` (unitaire) : UA navigateur classique, UA bot connu (Googlebot), UA vide.
- `GeoIpResolverTest` (unitaire, `MockHttpClient`) : IP privée/locale/vide ne déclenche aucun appel HTTP (assertion via un client qui échoue le test s'il est appelé), résolution réussie, échec de résolution retourne `null` sans lever d'exception.
- `StatsTrackingApiTest` (HTTP réel) : création de vue avec parsing UA réel, détection bot réelle, fusion `max()` du heartbeat (un beacon plus faible ne doit jamais écraser une valeur plus haute), 404 sur heartbeat d'une vue inconnue, création de clic lié à sa vue, 400 sur `pageViewId` invalide.
- `StatsDashboardApiTest` (HTTP réel) : agrégats vérifiés sur des données semées directement en base (moyennes, top pages, bot vs humain, top bots, top clics), 401 anonyme.
- Suite complète revérifiée verte (116 tests, aucune dépréciation).
- Build Webpack complet vérifié (hôte `default`/`adm`/`adm_host`, les 8 remotes de plugins existants, plus le nouveau remote `stats` et son bundle `tracker.js` indépendant) sans erreur.
- **Vérification en navigateur réel** (Playwright/Chromium) : connexion admin → page "Statistiques" → cartes et tableaux bien rendus (capture d'écran) → visite du site public (le beacon crée une vue, réellement analysée par `matomo/device-detector` comme "Headless Chrome"/"GNU/Linux"/"desktop") → clic sur un lien de la page (capté par le tracker, remonté dans "Clics les plus fréquents") → rechargement du dashboard admin → compteurs et tableaux mis à jour en conséquence. Aucune erreur console nouvelle (seul le `401` préexistant et déjà documenté sur `/api/me` côté site public anonyme apparaît). Données de vérification nettoyées de la base de dev après coup.

**Décisions**
- `PageView` en colonnes scalaires fixes plutôt qu'un JSON extensible façon builder/menus : voir ci-dessus (34) - c'est le choix inverse de ces précédents, justifié par le besoin d'agrégation SQL sur un ensemble de métriques déjà connu et fixé par la feuille de route.
- Résolution géo via API HTTP externe plutôt que base MaxMind embarquée : compromis pragmatique documenté dans la section 35 ci-dessus - aucune clé de licence disponible dans cet environnement pour télécharger GeoLite2.
- Le tag `<script>` du tracker est codé en dur dans `base.html.twig`, sans vérification que le plugin `stats` est bien actif : 404 silencieux si absent (n'empêche pas la page de fonctionner), même hypothèse déjà faite pour les routes Portfolio/News/Newsletter du thème public (celles-ci supposent déjà ces plugins installés) - aucun mécanisme de détection de plugin actif n'existe côté thème public, seulement côté admin (Module Federation dynamique).
- Pas de suivi par session/visiteur (pas d'identifiant visiteur en `localStorage`/cookie) : aucune métrique de la feuille de route (pages vues, temps moyen par page, scroll moyen, clics, pays, bots) n'en a besoin - seules des agrégations par vue de page individuelle. Pourra être ajouté plus tard si un besoin de metrics par session émerge.

## 0.29.1 — Correctif : un plugin désactivé restait actif côté site public

Signalé par l'utilisateur : "quand je désactive le plugin de newsletter dans l'administration le formulaire d'inscription à la newsletter est toujours présent sur le site public." Investigation (agent dédié) : `PluginState`/`PluginRegistry::isEnabled()` n'a jamais servi qu'à décider si l'admin doit charger le remote Module Federation du plugin (`cms/assets/adm/plugins/usePlugins.js`) - aucun autre endroit ne le consultait. Le thème public câble Portfolio/Actualités/Accueil/Newsletter en dur (`template/default/assets/App.jsx`), et le backend de chaque plugin (entités, contrôleurs, ressources ApiPlatform) est enregistré sans condition via `composer.json`/`services.yaml`/`doctrine.yaml` - désactiver un plugin ne faisait donc que le retirer du menu admin, sans aucun effet sur le site public ni sur ses routes API. Corrigé de façon générale (demandé explicitement par l'utilisateur, pas limité à Newsletter) pour les quatre plugins de contenu public existants.

**Réalisé**
- `App\Controller\PluginPublicController` (`GET /api/plugins/enabled`, `PUBLIC_ACCESS`) : liste des noms de plugins actuellement actifs uniquement (jamais `remoteEntry`/`exposedModule`, information réservée à l'admin) - c'est le pendant public de `GET /api/admin/plugins`.
- Chaque provider/processeur public d'un plugin de contenu consulte désormais `PluginRegistry::isEnabled()` :
  - `Plugin\Portfolio\State\PublishedPortfolioItemCollectionProvider`/`ItemProvider` et l'équivalent `Plugin\News\State\Published*` : liste vide / `null` (→ 404 ApiPlatform natif) quand le plugin est désactivé - même convention non-bloquante qu'un menu non attaché à un hook.
  - `Plugin\Newsletter\State\SubscriberSignupProcessor` : lève `NotFoundHttpException` sur `POST /api/newsletter/subscribers` quand désactivé - la route se comporte comme si elle n'existait pas plutôt que de renvoyer un 403 qui révélerait son existence.
  - `Plugin\Homepage\State\HomeContentProvider` : seule l'opération publique (`/homepage`, distinguée via `$operation->getUriTemplate()`) est concernée - un `HomeContent` transitoire (non persisté) est renvoyé quand désactivé, ce qui retombe sur le fallback "Bienvenue sur Solar CMS." déjà géré par `Home.jsx`, sans jamais toucher au contenu réellement enregistré. Les opérations admin (`/admin/homepage`) restent inchangées - déjà inatteignables une fois le remote Module Federation du plugin non chargé.
- Thème public : nouveau hook `template/default/assets/usePlugins.js` (`GET /api/plugins/enabled`, un seul fetch partagé par `AppShell`, même convention que `useMenus`/`useSiteConfig`) - masque désormais les liens de nav "Réalisations"/"Actualités" et le formulaire `<NewsletterSignup />` du footer quand le plugin correspondant est désactivé. Les routes elles-mêmes restent montées (un accès direct par URL retombe sur le comportement gracieux déjà produit par le backend - liste/contenu vide) : seuls les points d'entrée visibles étaient le problème signalé.

**Tests**
- `PluginGatingApiTest` (HTTP réel) : `GET /api/plugins/enabled` liste tout par défaut et exclut un plugin désactivé ; Portfolio/Actualités désactivés → liste publique vide + 404 sur l'item ; Newsletter désactivée → 404 sur l'inscription ; Accueil désactivé → contenu public vide alors que l'admin continue de voir le contenu réel.
- Suite complète revérifiée verte (122 tests, aucune dépréciation).
- Build Webpack (`encore dev`) revérifié sans erreur.
- **Vérification en HTTP réel + navigateur réel** (curl + Playwright) : désactivation de Newsletter via l'admin → `GET /api/plugins/enabled` ne la liste plus → `POST /api/newsletter/subscribers` renvoie 404 → le site public ne montre plus le formulaire (capture d'écran) → réactivation → formulaire de nouveau présent. Le plugin Newsletter avait d'ailleurs déjà été désactivé par l'utilisateur au moment du test (c'est très probablement l'action même qui a révélé le bug) - remis à `enabled: true` après vérification pour ne pas laisser l'environnement de dev dans un état différent de celui trouvé.

**Décisions**
- Portée volontairement limitée aux quatre plugins de contenu public existants (Portfolio, Actualités, Accueil, Newsletter) - le plugin Stats (34-36) n'a pas de composant public visible équivalent (son script de tracking doit rester actif indépendamment, et son "désactivable" n'a pas été demandé) ; Pages (`plugin/page`) n'a pas de bascule dédiée dans `MAIN.md` et n'a pas été touché.
- Pas de mécanisme générique (ex. un attribut/interface `PluginAware` sur les providers) pour éviter la répétition du même `if (!isEnabled(...))` dans quatre classes : la duplication reste minime (une ligne par provider) et une abstraction n'aurait rien simplifié de plus que l'appel direct déjà existant à `PluginRegistry::isEnabled()`.

## 0.30.0 — Étapes 37+38+39 : formulaires enrichis (S.E.O., image à la une, tags, catégorie)

Lot autorisé explicitement par l'utilisateur ("Fait les étapes de 37 à 39"). Livré en un seul commit/version : 38 et 39 dépendent tous les deux de la réorganisation de formulaire en deux colonnes posée par 37 (voir `../step/MAIN.md`), même raisonnement de regroupement que les lots précédents (22-25, 29-31, 32-33, 34-36).

**Réalisé — 37 (`content-form-seo-fields`)**
- Champs ajoutés à `Plugin\Page\Entity\Page`, `Plugin\Portfolio\Entity\PortfolioItem` et `Plugin\News\Entity\NewsArticle` : `seoTitle`, `seoDescription`, `ogImageUrl`, `ogType` (enum PHP `OgType` - `website`/`article`/`product`/`profile`, dupliqué une fois par plugin comme les enums de statut existants, pas de dépendance inter-plugins), `canonicalUrl`. Migrations `Version20260908120000` sur chacun des trois plugins.
- Image à la une : nouveaux champs `featuredImageUrl`/`featuredImageAlt` **uniquement sur `Page`** (n'avait aucun champ image avant cette étape) ; Réalisations/Actualités réutilisent tels quels `coverImageUrl`/`coverImageAlt` (étapes 17/19) sous ce même intitulé côté formulaire, sans champ dupliqué - décision actée dans les notes de `MAIN.md` avant de démarrer.
- Les trois formulaires admin (`PageForm.jsx`, `PortfolioItemForm.jsx`, `NewsArticleForm.jsx`) réorganisés en deux colonnes via `Row`/`Col`/`Card` (react-bootstrap) : gauche = Identité (titre + aperçu de slug calculé côté client, la vérité reste `refreshSlug()` côté serveur) / Contenu (éditeur inchangé) / S.E.O. & réseaux sociaux ; droite = Publication (inchangé) / Image à la une.

**Réalisé — 38 (`portfolio-tags`)**
- `Plugin\Portfolio\Entity\Tag` (table `portfolio_tag`, slug auto-généré) : `ApiResource` minimal (`GetCollection`/`Get`/`Post` sous `/admin/portfolio/tags`, pas de `Patch`/`Delete` - la spec ne demande que lister/créer à la volée depuis le formulaire, pas une page de gestion dédiée). Relation many-to-many `portfolio_item_tag` sur `PortfolioItem`.
- La relation n'est **pas** exposée via le mécanisme IRI natif d'ApiPlatform (aucun précédent de ce type dans le projet, et le format JSON simple par défaut - voir `api_platform.yaml` - en aurait compliqué la lecture côté frontend) : `PortfolioItem::getTags()` (groupe `portfolio:read`) renvoie un tableau plat `{id,name,slug}` calculé depuis la collection réelle, et une propriété virtuelle non mappée `tagIds` (groupe `portfolio:write`) porte l'écriture, résolue en entités `Tag` réelles par `Plugin\Portfolio\State\PortfolioItemProcessor` (décore le `PersistProcessor` Doctrine natif d'ApiPlatform, même mécanisme que `SubscriberSignupProcessor` de la Newsletter).

**Réalisé — 39 (`news-categories`)**
- `Plugin\News\Entity\Category` (table `news_category`, même forme que `Tag`), relation many-to-one sur `NewsArticle` (colonne `category_id`, `ON DELETE SET NULL`) - lecture du singulier "liste" dans `../FORM_ADD_OPTION.md` comme demandé dans les notes de `MAIN.md`. Même principe de propriété virtuelle write-only (`categoryId`) + accesseur de lecture à plat (`getCategory()`) que les tags, résolu par `Plugin\News\State\NewsArticleProcessor`.

**Bug réel trouvé et corrigé avant livraison** : la première version de `PortfolioItemProcessor`/`NewsArticleProcessor` résolvait systématiquement `tagIds`/`categoryId` à chaque écriture, y compris sur un `PATCH` partiel qui ne les mentionne pas - repéré via la vue liste, dont le bouton rapide "Archiver" (`PortfolioItemList.jsx`/`NewsArticleList.jsx`) n'envoie que `{status: 'archived'}` : ce PATCH aurait silencieusement vidé les tags/la catégorie de tout élément archivé depuis la liste. Corrigé en distinguant "le champ n'a pas été envoyé" de "le champ a été envoyé vide/null" : `PortfolioItem::$tagIds` est nullable (`null` = ne pas toucher aux tags, `[]` = tout retirer) ; `NewsArticle::$categoryId` reste nullable des deux côtés (une valeur `null` explicite doit pouvoir vider la catégorie) mais un flag séparé `$categoryIdProvided`, mis à `true` uniquement par le setter, indique si la clé était réellement présente dans la requête. Les deux formulaires admin envoient toujours ces champs à chaque sauvegarde complète, donc ce cas ne concernait que les actions rapides de la liste.

**Route piégée découverte et corrigée** : `/admin/portfolio/tags` (GET) matchait par intermittence la route générique `/admin/portfolio/{id}` de `PortfolioItem` au lieu de la route statique `tags_get_collection` de `Tag` - Symfony teste les routes dans leur ordre d'enregistrement (celui de la découverte des classes ApiResource, alphabétique par nom de fichier) et `{id}` n'a par défaut aucune contrainte de format, donc "tags" le satisfait. Corrigé par `requirements: ['id' => '\d+']` sur les opérations `Get`/`Patch`/`Delete` (admin et public) de `PortfolioItem` et `NewsArticle` (cette dernière n'avait pas encore été touchée par le bug - `Category.php` est alphabétiquement avant `NewsArticle.php` - mais restait exposée au même risque pour tout futur ajout de sous-route statique). `Page` n'a pas de sous-route statique équivalente et n'a pas été modifié.

**Tests**
- Champs S.E.O./réseaux sociaux : aller-retour complet sur les trois plugins, défaut `ogType` = `website`.
- Tags : création, association à la création, remplacement complet via `PATCH`, vidage explicite (`tagIds: []`), et surtout la régression ci-dessus (`PATCH` ne mentionnant que `status` laisse les tags intacts).
- Catégorie : création, association, changement, effacement explicite (`categoryId: null`), et la même régression côté catégorie.
- Nouveaux `PortfolioTagAdminApiTest`/`NewsCategoryAdminApiTest` (liste/création, 401 anonyme).
- Suite complète revérifiée verte (134 tests, aucune dépréciation).
- Build Webpack complet (`npm run build`) revérifié sans erreur.
- **Vérification en navigateur réel** (Playwright/Chromium, extension Claude in Chrome indisponible dans cette session - voir précédents) : connexion admin → création d'une réalisation avec un nouveau tag créé à la volée → tag bien attaché à l'enregistrement → édition → tag toujours sélectionné → action rapide "Archiver" depuis la liste → tag et champs S.E.O. toujours intacts après. Même vérification côté Actualités avec une catégorie créée à la volée, et côté Page pour les champs S.E.O./image à la une. Aucune erreur console. Données de vérification nettoyées de la base de dev après coup.

## 0.31.0 — Étapes 40 à 45 : nouveaux blocks du page builder (héro, ils nous font confiance, grille de services, notre approche, réalisations/actualités dynamiques)

Lot autorisé explicitement par l'utilisateur ("Fait les étape de 40 à 45"). Livré en un seul commit/version, même raisonnement de regroupement que les lots précédents (22-25, 29-31, 32-33, 34-36, 37-39) : les six étapes ajoutent chacune une entrée au même registre de modules du builder (`plugin/page_builder/assets/modules/registry.js`), sans dépendance entre elles sauf 44→45 (mécanisme de rendu dynamique conçu une fois, réutilisé tel quel).

**Réalisé — 40 (`builder-module-hero`)**
- `HeroModule.jsx` : eyebrow, titre (h1), texte d'accroche, bouton principal + bouton secondaire (texte/URL chacun). Rendu omis si ni titre ni texte d'accroche ne sont renseignés, boutons omis individuellement si leur texte est vide - même convention de robustesse que `CtaModule`/`DownloadModule` (étapes 13-14).

**Réalisé — 41 (`builder-module-trusted-by`)**
- `TrustedByModule.jsx` : texte d'introduction + liste de clients ajoutée/retirée dynamiquement (bouton "Ajouter un client"). "Clients/logos texte" du document source lu comme une liste de noms en texte brut (pas d'upload d'image via le média picker) - lecture actée dans `docs/step/MAIN.md` avant de démarrer.

**Réalisé — 42 (`builder-module-services-grid`)**
- `IconPicker.jsx` (`plugin/page_builder/assets/modules/`) : composant réutilisable de sélection d'icône avec recherche - une liste organisée d'environ 100 icônes `react-icons/bs`, importées nommément (`modules/icons.js`) plutôt que via `import * as` pour que webpack n'embarque que celles réellement référencées (le set complet de `react-icons/bs` pèse ~1,7 Mo non minifié pour ~2750 icônes - inutile pour une sélection curatée). `renderIcon.js` convertit la sélection en SVG statique via `react-dom/server`'s `renderToStaticMarkup` au moment de l'enregistrement (`renderToHtml` s'exécute dans le bundle admin, jamais côté thème public) - le HTML publié n'a donc aucune dépendance runtime à react-icons.
- `ServicesGridModule.jsx` : eyebrow, titre, texte d'intro, liste de services (icône + titre + description) ajoutée/retirée dynamiquement.

**Réalisé — 43 (`builder-module-process-steps`)**
- `ProcessStepsModule.jsx` : eyebrow, titre, exactement 4 cartes fixes (numéro, titre, description) - pas de bouton d'ajout/suppression, conformément à la spec.

**Réalisé — 44 (`builder-module-portfolio-feed`) et 45 (`builder-module-news-feed`)**
- Mécanisme de rendu dynamique du builder (premier besoin réel de ce type depuis l'étape 10, voir "Notes ouvertes" de `docs/step/MAIN.md`) : `PortfolioFeedModule`/`NewsFeedModule` ne rendent **pas** le contenu final à l'enregistrement - `renderToHtml()` produit un simple marqueur (`<section data-builder-feed="portfolio|news" data-count="N">...<div data-builder-feed-items>Chargement...</div>...</section>`), et c'est le thème public qui l'interprète et le complète à l'affichage (`template/default/assets/builderFeedHydrator.js`), pour refléter les *dernières* réalisations/actualités à chaque visite plutôt que celles publiées au moment de la sauvegarde de la page. Volontairement générique et sans dépendance au plugin builder (ni Module Federation ni autre) : le thème public sait juste interpréter ce contrat de placeholder documenté, donc continue de fonctionner (sans rien à hydrater) si le plugin builder est désinstallé/désactivé.
- `BuilderContent.jsx` (`template/default/assets/`) : composant partagé qui affiche le HTML admin (fallback ou builder) **et** déclenche l'hydratation des placeholders - remplace les quatre usages directs de `dangerouslySetInnerHTML` du thème (`Home.jsx`, `PageList.jsx`, `PortfolioItemDetail.jsx`, `NewsArticleDetail.jsx`), qui partagent tous le même contrat `content`.
- "Dernières N" côté hydratation : simple découpage côté client des réponses déjà triées par date décroissante et non paginées de `GET /api/portfolio`/`GET /api/news` (`PublishedPortfolioItemCollectionProvider`/`PublishedNewsArticleCollectionProvider`, `paginationEnabled: false`) - aucun nouvel endpoint backend nécessaire.

**Bug réel trouvé et corrigé pendant la vérification** : la première version de `hydrateBuilderFeeds` capturait la référence DOM du conteneur `[data-builder-feed-items]` avant l'`await` de l'appel `GET /portfolio`/`GET /news`, puis lui assignait `.innerHTML` une fois la réponse arrivée. Repéré en navigateur réel : les sections restaient bloquées sur "Chargement..." malgré des requêtes API réussies (200). Cause isolée via `MutationObserver` : peu après le montage de `BuilderContent`, un re-rendu du composant (déclenché par un des hooks fetch du parent `AppShell` - `useSiteConfig`/`useMenus`/`usePlugins` - qui résolvent tous à peu près au même moment) fait recréer intégralement les enfants du conteneur `dangerouslySetInnerHTML`, alors même que la chaîne HTML n'a pas changé de valeur. La référence capturée avant l'`await` pointe alors vers un nœud déjà détaché du document ; l'assignation `.innerHTML` réussit silencieusement mais n'a plus aucun effet visible. Corrigé en ne conservant qu'un index de position à travers l'`await`, et en relocalisant le nœud `[data-builder-feed-items]` à partir du conteneur racine (stable, lui, sur toute la durée de vie du composant) juste avant l'écriture - robuste quel que soit le nombre de re-rendus intermédiaires. Le conteneur racine (`BuilderContent`'s `containerRef`) ne recrée jamais son enfant direct, seul le sous-arbre injecté par `dangerouslySetInnerHTML` est recréé, d'où la stratégie de relocalisation plutôt qu'un autre `ref`.

**Tests**
- Aucune modification PHP dans ce lot (six étapes entièrement front-end : registre de modules + thème public) - suite PHPUnit revérifiée verte à l'identique (134 tests, aucune dépréciation) pour confirmer l'absence de régression.
- Pas de suite de tests JS dans ce projet (aucun framework du type Jest/Vitest configuré) - vérification via build Webpack complet (`npm run build`, hôte `default`/`adm`/`adm_host` + les 8 remotes de plugins existants) sans erreur, et vérification en navigateur réel ci-dessous.
- **Vérification en navigateur réel** (Playwright/Chromium via `playwright-core`, déjà présent en dépendance transitive du projet - extension Claude in Chrome indisponible dans cette session, voir précédents) : connexion admin → création d'une page → ajout des 6 nouveaux modules dans le canvas du builder → remplissage de chaque formulaire, y compris la recherche/sélection d'une icône dans `IconPicker` (recherche "gear" → sélection de `BsGear`) → capture d'écran de l'ensemble des éditeurs remplis → enregistrement → publication de la page → un item Portfolio et un article Actualités créés temporairement (via l'API admin) pour vérifier le cas "avec données" → visite du site public (`/pages`) → tous les modules statiques bien rendus (héro, liste de clients, grille de services avec icône SVG, 4 étapes) et les deux feeds dynamiques bien hydratés avec les titres réels et liens corrects vers `/portfolio/{id}`/`/news/{id}` → item et article de vérification supprimés après coup, page de vérification supprimée. Aucune erreur console nouvelle (seul le 401 préexistant et déjà documenté sur `/api/me` côté site public apparaît).

**Décisions**
- Sélecteur d'icônes construit comme composant dédié (`IconPicker`) plutôt qu'une extension du média picker existant (étape 03) : ce dernier gère des fichiers uploadés (images/PDF/ZIP), pas un ensemble fixe d'icônes vectorielles choisies dans une bibliothèque - les deux mécanismes de sélection n'ont en commun que la forme "modal + recherche", pas les données sous-jacentes.
- Carte de service partagée entre 42 et 48 (`builder-module-service-card`) : non extraite maintenant, comme acté dans les notes de `docs/step/MAIN.md` - la décision est explicitement différée au démarrage de 48, hors périmètre de ce lot.
- Mécanisme de rendu dynamique volontairement limité à un contrat HTML (attributs `data-*`) plutôt qu'un système de "widgets" plus générique : c'est le minimum suffisant pour 44/45, et 50/51 (listes sans titre/section, hors périmètre de ce lot) pourront réutiliser le même contrat avec des attributs supplémentaires plutôt que nécessiter une réécriture.

## 0.32.0 — Étapes 46 à 51 : nouveaux blocks du page builder (titre, bouton, service carte seule, tarifs, listes actualités/réalisations)

Lot autorisé explicitement par l'utilisateur ("Fait les étapes de 46 à 51"). Livré en un seul commit/version, même raisonnement de regroupement que les lots précédents (22-25, 29-31, 32-33, 34-36, 37-39, 40-45) : les six étapes ajoutent chacune une entrée au même registre de modules du builder (`plugin/page_builder/assets/modules/registry.js`), avec deux dépendances internes au lot (48→42, 50/51→44/45) déjà anticipées par ces étapes antérieures.

**Réalisé — 46 (`builder-module-heading`)**
- `HeadingModule.jsx` : sélection du niveau H1-H6 (liste déroulante) + texte. `level` est mis en liste blanche côté `render()` (retombe sur `h2` sinon) plutôt qu'interpolé tel quel - il pilote directement le nom de la balise HTML générée.

**Réalisé — 47 (`builder-module-button`)**
- `ButtonModule.jsx` : texte, URL, couleur (les 8 couleurs Bootstrap), taille (petit/moyen/grand), cible (même onglet/nouvel onglet, `target="_blank" rel="noopener noreferrer"` si nouvel onglet). Seul module du plugin à utiliser les classes Bootstrap natives (`btn btn-{couleur} btn-{sm,lg}`) plutôt qu'une classe `builder-*` maison - ses variantes visuelles correspondent 1:1 à un primitif Bootstrap déjà chargé, donc aucun CSS à écrire. Non extrait en sous-composant partagé avec le CTA existant (étape 14) ni le Héro (étape 40) comme évoqué dans les notes de l'étape : leurs schémas de style diffèrent trop (CTA = primary/secondary/outline maison, Héro = deux boutons figés) pour qu'un partage simplifie réellement quoi que ce soit.

**Réalisé — 48 (`builder-module-service-card`)**
- `serviceCard.js` (nouveau, `plugin/page_builder/assets/modules/`) : extraction du rendu HTML d'une carte service (icône + titre + description), jusque-là inline dans `ServicesGridModule` (étape 42) - décision actée dans les notes de `docs/step/MAIN.md`, tranchée au démarrage de cette étape. `ServicesGridModule` a été mis à jour pour consommer ce helper (sortie HTML strictement identique, vérifié par relecture) au lieu de dupliquer le balisage.
- `ServiceCardModule.jsx` : même carte que 42 mais isolée (une seule, pas de grille/eyebrow/titre de section) et avec en plus une couleur de fond (liste Bootstrap complète), appliquée via une classe `builder-service-card-bg-{couleur}` passée au helper partagé - 42 n'a pas été aligné en retour sur cette couleur, la grille reste sans variante de fond (non demandé par la spec de 42).

**Réalisé — 49 (`builder-module-pricing`)**
- `PricingModule.jsx` : 3 cards fixes (même convention que `ProcessStepsModule`, étape 43 - pas de bouton d'ajout/suppression de card), chacune avec icône/titre/tarif/description et sa propre liste d'options incluses dynamique (ajout/suppression, même pattern imbriqué que les clients de 41 et les services de 42).

**Réalisé — 50 (`builder-module-news-list`) et 51 (`builder-module-portfolio-list`)**
- Réutilisation du mécanisme de rendu dynamique de 44/45 plutôt qu'un nouveau : `NewsListModule`/`PortfolioListModule` émettent le **même** marqueur `data-builder-feed="news"|"portfolio"` que les feeds de section (44/45), avec deux attributs `data-*` supplémentaires (`data-order` et `data-category`/`data-tags`) - `template/default/assets/builderFeedHydrator.js` sait maintenant les lire (absents/par défaut pour un feed de 44/45, donc comportement inchangé pour ces deux modules). "Plus ancien en premier" reste un simple `.reverse()` côté client de la réponse déjà triée `DESC` par l'API (aucune modification backend). Le filtre catégorie/tags est également appliqué côté client sur la réponse déjà non paginée de `GET /api/news`/`GET /api/portfolio` (`item.category.id`/`item.tags[].id`, exposés depuis les étapes 38/39) - toujours aucun nouvel endpoint backend, même principe que la pagination "derniers N" de 44/45.
- Les éditeurs admin de ces deux modules font chacun un appel HTTP direct vers l'endpoint admin d'un autre plugin (`GET /admin/news/categories`, `GET /admin/portfolio/tags`) pour peupler leur liste déroulante/cases à cocher - nouveau petit client axios `plugin/page_builder/assets/modules/api/client.js` (même convention que le client de chaque plugin de contenu), pas un import direct : si Actualités/Réalisations est désinstallé, l'appel échoue silencieusement (liste vide) sans casser l'éditeur. Le filtre catégorie utilise un `<select>` (catégorie = relation many-to-one, un seul choix a du sens) ; le filtre tags utilise des cases à cocher comme demandé explicitement par la spec (tags = relation many-to-many, plusieurs choix possibles).

**Tests**
- Aucune modification PHP dans ce lot (six étapes entièrement front-end, comme 40-45) - suite PHPUnit revérifiée verte à l'identique (134 tests, aucune dépréciation).
- Build Webpack complet (`npm run build`) revérifié sans erreur, y compris le remote `page_builder` qui embarque les six nouveaux modules.
- **Vérification en navigateur réel** : extension Claude in Chrome disponible et connectée dans cette session (contrairement aux sessions précédentes, voir mémoire mise à jour côté agent) - utilisée directement plutôt que Playwright/Chromium. Connexion admin (session déjà active) → création de deux actualités publiées (une catégorisée "Verif Cat", une non catégorisée) et deux réalisations publiées (une taguée "Verif Tag", une non taguée) → création d'une page avec les 6 nouveaux modules remplis (Titre en H3, Bouton success/grand/nouvel onglet, Service carte avec icône et fond info, Tarifs avec 3 cards dont une option sur la première, Actualités (liste) filtrée sur "Verif Cat", Réalisations (liste) filtrée sur "Verif Tag") → publication → visite du site public (`/pages`) : tous les modules statiques bien rendus (bouton `<a class="btn btn-success btn-lg" target="_blank" rel="noopener noreferrer">` confirmé par inspection du DOM, carte service avec classe `builder-service-card-bg-info` et SVG d'icône, 3 cards de tarifs avec l'option imbriquée) et les deux listes dynamiques correctement hydratées et **filtrées** - seule l'actualité catégorisée et la réalisation taguée apparaissent, confirmant que le filtre côté hydrateur fonctionne. Aucune erreur console. Données de vérification (2 actualités, 1 catégorie, 2 réalisations, 1 tag, 1 page) supprimées de la base de dev après coup (suppression directe en base pour la catégorie/le tag, ces entités n'ayant pas d'opération `Delete` exposée par leur `ApiResource` - seules `GetCollection`/`Get`/`Post`, cf. étapes 38/39).

**Décisions**
- Carte de service partagée entre 42 et 48 : extraite dans `serviceCard.js` comme évoqué dans les notes de `docs/step/MAIN.md` (différé à 48) - voir "Réalisé — 48" ci-dessus.
- Bouton (47) non partagé avec CTA (14)/Héro (40) : voir "Réalisé — 47" ci-dessus.
- Filtre catégorie/tags des listes (50/51) résolu entièrement côté client (aucun paramètre de requête ajouté aux endpoints publics existants) : cohérent avec la décision déjà prise à 44/45 de ne pas toucher au backend pour "derniers N", et les collections publiques `/api/news`/`/api/portfolio` sont déjà non paginées donc contiennent tout ce qui est nécessaire au filtrage.

## 0.33.0 — Étape 52 : Page et i18n rejoignent le cœur

Lot autorisé explicitement par l'utilisateur ("Fait l'étape 52"). `plugin/page` et `plugin/i18n` cessent d'être des plugins Module Federation et rejoignent `cms/*`, conformément à `docs/step/steps/52_page-i18n-to-core_done.md` et à la spécification de `docs/REFORGE_PLUGINS_AND_I18N.md`.

**Réalisé**
- Code PHP déplacé tel quel de `plugin/page/src/*` et `plugin/i18n/src/*` vers `cms/src/*`, namespace `Plugin\Page\`/`Plugin\I18n\` → `App\` : `Entity/{Page,PageStatus,OgType,Lang,Translation}`, `Repository/{PageRepository,LangRepository,TranslationRepository}`, `State/{PublishedPageCollectionProvider,PublishedPageItemProvider,ActiveLangCollectionProvider}`, `Service/TranslationPoConverter`, `Twig/TranslationExtension` (nouveau répertoire `cms/src/Twig/`), `Controller/Admin/TranslationController`. Aucune logique modifiée, uniquement les `namespace`/`use`.
- `cms/config/packages/doctrine.yaml` : suppression des mappings `PluginPage`/`PluginI18n` (les entités sont déjà couvertes par le mapping `App` par défaut). `cms/config/packages/api_platform.yaml` : suppression de l'entrée `plugin/page/src/Entity` de `mapping.paths` (même raison) — `plugin/i18n` n'y avait jamais été listée, l'API de `Lang`/`Translation` fonctionnait déjà sans cette entrée.
- `cms/config/services.yaml` : suppression des imports DI `Plugin\Page\:`/`Plugin\I18n\:`, déjà couverts par `App\:`.
- `cms/composer.json` : suppression des entrées autoload PSR-4 `Plugin\Page\`/`Plugin\I18n\`, `composer dump-autoload --optimize` relancé.
- Migrations physiquement déplacées vers `cms/migrations/Page/` et `cms/migrations/I18n/`, **namespace PHP conservé tel quel** (`Plugin\Page\Migrations`/`Plugin\I18n\Migrations`, pas renommé en `App\Migrations`) — `doctrine_migrations.yaml` ne change que le chemin des deux entrées `migrations_paths` existantes. Décision clé de cette étape : le nom de version stocké en base par `doctrine/migrations` est le nom de classe complet (namespace inclus), donc renommer le namespace aurait fait perdre la trace des migrations déjà appliquées et provoqué une tentative de recréation des tables `page`/`lang`/`translation` au prochain `doctrine:migrations:migrate` — vérifié en conditions réelles : `make test` affiche "Already at la latest version" après le déplacement, aucune table recréée.
- Admin React : `PageList.jsx`/`PageForm.jsx`/`LangManager.jsx`/`TranslationManager.jsx` déplacés de `plugin/{page,i18n}/assets/` vers `cms/assets/adm/pages/` (import `./api/client` → `../api/client`), routés en dur dans `App.jsx` (`/pages`, `/pages/new`, `/pages/:id/edit`, `/langs`, `/translations`) et ajoutés à `staticNavItems.js` (`pages`, `langs`) — même famille que `UserManager`/`SiteConfig`, plus de chargement dynamique via `usePlugins.js`. Les deux `AdminModule.jsx` (contrat Module Federation) supprimés, devenus inutiles.
- `PageForm.jsx` consommait `adm_host/RichTextEditor` et `adm_host/MediaPicker` en remote Module Federation (utile pour un vrai plugin externe, plus pour du code qui vit maintenant dans le host lui-même) : remplacé par un import direct de `../components/RichTextEditor`/`../components/MediaPicker`, `Suspense` retiré autour des deux usages de `MediaPicker` devenus non paresseux (conservé autour de `BuilderCanvas`, resté un vrai remote). `page_builder/BuilderCanvas` et `page_builder/renderToHtml` restent des imports Module Federation paresseux, le plugin `page_builder` n'étant pas concerné par cette étape.
- `webpack.config.js` (racine) : le host `adm_host` déclare désormais un `remotes: { page_builder: ... }` statique (avant : aucun, tout était dynamique) — nécessaire pour que `PageForm.jsx`, non chargé dynamiquement, puisse tout de même résoudre `page_builder/BuilderCanvas`/`page_builder/renderToHtml` au build. Aucun autre plugin n'a besoin de ce traitement, ils restent tous découverts dynamiquement par `usePlugins.js`.
- `plugin/page/{plugin.json,webpack.config.js}` et `plugin/i18n/{plugin.json,webpack.config.js}` supprimés, ainsi que les deux répertoires (devenus vides). `package.json` (`build:plugins`) : retrait des deux entrées correspondantes. Build généré obsolète (`cms/public/build/plugins/{page,i18n}/`, gitignoré) supprimé manuellement.
- Commentaires de code mentionnant l'ancien emplacement mis à jour par cohérence (pas fonctionnels) : `plugin/{homepage,news,portfolio}/src/Entity/*` (référence à `Plugin\Page\Entity\Page`/`PageStatus` → `App\Entity\...`), `plugin/page_builder/webpack.config.js`, `plugin/newsletter/assets/AdminModule.jsx`, `CLAUDE.md` (section "Project state" et "Architecture notes").

**Tests**
- `cms/tests/Api/PagePublicApiTest.php`, `cms/tests/Api/TranslationAdminApiTest.php`, `cms/tests/Service/TranslationExtensionTest.php` : imports `Plugin\Page\`/`Plugin\I18n\` → `App\`, aucune autre modification nécessaire.
- `cms/tests/Api/PluginAdminApiTest.php::testListingOnlyIncludesEnabledPluginsAfterDisabling` utilisait le plugin réel "page" comme exemple de plugin qu'on n'a pas le droit de supprimer du disque (contrairement au plugin `throwaway-test-plugin` du second test) — remplacé par "portfolio" (Page n'étant plus un plugin, il n'apparaît plus dans `GET /api/admin/plugins`).
- Suite PHPUnit complète revérifiée verte (134 tests, 477 assertions, aucune dépréciation) après ce seul changement de test.
- Build Webpack complet (`npm run build`) revérifié sans erreur : les deux entrées Encore (`default`, `adm`) et les six remotes de plugins restants (`page_builder`, `portfolio`, `news`, `homepage`, `newsletter`, `stats` + `stats/tracker`) compilent, `adm` embarque désormais `PageForm`/`PageList`/`LangManager`/`TranslationManager` directement plutôt que de les charger dynamiquement.
- **Vérification en navigateur réel** (Claude in Chrome, connectée pour cette session) : connexion admin déjà active → `Pages` et `Langues` bien présents dans la nav statique (au lieu de la nav plugin) → création d'une page ("Verif Etape 52") avec un module `Titre` du builder (menu "Ajouter un module" affichant bien les 21 modules du registre, remote `page_builder` donc opérationnel) → enregistrement (brouillon) → réouverture en édition : contenu du builder et champs S.E.O. bien rechargés → passage au statut "Publiée" → enregistrement → `GET /api/pages` public renvoie bien la page avec son HTML rendu (`<h2 class="builder-heading">...`) → `Langues` liste bien `fr`/`en` (seed de la migration `Version20260904150000` intact après son déplacement) → `Traductions` : ajout d'une traduction de test via `POST /api/admin/translations` (`TranslationController`, maintenant `App\Controller\Admin\`) confirmé persisté puis supprimé. Aucune erreur console à aucune étape. Page et traduction de vérification supprimées après coup (page via un appel direct à `DELETE /api/admin/pages/4` pour éviter la boîte de dialogue `window.confirm` du bouton "Supprimer", incompatible avec l'automatisation du navigateur).

**Décisions**
- Interprétation retenue pour "Page et i18n rejoignent le cœur" (question ouverte notée dans `docs/step/MAIN.md`) : fusion complète dans le namespace `App\`, code physiquement sous `cms/src/*` — pas un espace de noms intermédiaire ni un sous-répertoire dédié, cohérent avec le traitement des autres fonctionnalités "pas un plugin" du projet (`FileController`, `PluginRegistry`, etc., tous directement sous `App\`).
- Migrations : espace de noms PHP délibérément **non aligné** sur le nouvel emplacement physique (voir "Réalisé" ci-dessus) — l'alternative (renommer en `App\Migrations`/fusionner dans `DoctrineMigrations`) aurait cassé la reconnaissance des versions déjà appliquées en base et déclenché une tentative de recréation des tables. Seul le chemin de `migrations_paths` a changé, pas le namespace.
- `page_builder` promu en remote Module Federation statique de l'hôte admin plutôt que conservé en résolution paresseuse générique : seule solution simple pour qu'un composant non dynamiquement chargé (`PageForm`, désormais statique) puisse importer un module d'un vrai plugin externe au moment du build.

## 0.34.0 — Étape 53 : `i18n-po-symfony-standard`

Lot autorisé explicitement par l'utilisateur ("Fait les étapes 53 à 58"). Remplace le format PO maison de l'étape 08 (table `translation`, `TranslationController`, `TranslationPoConverter`) par le mécanisme standard Symfony (`translations/`, `domaine.locale.po`, `TranslatorInterface`) pour la **langue de l'interface** uniquement — la langue du contenu reste hors sujet ici, traitée séparément à l'étape 58.

**Réalisé**
- Suppression complète de l'ancien système : `App\Entity\Translation`, `App\Repository\TranslationRepository`, `App\Controller\Admin\TranslationController`, `App\Service\TranslationPoConverter`, `App\Twig\TranslationExtension` (jamais utilisée dans les templates — `grep` confirmé avant suppression), page admin `TranslationManager.jsx` et sa route `/translations` (`App.jsx`), bouton "Traductions" retiré de `LangManager.jsx`.
- Nouvelle migration `cms/migrations/I18n/Version20260910120000.php` (namespace `Plugin\I18n\Migrations`, même convention que l'étape 52 — les versions déjà appliquées ne doivent pas changer de nom de classe) : `DROP TABLE translation`.
- `App\Controller\I18nCatalogController` (`GET /api/i18n/{domain}/{locale}`, public) : sert le catalogue complet d'un domaine/locale en JSON (`{clé: valeur}`), au-dessus de `TranslatorBagInterface::getCatalogue()->all($domain)` — nécessaire parce que l'admin et le site public sont des SPA React, pas du Twig, donc ne peuvent pas utiliser `trans()`/`{% trans %}` directement ; chaque app récupère son catalogue une fois par changement de langue plutôt que de faire un appel réseau par clé. Route publique (pas de garde `ROLE_SUPER_ADMIN`) car nécessaire avant même la connexion (ex. libellés du formulaire de login).
- `cms/config/packages/translation.yaml` (fichier déjà présent, recette Flex, jusque-là avec `default_path` seul et non exploité) : ajout d'un `when@test.framework.translator.paths` pointant vers `cms/tests/fixtures/translations/` — chemin de scan additionnel réservé aux tests, pour ne pas dépendre du contenu réel (`admin.*`/`public.*`/etc., qui arrivera aux étapes 54/56/57) dans le test de l'infrastructure elle-même.
- `cms/tests/Api/I18nCatalogApiTest.php` + fixtures `cms/tests/fixtures/translations/smoke.{fr,en}.po` (domaine `smoke`) : catalogue servi pour un domaine/locale connu, catalogue vide (`{}`, pas 404) pour un domaine inconnu, accès anonyme.
- `Symfony\Component\Translation\TranslatorInterface` (utilisé par l'ancien `TranslationPoConverter`... en réalité non, celui-ci n'utilisait que `PoFileLoader`/`PoFileDumper`) n'existe plus dans cette version de Symfony (retiré au profit de `Symfony\Contracts\Translation\TranslatorInterface`, qui n'expose pas `getCatalogue()`) — `I18nCatalogController` type-hint `Symfony\Component\Translation\TranslatorBagInterface` (l'interface qui porte réellement `getCatalogue()`), injecté explicitement via `#[Autowire(service: 'translator')]` car Symfony n'alias pas cette interface pour l'autowiring générique (seuls `translator.default`/`translator.logging`/`translator.data_collector` existent comme services concrets).

**Tests**
- Suite PHPUnit complète verte (130 tests, 461 assertions) après suppression des deux anciens tests (`TranslationExtensionTest`, `TranslationAdminApiTest`) et ajout du nouveau.

**Décisions**
- Aucun fichier `.po` réel n'est ajouté sous `cms/translations/` à cette étape : les vrais domaines (`admin`, `public`, un par plugin restant) sont le contenu propre des étapes 54/56/57, cette étape ne livre que l'infrastructure (répertoire scanné, chargeur, endpoint catalogue, convention de nommage).
- Domaine `messages` (utilisé par défaut par Symfony et par l'ancien système conflaté interface/contenu) volontairement laissé libre pour un usage futur générique ; les étapes suivantes utiliseront des domaines explicites (`admin`, `public`, `<plugin>`) plutôt que `messages`, pour que chaque app ne charge que son propre catalogue.
- `Lang`/`LangRepository`/`ActiveLangCollectionProvider` (étape 07) conservés tels quels — toujours la source de vérité de la liste des langues du site, sujet indépendant du format des chaînes d'interface.

## 0.35.0 — Étape 54 : `i18n-admin-ui-translation`

Lot autorisé explicitement par l'utilisateur ("Fait les étapes 53 à 58"). Traduit l'interface admin elle-même (FR + EN) au-dessus de l'infrastructure de l'étape 53, avec un bouton de changement de langue dans l'administration.

**Réalisé**
- `cms/assets/adm/i18n/TranslationContext.jsx` : `TranslationProvider`/`useTranslator()`, récupère le catalogue complet du domaine `admin` (`GET /api/i18n/admin/{locale}`) à chaque changement de langue et expose `t(key, params?)` avec interpolation `{{placeholder}}` et repli sur la clé brute si absente du catalogue. Langue choisie persistée en `localStorage` (`admLocale`), indépendante de l'utilisateur connecté, disponible dès l'écran de connexion.
- `cms/assets/adm/i18n/LocaleSwitcher.jsx` : bascule FR/EN, montée à la fois sur `Login.jsx` (coin haut-droit) et dans `Sidebar.jsx` (bas de la nav, `mt-auto`).
- `App.jsx` enveloppé dans `TranslationProvider` (avant `AuthProvider`, pour rester actif y compris sur l'écran de connexion).
- Traduction effective de toutes les pages statiques du cœur : `Login`, `Dashboard`, `FileManager`, `PluginManager`, `UserManager`, `SiteConfig`, `MenuManager`, `MenuForm`, `AdminMenuSettings`, `PageList`, `PageForm`, ainsi que les composants partagés `MenuItemsEditor`, `FileGrid`, `FileUploadForm`, `MediaPicker`, `RichTextEditor` (labels, boutons, en-têtes de table, placeholders, messages `window.confirm`/erreur/succès). `LangManager.jsx` volontairement **non traduit** à cette étape : remplacé en totalité à l'étape 55 (section "Langues" de Configuration).
- `staticNavItems.js` : chaque `label` devient une clé de traduction (`nav.dashboard`, etc.) plutôt qu'un texte brut, rendue via `t(entry.label)` dans `Sidebar.jsx`/`AdminMenuSettings.jsx`. Les entrées fournies par un plugin gardent un `label` français brut (`plugin.pluginName`/`navItem.label`, voir `usePlugins.js`) — passer ce même texte dans `t()` ne le casse pas : `t()` retombe sur la clé elle-même quand elle n'a pas d'entrée dans le catalogue, donc un libellé de plugin non traduit s'affiche simplement inchangé. Mécanisme volontairement réutilisé tel quel, pas de branchement "est-ce un plugin ?" nécessaire.
- `cms/translations/admin.fr.po` / `admin.en.po` (nouveaux, ~140 clés) : tout le texte des pages ci-dessus, domaine `admin`.
- Clé partagée `common.saveError` introduite (au lieu de dupliquer le message "Échec de l'enregistrement." sous une clé par page) - `MenuForm`/`PageForm` la réutilisent toutes les deux.

**Tests**
- Aucun changement PHP à cette étape - suite PHPUnit complète revérifiée verte (130 tests, 461 assertions) par prudence.
- **Vérification en navigateur réel** (Claude in Chrome) : bascule FR→EN persistée après rechargement (localStorage), `Dashboard`/`Pages`/`Nouvelle page` intégralement traduits (`Identity`, `Content`, `S.E.O. & social networks`, `Featured image`, `Save`/`Cancel`, etc.), le bouton "Ajouter un module" du plugin `page_builder` et son placeholder restent en français comme attendu (étape 57, pas celle-ci), aucune erreur console après le rebuild.
- **Incident rencontré et corrigé en cours de route** : un `npm run watch` laissé actif par une session précédente (PID trouvé au démarrage de cette étape) était au milieu d'une compilation avortée, ayant vidé `cms/public/build/{runtime,default}.js` sans les régénérer - page blanche sur le site public ET l'admin, signalée par l'utilisateur. Corrigé en tuant le processus et relançant `npm run build` (le seul mode de build "sûr" documenté dans la mémoire du projet - `encore dev`/`npm run watch` restent fragiles ici). Aucun rapport avec le code de cette étape.

**Décisions**
- Domaine unique `admin` pour tout le cœur statique (pas un domaine par page) : une seule requête réseau par changement de langue plutôt qu'une par page visitée.
- Interpolation de paramètres réimplémentée en `{{placeholder}}` (pas de dépendance i18n JS supplémentaire) - suffisant pour les seuls cas réels ici (compteurs, noms d'entités dans un message de confirmation).
- `LangManager.jsx` intentionnellement laissé intact (toujours en français, toujours monté sur `/langs`) : l'étape 55 le remplace entièrement plutôt que de le traduire pour le jeter ensuite.

## 0.36.0 — Étape 55 : `i18n-site-config-languages`

Lot autorisé explicitement par l'utilisateur ("Fait les étapes 53 à 58"). Décision préalablement confirmée avec l'utilisateur (voir doute signalé dans `docs/step/MAIN.md`) : la page dédiée `LangManager.jsx` (étape 07) est **remplacée**, pas doublée, par une nouvelle section "Langues" dans Configuration.

**Réalisé**
- `cms/assets/adm/components/LanguagesSection.jsx` (nouveau) : reprend la logique de l'ancien `LangManager.jsx` (liste, ajout, bascule active/inactive, suppression - mêmes endpoints `/admin/langs`) mais en composant monté dans `SiteConfig.jsx` (sous "Cache"), traduit (`t()`, domaine `admin`) et avec un libellé de colonne renommé "Statut public" pour clarifier ce que le flag `active` pilote réellement (déjà utilisé par `ActiveLangCollectionProvider`/`GET /api/langs`, inchangé - cette étape ne touche qu'à l'UI admin, pas au comportement déjà en place depuis l'étape 07).
- `LangManager.jsx` supprimé, ainsi que sa route (`/langs`, `App.jsx`) et son entrée de nav statique (`staticNavItems.js`) - `AdminMenuSettings`/`resolveNavOrder` gèrent déjà silencieusement une clé de config obsolète (`langs`) si un ordre de menu personnalisé y faisait référence (voir la garantie déjà documentée dans `resolveNavOrder.js`, étape 32).
- `cms/translations/admin.{fr,en}.po` : retrait de `nav.langs` (devenue orpheline), ajout de `common.add` et 13 clés `languages.*`.

**Tests**
- Aucun changement PHP (aucun nouvel endpoint, `Lang`/`LangRepository`/`ActiveLangCollectionProvider` inchangés) - suite PHPUnit revérifiée verte (130 tests, 461 assertions).
- **Vérification en navigateur réel** : section "Langues" visible en bas de `/adm/settings`, `fr`/`en` listées avec statut "Active", plus d'entrée "Langues" dans la nav latérale, aucune erreur console après rebuild.

**Décisions**
- Aucune migration de données nécessaire : la table `lang` et son champ `active` existent depuis l'étape 07 et gardent exactement le même sens: seule l'UI qui les pilote a changé d'emplacement.

## 0.37.0 — Étape 56 : `i18n-public-ui-translation`

Lot autorisé explicitement par l'utilisateur ("Fait les étapes 53 à 58"). Traduit l'interface du thème public (FR + EN) et ajoute son sélecteur de langue, masqué automatiquement s'il n'y a qu'une langue active.

**Réalisé**
- `template/default/assets/i18n/TranslationContext.jsx` : même principe que l'étape 54 côté admin (`TranslationProvider`/`useTranslator()`, catalogue du domaine `public`, `t(key, params?)`, persistance `localStorage` sous `publicLocale`) mais combine aussi la récupération des langues actives (`GET /api/langs`, étape 55) dans le même provider : au montage, la locale stockée est validée contre les codes actifs (repli sur `fr` si absente/inconnue, ou sur le premier code actif si `fr` lui-même n'est pas actif) - un seul fetch partagé entre la validation de la langue et le sélecteur public, pas un hook séparé. Le contexte expose aussi `activeLangs` pour cette raison.
- `template/default/assets/i18n/LanguageSwitcher.jsx` : un lien par langue active, **rendu `null` si moins de deux langues actives** (exigence explicite de l'étape) - monté dans `App.jsx` à côté de `AuthNav`.
- `App.jsx` enveloppé dans `TranslationProvider`. Traduction effective de tout le thème : navigation (`Accueil`/`Pages`/`Réalisations`/`Actualités`/`Connexion`/`Inscription`/`Déconnexion`), `Home`, `Login`, `Register`, `Profile`, `VerifyEmail`, `NewsletterSignup`, `PageList`, `PortfolioItemList`/`PortfolioItemDetail`, `NewsArticleList`/`NewsArticleDetail`. `MenuHook.jsx` et `BuilderContent.jsx` n'avaient aucune chaîne statique (contenu entièrement piloté par les données admin) - non modifiés.
- **Portée volontairement élargie à Portfolio/News côté rendu public** : bien que Portfolio et News restent de vrais plugins (`plugin/portfolio`, `plugin/news`), leurs vues **publiques** (`PortfolioItemList.jsx`, `NewsArticleList.jsx`, etc.) vivent physiquement dans `template/default/assets/` (choix déjà fait aux étapes 17-20, avant même l'existence du mécanisme de plugin dynamique côté public) - donc traitées ici comme chaîne du thème, pas comme fichier de langue de plugin (étape 57, qui couvre le côté **admin** de ces mêmes plugins - `plugin/{portfolio,news}/assets/*.jsx` - resté en français à cette étape, comme prévu).
- `cms/translations/public.fr.po` / `public.en.po` (nouveaux, ~40 clés), domaine `public`.

**Tests**
- Aucun changement PHP - suite PHPUnit revérifiée verte (130 tests, 461 assertions).
- **Vérification en navigateur réel** : sélecteur EN/FR visible (2 langues actives) et fonctionnel sur `/` et `/pages`, persistance après navigation client-side, retombée correcte sur le texte brut pour tout module de plugin non concerné par cette étape, aucune erreur console.

**Décisions**
- Locale publique et "langue de contenu" (étape 58) partagent délibérément le même état (`locale` du `TranslationContext`) plutôt que deux mécanismes séparés - c'est la réponse déjà confirmée par l'utilisateur à la question posée avant de démarrer ce lot ("le sélecteur d'interface doit-il aussi piloter la langue du contenu affiché ?" → oui).
- Pas de négociation de langue basée sur `navigator.language` au premier chargement : repli simple sur `fr` (ou la première langue active) - cohérent avec le comportement déjà choisi côté admin (étape 54) plutôt que d'introduire un mécanisme différent entre les deux apps.

## 0.37.1 — Correction étape 55 : la page Langues est restaurée

L'utilisateur a corrigé l'étape 55 en cours de route ("On c'est mal compris, garde le menu i18n dans l'administration, ce que je souhaite c'est d'avoir un sélecteur de langue par défaut dans le menu configuration, seulement ça.") : l'interprétation initiale (fusion complète de `LangManager.jsx` dans Configuration) était incorrecte. Comportement corrigé :
- `LangManager.jsx` restauré comme page admin à part entière (route `/langs`, entrée de nav `nav.langs`), CRUD complet (ajout/activation publique/suppression) inchangé par rapport à l'étape 07/54.
- Configuration (`SiteConfig.jsx`) ne gagne qu'un unique champ "Langue par défaut" (`<Form.Select>` peuplé depuis `/admin/langs`, valeur `""` = "Non définie"), intégré au même formulaire général existant (mêmes `save()`/bouton "Enregistrer" que le reste de la section "Général") - pas de nouvelle section dédiée, conformément à "seulement ça".
- Nouveau champ persisté : `App\Entity\SiteConfig::$defaultLocale` (nullable, `VARCHAR(10)`), migration `cms/migrations/Version20260910180000.php`. Exposé en lecture/écriture sur `GET|PATCH /api/admin/site-config` et en lecture sur le `GET /api/site-config` public (nécessaire : c'est cette route, accessible sans connexion, qui alimente le repli de langue de l'écran de connexion admin).
- `defaultLocale` est maintenant réellement consommé : `TranslationContext` (admin, étape 54) et `TranslationContext` (public, étape 56) l'utilisent tous les deux comme valeur de repli à la place du "fr" codé en dur, mais **seulement** tant qu'aucune préférence n'est déjà stockée en `localStorage` pour ce visiteur/navigateur - une préférence déjà choisie n'est jamais écrasée.
- **Piège rencontré** : after avoir appliqué la migration côté `app_test` (via `make test`), la base `app` (dev, celle que le navigateur utilise réellement) était encore en retard de deux migrations (celle-ci et le `DROP TABLE translation` de l'étape 53, jamais explicitement appliquée en dev jusque-là) - `GET /api/site-config` échouait en 500 (`Unknown column 's0_.default_locale'`), page blanche. Corrigé par un `make migrate` explicite. **Leçon générale pour la suite de ce lot** : `make test` ne migre que `app_test`, jamais `app` - après toute migration ajoutée pendant une session multi-étapes comme celle-ci, lancer `make migrate` en plus, pas seulement `make test`.
- Suite PHPUnit revérifiée verte (130 tests, 461 assertions) après ce correctif, ainsi qu'une vérification en navigateur réel (Configuration affiche uniquement le sélecteur, Langues fonctionne comme avant, changement de langue par défaut sauvegardé et repris par un nouveau visiteur sans préférence stockée).

## 0.38.0 — Étape 57 : `i18n-per-plugin-language-files`

Lot autorisé explicitement par l'utilisateur ("Fait les étapes 53 à 58"). Fichiers de langue FR + EN pour chacun des 6 plugins restants (Réalisations, Actualités, Accueil, Newsletter, Page builder, Statistiques) - Page et i18n n'en ont plus besoin depuis l'étape 52 (couverts par 54/56).

**Réalisé**
- **Mécanisme retenu (nouveau, pas de précédent direct)** : chaque plugin obtient sa **propre copie** d'un petit hook autonome `useDomainTranslator(domain)` (~60 lignes, `plugin/<name>/assets/useDomainTranslator.js`, identique dans les 6 plugins) plutôt qu'un hook partagé exposé par l'admin host via Module Federation. Raison : un hook ne peut pas être chargé en `React.lazy()` comme un composant (seul mécanisme déjà en place pour partager du code cross-remote, voir `MediaPicker`/`RichTextEditor`), donc le partager aurait exigé une nouvelle mécanique. Le hook lit/réagit à la locale de l'admin (`localStorage.admLocale` + un nouvel événement same-window `adm-locale-change`, dispatché par `TranslationContext.jsx` de l'étape 54 à chaque changement - un `localStorage.setItem` dans le même onglet ne déclenche jamais l'événement natif `storage`) et récupère son propre catalogue de domaine (`GET /api/i18n/<domain>/<locale>`, étape 53) via `fetch()` brut (pas le client axios propre à chaque plugin, chemins d'import différents selon les plugins - `fetch('/api/...')` reste identique partout). Chaque plugin reste ainsi **totalement découplé** de l'admin host pour l'i18n - aucun `remotes: { adm_host }` supplémentaire nécessaire pour ce seul besoin.
- Domaines créés : `cms/translations/{portfolio,news,homepage,newsletter,page_builder,stats}.{fr,en}.po`.
- Traduction effective de tout le code admin de chaque plugin : `PortfolioItemList`/`PortfolioItemForm` (+ tags), `NewsArticleList`/`NewsArticleForm` (+ catégorie), `HomepageForm`, `CampaignList`/`CampaignForm`/`CampaignSend`/`SubscriberList` (Newsletter), `Dashboard` (Stats), et l'intégralité du plugin Page builder : `BuilderCanvas.jsx` (menu "Ajouter un module", en-tête de bloc, état vide) + les 16 modules (`ImageModule`, `SliderModule`, `DownloadModule`, `CtaModule`, `TextModule`, `HeroModule`, `TrustedByModule`, `ServicesGridModule`, `ProcessStepsModule`, `PortfolioFeedModule`, `NewsFeedModule`, `HeadingModule`, `ButtonModule`, `ServiceCardModule`, `PricingModule`, `NewsListModule`, `PortfolioListModule`) + `IconPicker.jsx` (composant partagé par plusieurs modules) - domaine unique `page_builder` pour tout le plugin (~72 clés), pas un domaine par module.
- Chaque module du registre (`modules/registry.js`) a son `label` converti d'un texte français brut en clé de traduction (ex. `module.hero.label`) - `BuilderCanvas.jsx` fait `t(module.label)` à la fois dans le menu déroulant "Ajouter un module" et l'en-tête de chaque bloc posé sur le canvas, même mécanisme de repli déjà validé à l'étape 54 pour `staticNavItems.js`.
- **Scope volontairement exclu** : les `defaultProps` texte (ex. CTA "En savoir plus", Download "Télécharger") sont du contenu par défaut pré-rempli à l'ajout d'un module, pas des chaînes d'interface - laissés tels quels, pas traduits. Le texte "Chargement..." figé dans le HTML produit par `render()` des modules Feed/List (`data-builder-feed-items`) est écrit **au moment de l'enregistrement en admin**, dans une langue déjà arbitraire pour un visiteur public qui la lira potentiellement bien plus tard, dans sa propre langue à lui - limite architecturale préexistante (le HTML est figé, `render()` est une fonction pure sans accès aux hooks), non résolue dans le cadre de cette étape.

**Tests**
- Aucun changement PHP - suite PHPUnit revérifiée verte (130 tests, 461 assertions).
- **Vérification en navigateur réel** : bascule FR→EN en direct sur `/adm/pages/new` avec un module Titre posé sur le canvas (`HEADING`/`Remove`/`Heading text`/`Add a module` - confirme que le mécanisme `adm-locale-change` traverse bien la frontière Module Federation), ainsi que Newsletter, Réalisations, Statistiques (tableau de bord complet avec ses propres données de clics de test), Accueil - toutes intégralement traduites, aucune erreur console.

**Décisions**
- Domaine `page_builder` unique pour tout le plugin plutôt qu'un domaine par module : un seul fetch de catalogue par changement de langue au lieu de potentiellement 17, cohérent avec le choix déjà fait à l'étape 54 (domaine `admin` unique pour tout le cœur statique).
- Le hook dupliqué (plutôt que partagé) est un choix déjà précédenté dans ce projet (`MenuItemsEditor` vs `BuilderCanvas`, même logique de drag & drop dupliquée plutôt que partagée cross-remote, voir étape 32) - appliqué ici pour la même raison structurelle (pas de mécanisme de partage disponible pour ce cas précis).

## 0.39.0 — Étape 58 : `i18n-content-language-picker`

Dernier lot du batch autorisé explicitement par l'utilisateur ("Fait les étapes 53 à 58"). Sélecteur de langue dans les formulaires de contenu (Page, Réalisations, Actualités, Accueil) - la **langue du contenu**, distincte de la langue d'interface (53-57). Conception validée avec l'utilisateur avant de démarrer (voir la question posée en tout début de ce lot) : stockage en overlay générique, sélecteur public lié au switch d'interface (56).

**Réalisé**
- `App\Entity\ContentTranslation` (nouveau, `cms/src/Entity/`) : store générique `(entityType, entityId, field, locale) → value`, contrainte d'unicité sur ce quadruplet. Volontairement sans connaissance d'aucun plugin - `entityType` est juste une chaîne libre choisie par l'appelant ("page", "portfolio_item", "news_article", "homepage"). Migration `cms/migrations/Version20260910190000.php`.
- `App\Service\ContentTranslator::translate(entity, entityType, entityId, locale, fields, entityManager)` : service générique appelé par le provider public de chaque type de contenu avec sa propre liste de champs traduisibles (`title`, `content`, `seoTitle`, `seoDescription` pour Page/Portfolio/News ; `content` seul pour Homepage, qui n'a ni titre ni SEO). Détache l'entité de l'EntityManager avant d'appeler ses propres setters (`setTitle()`, etc.) avec les valeurs traduites - jamais flush, un simple overlay pour une réponse HTTP en lecture seule. i18n ne référence ainsi jamais aucun plugin ; chaque plugin dépend de `App\Service\ContentTranslator` (core), pas l'inverse.
- `App\Controller\Admin\ContentTranslationController` (`GET`/`PATCH /api/admin/content-translations`) : backend générique, un seul contrôleur pour tous les types de contenu (`GET` renvoie toutes les langues groupées `{locale: {field: value}}` en un seul appel ; `PATCH` upsert un lot de champs pour une langue donnée).
- `ContentTranslationPanel.jsx` (nouveau, `cms/assets/adm/components/`) : composant partagé exposé par l'admin host via Module Federation (même mécanisme que `MediaPicker`/`RichTextEditor`, étape 06) - un onglet par langue active autre que la langue par défaut (étape 55), un champ par entrée de `fields` (texte / textarea / HTML via `RichTextEditor`, réutilisé directement puisque ce composant vit dans le même bundle host). Affiche "Enregistrez d'abord ce contenu" tant que l'entité n'a pas encore d'id réel (nouvelle création). Intégré dans `PageForm.jsx` (import direct, cœur) et, en remote (`adm_host/ContentTranslationPanel`), dans `PortfolioItemForm.jsx`, `NewsArticleForm.jsx`, `HomepageForm.jsx`.
- **Rendu public lié au sélecteur de langue d'interface** (décision confirmée avant de démarrer) : les quatre providers publics (`PublishedPageCollectionProvider`/`ItemProvider`, `PublishedPortfolioItemCollectionProvider`/`ItemProvider`, `PublishedNewsArticleCollectionProvider`/`ItemProvider`, `HomeContentProvider`) lisent un paramètre de requête `?locale=` (via `RequestStack`) et appellent `ContentTranslator::translate()` avant de renvoyer l'entité. Le thème public (`PageList`, `PortfolioItemList`/`Detail`, `NewsArticleList`/`Detail`, `Home.jsx`) passe désormais `{ params: { locale } }` (lu depuis `useTranslator()`, le même état que le sélecteur de langue d'interface de l'étape 56) sur chacun de ses appels `GET`.

**Tests**
- `cms/tests/Api/ContentTranslationAdminApiTest.php` (nouveau) : upsert puis liste groupée par langue, ré-upsert remplace (pas de doublon), 401 anonyme - testé avec un `entityType` fictif ("widget") pour vérifier que le mécanisme ne connaît vraiment aucun plugin.
- `cms/tests/Api/PagePublicApiTest.php` : deux nouveaux cas - `?locale=` surcharge le titre traduit et retombe sur le contenu de base pour un champ non traduit ; sans `?locale=`, toujours les valeurs de base.
- Suite PHPUnit complète verte (135 tests, 476 assertions).
- **Vérification en navigateur réel** (Claude in Chrome) : création d'une page "Content translation test", panneau "Translated content" affichant bien "Save this content first..." avant le premier enregistrement, puis un onglet "English" une fois sauvegardée ; traduction du titre enregistrée ("Translation saved.") ; page publiée ; site public affichant "EN: Content translation test" avec le sélecteur sur EN et "Content translation test" (valeur de base) en basculant sur FR, sans rechargement de page. Page et traduction de test supprimées après coup.
- **Incident rencontré, sans rapport avec le code de cette étape** : un troisième `npm run watch` externe (lancé dans une session tmux hors du contrôle de cette session, déjà rencontré aux étapes 54 et 57) a de nouveau corrompu le build en cours de vérification - même remède (processus tué, `npm run build` relancé).

**Décisions**
- `ContentTranslation` volontairement **sans relation Doctrine** vers `Page`/`PortfolioItem`/etc. (juste `entityType` + `entityId` en colonnes plates) : une vraie relation ORM aurait obligé i18n à connaître et importer les classes d'entité de chaque plugin, exactement la dépendance interdite par la contrainte de l'utilisateur.
- Détacher l'entité avant d'appliquer la traduction plutôt que cloner l'objet : plus simple, et sans risque puisqu'aucun de ces providers publics n'appelle jamais `flush()`.
- Le sélecteur de langue de contenu (`ContentTranslationPanel`) ne propose que les langues actives *autres* que la langue par défaut (étape 55) - éditer la langue par défaut du contenu se fait déjà via les champs principaux du formulaire, un onglet redondant aurait juste prêté à confusion.

## 0.40.0 — Étape 58 (suite) : refonte de l'éditeur de traduction de contenu + Newsletter

Corrections apportées par l'utilisateur en aval de la release 0.39.0, avant validation finale de l'étape 58. Trois retours distincts, tous traités dans ce lot :
1. Le panneau `ContentTranslationPanel` ne couvrait que titre/texte/SEO, pas les blocs du page builder, et n'existait pas pour la Newsletter ("je souhaite aussi pouvoir éditer les blocks (page builder)" + confirmation d'inclure la Newsletter avec capture du nom et de la langue du navigateur à l'inscription).
2. Refonte UX complète demandée ("Presque, maintenant tu déplace la section Contenue traduit en haut de la page avec un menu de navigation avec [français] [anglais] et quand on clique dessus sa remplace le formulaire avec la langue sélectionné. Pas une card à droite") : la card latérale devient un menu de navigation en haut de page qui remplace entièrement le formulaire.
3. Bug bloquant signalé après la refonte : un bloc texte du page builder modifié sur une langue se modifiait aussi sur l'autre ("Modifier en block en Anglais le modifie aussi en Français").

**Réalisé**
- **`ContentTranslationPanel.jsx` supprimé** (composant + son entrée `exposes` dans le `webpack.config.js` racine), remplacé par un hook `useContentLocale(entityType, entityId)` dupliqué dans chaque plugin de contenu (même raison qu'à l'étape 57 : un hook ne peut pas être `React.lazy()`-chargé comme un composant) - `cms/assets/adm/hooks/useContentLocale.js` (cœur, pour `PageForm`) + copies identiques dans `plugin/{portfolio,news,homepage,newsletter}/assets/`.
- Le hook expose `activeLangs`, `editingLocale`/`setEditingLocale`, `isDefaultLocale`, `fieldValue(baseValue, key)`/`setField(key, setBaseValue)` (lecture/écriture qui route vers l'état de base ou vers un overlay de traduction en mémoire selon la langue en cours d'édition) et `saveAllTranslations(entityId, baseFieldValues, builderFieldName)` (appelée après le `POST`/`PATCH` du formulaire, upsert un lot de traductions par langue - convertit aussi le JSON du builder en HTML via `page_builder/renderToHtml` avant envoi quand `builderFieldName` est fourni).
- Chaque formulaire de contenu (`PageForm`, `PortfolioItemForm`, `NewsArticleForm`, `HomepageForm`, et désormais `CampaignForm` de la Newsletter) affiche un menu `<Nav variant="pills">` en haut de page (langues actives, visible seulement si plus d'une langue active) : cliquer une langue **remplace tous les champs traduisibles du formulaire** par leur valeur pour cette langue (titre, contenu/blocs du builder, SEO), avec repli "copie conforme" sur la valeur de base tant qu'aucune traduction n'existe pour cette langue - pas de traduction sauvegardée, pas de patch partiel. Les sections non traduisibles (slug, image à la une/OG, statut de publication, tags/catégorie) restent gérées uniquement via la langue par défaut, masquées sinon (`{isDefaultLocale && (...)}`).
- **Extension à la Newsletter** : `CampaignForm.jsx` gagne le même mécanisme (`subject` + `content`, pas de builder - `Campaign` n'a pas d'intégration page builder). `Subscriber` (`plugin/newsletter/src/Entity/Subscriber.php`) gagne deux champs optionnels, `name` et `locale` (code de langue du navigateur au moment de l'inscription), migration `plugin/newsletter/migrations/Version20260910200000.php`. `NewsletterSignup.jsx` (thème public) ajoute un champ Nom optionnel et capture silencieusement `locale` depuis `useTranslator()` à la soumission. `SubscriberList.jsx` (admin) affiche les deux nouvelles colonnes.
- **Bug critique corrigé — blocs du page builder partagés entre langues** : `BuilderCanvas`/`RichTextEditor` ne re-synchronisent délibérément jamais leur état interne depuis la prop `value` après le montage (protection du curseur pendant la frappe, voir étape 09/15) - en changeant de langue, l'éditeur gardait donc le contenu de la langue précédente au lieu de recharger celui de la nouvelle. Corrigé en ajoutant `key={editingLocale}` sur `<BuilderCanvas>`/`<RichTextEditor>` dans les 5 formulaires : React démonte et remonte intégralement l'éditeur à chaque changement de langue, le forçant à se réinitialiser avec la bonne valeur.
- **Deux bugs supplémentaires découverts pendant la vérification de ce correctif, tous deux dans `RichTextEditor.jsx`** (composant partagé, donc le correctif profite à tous ses usages, pas seulement au contenu traduit) :
  - Sous `React.StrictMode` (actif dans l'app admin), le montage d'un composant déclenche un cycle monter→démonter→remonter en dev. Quill mute en place le nœud DOM qui lui est passé (lui ajoute des classes, insère sa barre d'outils comme frère juste avant), et l'ancien cleanup (`quillRef.current = null`) ne défaisait jamais cette mutation - un second remontage sur le même nœud laissait donc deux barres d'outils empilées. Visible en particulier sur `CampaignForm.jsx` (Newsletter), premier formulaire de ce lot testé par une navigation fraîche complète. Corrigé en montant Quill sur un élément enfant fraîchement créé à chaque montage, et en vidant tout le conteneur au démontage (`container.innerHTML = ''`).
  - Le `placeholder` passé à Quill n'était lu qu'une fois, à la construction, alors qu'il provient du hook de traduction du plugin (`useDomainTranslator`, étape 57) dont le catalogue se charge de façon asynchrone - si l'éditeur (chargé en `React.lazy()` via Module Federation) montait avant la fin de ce fetch, la clé brute non traduite (ex. `newsletter.contentPlaceholder`) restait affichée pour toujours. Corrigé en synchronisant l'attribut DOM `data-placeholder` (celui que Quill lit réellement pour l'afficher) à chaque changement de la prop `placeholder`, sans re-semer `value` ni perturber le curseur.

**Tests**
- Aucun changement de schéma en dehors de `plugin/newsletter/migrations/Version20260910200000.php` (colonnes `name`/`locale` sur `newsletter_subscriber`).
- `cms/tests/Api/NewsletterSubscriberPublicApiTest.php` : `testNameAndLocaleAreCapturedWhenProvided`, `testNameAndLocaleAreOptional`.
- Suite PHPUnit complète revérifiée verte (137 tests, 482 assertions).
- **Vérification en navigateur réel** (Claude in Chrome), reprise complète après le correctif `key={editingLocale}` : sur `PageForm.jsx`, ajout d'un bloc texte sur l'onglet EN, bascule FR, vérification que le bloc n'apparaît pas côté FR, ré-édition indépendante des deux blocs, confirmé par inspection directe des lignes `content_translation` en base. Même scénario rejoué intégralement sur `NewsArticleForm.jsx` (Actualités) avec le page builder : titre + bloc texte saisis sur EN, bascule FR (repli "copie conforme" vers l'état de base vide vérifié), saisie d'un titre + bloc texte différents sur FR, retour sur EN confirmant que le contenu anglais n'a pas bougé, sauvegarde puis rechargement de la page confirmant la persistance correcte des deux versions. `CampaignForm.jsx` (Newsletter) : reproduction du bug de double barre d'outils + placeholder non traduit sur `/adm/newsletter/new` (rechargements répétés pour confirmer que ce n'était pas un glitch ponctuel), correctif vérifié (une seule barre d'outils, placeholder "Contenu de la campagne..." affiché), puis cycle complet saisie EN → bascule FR (repli confirmé) → saisie FR → retour EN (contenu anglais intact) → enregistrement → rechargement (les deux langues bien persistées). Portfolio et Accueil re-vérifiés visuellement (menu de langue, champs non traduisibles correctement masqués hors langue par défaut). Données de test supprimées après chaque vérification (page, actualité, campagne).

**Décisions**
- Hook dupliqué (`useContentLocale.js`) plutôt que composant partagé unique : cohérent avec la contrainte déjà rencontrée à l'étape 57 (un hook ne traverse pas Module Federation comme un composant) - la duplication était déjà le choix retenu pour `useDomainTranslator`.
- `saveAllTranslations` envoie systématiquement un instantané complet résolu par champ (valeur traduite si elle existe, sinon valeur de base) plutôt qu'un patch partiel - plus simple côté backend (un seul type d'upsert, pas de fusion), et cohérent avec le principe "copie conforme" affiché à l'écran.
- Le correctif `RichTextEditor.jsx` (double barre d'outils, placeholder figé) est resté dans le composant partagé plutôt que contourné localement dans `CampaignForm.jsx` : les deux causes (React.StrictMode + mutation DOM en place par Quill ; lecture unique d'une prop qui peut arriver après coup) sont structurelles au composant, pas spécifiques à la Newsletter - un usage futur de cet éditeur derrière un remount par `key` ou un chargement de traduction lent aurait reproduit les deux bugs à l'identique.

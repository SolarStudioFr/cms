# Vue d'ensemble des étapes

> Découpage du backlog de `../INIT_ETAPES.md` (section 5) en étapes, conformément à la section 6, complété par plusieurs ajouts/ajustements explicites de l'utilisateur (gestionnaire de plugins, média picker, nettoyage et ré-optimisation des images en deux actions séparées, éditeur de contenu générique de secours, vidage de cache, hooks de menus, tracking en AJAX différé — détaillés dans « Notes ouvertes » ci-dessous et dans chaque fichier `steps/`). Les fichiers détaillés `steps/{id}_{slug}_{status}.md` et le protocole `action/{order}_{slug}.md` ont été créés à la demande explicite de l'utilisateur — voir avertissement en tête de `INIT_ETAPES.md`.
>
> L'ordre ci-dessous suit l'ordre des fonctionnalités en section 5 (fichiers → gestionnaire de plugins → multilangue → éditeur de contenu générique puis builder → gestionnaires de contenu qui s'appuient dessus → newsletter → utilisateurs → config → menus → statistiques), qui correspond déjà à un ordre de dépendances raisonnable. Cet ordre n'est pas contractuel : il pourra être révisé au moment de démarrer réellement le travail.
>
> Statut possible : `on-hold` (pas commencé) / `in-progress` / `done`.

## Gestion des fichiers (pas un plugin — `cms/*`)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 01 | `file-storage-backend` | Entity `File` générique + service de stockage (voir spec complète dans `steps/01_...`) : arborescence `upload/img/{source,webp,thumbnail}`, `upload/pdf`, `upload/zip`, pipeline webp + 4 miniatures | done |
| 02 | `file-manager-admin-ui` | Gestionnaire de fichiers en admin (parcourir / uploader / supprimer) | done |
| 03 | `media-picker-component` | Modal réutilisable de sélection/ajout de fichier, configurable par type (images, ou PDF+ZIP, etc.), utilisée par tout éditeur de contenu | done |
| 04 | `file-cleanup-unused` | Bouton admin : supprimer les images non utilisées | done |
| 05 | `file-reoptimize-images` | Bouton admin : ré-optimiser toutes les images (webp + miniatures depuis la source) | done |

04 et 05 sont **deux boutons/actions distincts**, pas une seule étape.

## Gestionnaire de plugins (pas un plugin — `cms/*`)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 06 | `plugin-manager-admin` | Page d'administration listant les plugins détectés (`PluginRegistry`) : activer, désactiver, supprimer (avec confirmation) | done |

## Multilangue (plugin)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 07 | `i18n-plugin-core` | Squelette du plugin multilangue, entity Langue, menu admin pour ajouter/gérer les langues actives (FR + EN au départ) | done |
| 08 | `i18n-translation-management` | Outil d'édition/création de traductions en admin (composant React + backend composer) + intégration au rendu public + **export/import au format PO** | done |

## Éditeur de contenu — fallback générique (pas un plugin — `cms/*`)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 09 | `content-editor-fallback` | Éditeur WYSIWYG simple (type TinyMCE), utilisé par défaut par les plugins de contenu tant que le builder n'est pas actif — voir principe « aucun plugin non-bloquant » ci-dessous | done |

## Éditeur de contenu (drag & drop, page builder — plugin unique)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 10 | `builder-core` | Cœur du builder : canvas drag & drop, modèle de données pour stocker les modules d'une page, registre de modules conçu pour être extensible | done |
| 11 | `builder-module-image` | Module Image simple | done |
| 12 | `builder-module-slider` | Module Slider (carrousel d'images) | done |
| 13 | `builder-module-download` | Module « Télécharger un fichier » (PDF/ZIP), sélection via le média picker filtré | done |
| 14 | `builder-module-cta` | Module Appel à l'action (CTA) | done |
| 15 | `builder-module-text` | Module Texte — éditeur WYSIWYG complet (HTML, images inline, liens, police, mise en forme). Le plus complexe des modules. | done |
| 16 | `builder-page-integration` | Migration du plugin `page` existant pour utiliser le builder à la place du formulaire titre/contenu actuel | done |

**10 à 16 forment un seul et même plugin** (ex. `plugin/builder/`), pas un plugin par module.

## Gestionnaires de contenu (plugins)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 17 | `plugin-realisations-admin` | Plugin Réalisations — CRUD admin (portfolio de projets) | done |
| 18 | `plugin-realisations-public` | Rendu public du plugin Réalisations | done |
| 19 | `plugin-actualites-admin` | Plugin Actualités — CRUD admin (articles de blog/news) | done |
| 20 | `plugin-actualites-public` | Rendu public du plugin Actualités | done |
| 21 | `plugin-accueil` | Page d'accueil — configuration du contenu **et rendu public**, via le builder si actif sinon l'éditeur générique | done |

**17-18** = un seul plugin Réalisations. **19-20** = un seul plugin Actualités.

## Newsletter (plugin, avec une brique générale)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 22 | `mail-sending-service` | Brique générale d'envoi d'email (SMTP, etc.), réutilisable ailleurs — `cms/src/*` | done |
| 23 | `newsletter-plugin-admin` | Gestion des campagnes newsletter côté admin (abonnés, création de campagne, déclenchement) | done |
| 24 | `newsletter-bulk-send-ui` | Envoi en masse piloté par le navigateur : un fetch par envoi, barre de progression (%, compteur envoyés/total) | done |
| 25 | `newsletter-public-signup` | Inscription à la newsletter côté visiteur (site public) | done |

**23 à 25 forment un seul et même plugin** Newsletter (22 reste une brique générale séparée dans `cms/src/*`).

## Utilisateurs (pas un plugin — `cms/*`)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 26 | `user-registration-auth` | Inscription publique + vérification d'adresse email (au-dessus de l'auth existante) | done |
| 27 | `user-public-profile` | Profil membre + édition du compte, côté public | done |
| 28 | `user-admin-management` | Page d'administration complète des utilisateurs (liste, édition, rôles) | done |

## Configuration du site (pas un plugin — `cms/*`)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 29 | `site-config-smtp` | Paramètres SMTP + formulaire de test d'envoi (s'appuie sur `mail-sending-service`) | done |
| 30 | `site-config-general` | Nom du site, logo, favicon | done |
| 31 | `site-config-cache-clear` | Bouton pour vider les caches du site | done |

## Menus (pas un plugin — `cms/*`)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 32 | `menus-admin` | Gestion admin des menus + liaison à un **hook** déclaré par le template (le template enregistre ses hooks par nom, l'admin les récupère et y accroche un menu) + ordre du menu admin (ajout utilisateur) | done |
| 33 | `menus-public` | À chaque hook du template, affichage du menu qui lui est accroché | done |

## Statistiques (plugin)

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 34 | `stats-tracking-core` | Collecte extensible : navigateur, OS, langue, résolution, pages vues, temps moyen par page, % de scroll, clics boutons/liens — **récupération en AJAX uniquement, script chargé en defer** | done |
| 35 | `stats-geo-bot-detection` | Origine géographique (pays) + détection détaillée des crawlers/bots | done |
| 36 | `stats-admin-dashboard` | Visualisation admin des métriques collectées | done |

## Formulaires enrichis — Page / Réalisations / Actualités (pas un nouveau plugin — modifie `page`/`portfolio`/`news`)

Ajoutées sur demande explicite de l'utilisateur, hors backlog initial de `../INIT_ETAPES.md` — découpage basé sur `../FORM_ADD_OPTION.md` (une section de ce document = une étape : le tronc commun, puis chaque spécificité par type de contenu).

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 37 | `content-form-seo-fields` | Champs SEO & réseaux sociaux (titre SEO, meta description, image OG, type OG, URL canonique) + image à la une + formulaire réorganisé en 2 colonnes (gauche : identité/contenu/SEO ; droite : publication/image à la une) sur les formulaires Page, Réalisations et Actualités | done |
| 38 | `portfolio-tags` | Tags pour les réalisations (sélection multiple + création à la volée) | done |
| 39 | `news-categories` | Catégorie pour les actualités (liste + création à la volée) | done |

## Nouveaux blocks du page builder (plugin `page_builder`)

Ajoutées sur demande explicite de l'utilisateur, hors backlog initial de `../INIT_ETAPES.md` — découpage basé sur `../NEW_BLOCK_PAGE_BUILDER.md`, un block = une étape, même principe que 11-15 (pas de nouvelle étape "core" : 10 suffit déjà).

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 40 | `builder-module-hero` | Héro : eyebrow, titre principal (h1), texte d'accroche, bouton principal + bouton secondaire (texte/URL) | done |
| 41 | `builder-module-trusted-by` | "Ils nous font confiance" : texte d'intro + liste de clients/logos ajoutée dynamiquement | done |
| 42 | `builder-module-services-grid` | Grille des services : eyebrow, titre, texte d'intro, liste de services (icône + titre + description) ajoutée dynamiquement | done |
| 43 | `builder-module-process-steps` | "Notre approche" : eyebrow, titre, 4 étapes fixes (numéro, titre, description) | done |
| 44 | `builder-module-portfolio-feed` | Réalisations (dynamique) : eyebrow, titre de section, nombre à afficher, texte du lien "Tout voir", fond de section (blanc/gris doux) | done |
| 45 | `builder-module-news-feed` | Actualités (dynamique) : mêmes champs que 44 pour les actualités | done |
| 46 | `builder-module-heading` | Titre : sélection du niveau H1 à H6 + texte | done |
| 47 | `builder-module-button` | Bouton : texte, URL, couleur (liste Bootstrap), taille, cible (même onglet/nouvel onglet) | done |
| 48 | `builder-module-service-card` | Service (carte seule) : couleur, icône, titre, description — même forme qu'une carte de 42, isolée | done |
| 49 | `builder-module-pricing` | Tarifs : 3 cards (titre, tarif, icône, description, options incluses ajoutées/retirées dynamiquement) | done |
| 50 | `builder-module-news-list` | Liste d'actualités (sans titre/description) : nombre, ordre (récent/ancien), filtre catégorie optionnel | done |
| 51 | `builder-module-portfolio-list` | Liste de réalisations (sans titre/description) : nombre, ordre, filtre tags optionnel | done |

## Restructuration : Page et i18n rejoignent le cœur (`cms/*`)

Ajoutées sur demande explicite de l'utilisateur, hors backlog initial de `../INIT_ETAPES.md` — découpage basé sur `../REFORGE_PLUGINS_AND_I18N.md`.

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 52 | `page-i18n-to-core` | `plugin/page` et `plugin/i18n` cessent d'être des plugins et rejoignent `cms/*` (namespace `App\`, plus de manifeste, plus de remote Module Federation) | done |

## Refonte du multilinguisme (i18n)

Ajoutées sur demande explicite de l'utilisateur, hors backlog initial de `../INIT_ETAPES.md` — découpage basé sur `../REFORGE_PLUGINS_AND_I18N.md`, section "i18n" (une puce ≈ une étape, regroupée quand deux puces décrivent la même fonctionnalité vue de deux côtés). **Distinction importante à garder en tête pour tout le découpage ci-dessous** : la "langue de l'interface" (les libellés/boutons/menus de l'admin et du thème public, traduits une fois pour toutes en FR/EN — étapes 54/56/57) est un sujet complètement différent de la "langue du contenu" (le titre/texte d'une Page ou d'une Actualité, saisi en plusieurs langues par l'admin — étape 58) : WordPress distingue de la même façon la langue de son interface (réglage utilisateur) et le multilingue de contenu (plugin dédié, ex. WPML/Polylang).

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 53 | `i18n-po-symfony-standard` | Remplacer le format PO maison (`TranslationPoConverter`, étape 08) par le standard Symfony (répertoires/`translations/`, nommage `domaine.locale.format`, `TranslatorInterface`) | done |
| 54 | `i18n-admin-ui-translation` | Traduire l'interface admin elle-même (FR + EN), bouton de changement de langue dans l'administration | done |
| 55 | `i18n-site-config-languages` | Section "Langues" dans Configuration (admin) : langues disponibles + langues activées côté site public | on-hold |
| 56 | `i18n-public-ui-translation` | Traduire l'interface du site public (FR + EN), menu public de changement de langue (masqué si une seule langue active) | on-hold |
| 57 | `i18n-per-plugin-language-files` | Fichiers de langue (FR + EN) pour chacun des plugins restants (Réalisations, Actualités, Accueil, Newsletter, Page builder, Statistiques) | on-hold |
| 58 | `i18n-content-language-picker` | Sélecteur de langue dans les formulaires d'ajout de contenu, **sans dépendance croisée** entre i18n et les plugins de contenu | on-hold |

## Restructuration globale : point d'entrée et fichiers d'environnement

Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../INIT_ETAPES.md` — découpage basé sur `../REFORGE_PLUGINS_AND_I18N.md`, section "Global".

| ID | Slug | Étape | Statut |
|----|------|-------|--------|
| 59 | `bootstrap-public-dir-restructure` | `index.php` déplacé dans `public/` (+ reconfiguration Apache), fichiers `.env*` déplacés à la racine du dépôt | on-hold |

## Notes ouvertes pour la mise en place réelle

- **Aucun plugin ne doit être bloquant** : tout plugin de contenu (page, réalisations, actualités, accueil) doit fonctionner avec l'éditeur générique de secours (09) si le plugin builder (10-16) n'est pas installé/activé, et basculer automatiquement sur le builder s'il l'est. Cette détection passe par le gestionnaire de plugins (06)/`PluginRegistry`. Plus généralement, aucune fonctionnalité ne doit dépendre en dur d'un plugin optionnel.
- **Entity `File` unifiée** : tous les fichiers uploadés (images, PDF, ZIP) sont gérés par une entity unique définie à l'étape 01 (champs `name`, `description`, `uniqid`, `slug`, `size`, `width`, `height`, `source`, `file`, `thumbnail[]`, `type`, `created_at`, `modified_at`) avec une arborescence de stockage stricte (`upload/img/{source,webp,thumbnail}`, `upload/pdf`, `upload/zip`) — voir détail dans `steps/01_file-storage-backend_on-hold.md`.
- **Média picker (03)** : toute insertion d'image/fichier dans un contenu (builder, page, actualités, réalisations) passe par ce modal commun, jamais par un champ d'upload ad hoc par plugin. Le modal est paramétrable par l'appelant (types de fichiers affichés/acceptés), ce qui permet par exemple au module builder « Télécharger un fichier » (13) de ne proposer que des PDF/ZIP.
- **Nettoyage et ré-optimisation des images (04/05)** : deux boutons/actions bien distincts. 04 (suppression des inutilisées) dépend fonctionnellement de la présence de la plupart des consommateurs de fichiers pour une détection fiable — son ID bas reflète le regroupement par fonctionnalité, pas l'ordre d'exécution réel recommandé. 05 (ré-optimisation) n'a pas cette contrainte et peut être livrée dès 01 terminée.
- **Hooks de menus (32/33)** : le mécanisme exact de déclaration des hooks côté template (ex. fichier de manifeste du thème, fonction Twig dédiée, etc.) reste à concevoir au démarrage de l'étape 32.
- **Statistiques en AJAX différé (34-36)** : aucune donnée de tracking n'est récupérée au chargement synchrone de la page publique ; le script de collecte est chargé en `defer` et communique uniquement via des appels AJAX asynchrones, pour ne pas impacter les performances de chargement.
- **Multilangue transverse** : les étapes 07/08 ont posé l'infrastructure de base (entité `Lang`, gestion des traductions au format PO maison) ; la refonte complète (interface admin/public traduite, fichiers de langue par plugin, sélecteur de langue de contenu découplé) est désormais découpée en étapes dédiées, 53 à 58 ci-dessus, plutôt que traitée au cas par cas comme envisagé initialement.
- **Découpage admin / public** : suivi ici pour Réalisations, Actualités et Menus (rendu front public distinct) ; Accueil (21) regroupe admin et public dans la même étape. À réévaluer au démarrage de chaque fonctionnalité si un découpage différent s'avère plus pertinent (cf. section 4 de `INIT_ETAPES.md`).
- **Ordre global proposé** ne tient pas compte de priorités métier (ex. utilisateurs/newsletter pourraient être priorisés différemment) — à confirmer avec l'utilisateur avant de démarrer.
- **Rendu dynamique dans le builder (44, 45, 50, 51)** : le builder actuel (étape 10) produit du HTML figé une fois pour toutes à l'enregistrement (`Page.content`/`builderData`, voir `renderToHtml()` dans `CLAUDE.md`) — incompatible tel quel avec un block qui doit refléter les *dernières* réalisations/actualités à chaque affichage de la page publique, pas seulement au moment où l'admin a sauvegardé. Un mécanisme de rendu dynamique côté site public (ex. le HTML statique contient un marqueur/placeholder que le rendu public interprète et hydrate à l'affichage, plutôt que le contenu final) reste à concevoir **au démarrage de l'étape 44**, la première des quatre à en avoir besoin — 45/50/51 le réutilisent ensuite.
- **Image à la une de Page (37)** : Page n'a aujourd'hui aucun champ de ce type, contrairement à Réalisations/Actualités qui ont déjà `coverImageUrl`/`coverImageAlt` (étapes 17/19) — n'ajouter le nouveau champ qu'à Page, réutiliser l'existant tel quel pour les deux autres plutôt que d'en dupliquer un second.
- **Tag vs Catégorie (38/39)** : `../FORM_ADD_OPTION.md` emploie "à sélectionner" (pluriel) pour les tags Réalisations et "liste" (singulier) pour la catégorie Actualités — parti pris de lecture : Tag = relation many-to-many, Category = relation many-to-one. À confirmer avec l'utilisateur au démarrage de 38/39 si ce n'est pas ce qui était voulu.
- **Carte de service partagée (42/48)** : "Grille des services" et "Service (carte seule)" utilisent la même forme de carte (icône + titre + description) — évaluer un sous-composant partagé entre les deux modules plutôt que dupliqué, à trancher au démarrage de 48 (après 42).
- **Doutes explicitement signalés par l'utilisateur dans `../REFORGE_PLUGINS_AND_I18N.md`** ("Si tu as le moindre doute pose moi des questions") — points à clarifier au démarrage de l'étape concernée plutôt que tranchés unilatéralement ici :
    - **52** : "déplacer dans CMS" est lu comme une fusion complète dans le namespace `App\` (plus de `Plugin\Page`/`Plugin\I18n` séparé) — à confirmer, une alternative serait de garder ces deux namespaces mais physiquement sous `cms/src/` sans manifeste ni remote.
    - **55** : la page dédiée de gestion des langues (`LangManager.jsx`, étape 07) est-elle remplacée par la nouvelle section "Langues" de Configuration, ou les deux coexistent-elles (l'une pour ajouter/retirer une langue disponible, l'autre pour piloter son activation publique) ?
    - **57** : "chaque plugin" ne peut plus concerner `page`/`i18n` une fois 52 fait (ils rejoignent le cœur, couverts par 54/56 à la place) — s'applique donc aux 6 plugins restants (Réalisations, Actualités, Accueil, Newsletter, Page builder, Statistiques).
    - **58** : c'est le point le plus explicitement ouvert du document source — comment un formulaire de contenu (Page, Réalisations, Actualités, Accueil) peut proposer un sélecteur de langue sans que son plugin importe i18n, ni que i18n connaisse ces plugins. Piste à valider avec l'utilisateur au démarrage : un composant partagé exposé par l'admin host (même mécanisme que `MediaPicker`/`RichTextEditor`, étape 06) associé à un stockage générique côté i18n indexé par `(type d'entité, id, champ, locale)` plutôt que des tables spécifiques par plugin.
    - **59** : le document parle de `.env`, `.env.dev`, `.env.test` — la convention Symfony réelle est `.env`/`.env.local`/`.env.test`/`.env.test.local` ; à confirmer si "`.env.dev`" désigne bien `.env.local`, ou un fichier `.env.dev` explicite en plus des conventions Symfony standard.

# Feuille de route du projet

> Reformulation de la version originale (voir historique git si besoin de comparer). Objectif : que ce document soit directement exploitable par moi (Claude) dans une future session, sans ambiguïté sur le processus ni sur le périmètre de chaque fonctionnalité.
>
> **Ces instructions ne sont pas encore actives.** Ne pas créer l'arborescence `cms/docs/step/`, ne pas commencer une étape, tant que l'utilisateur ne l'a pas explicitement demandé.

## 1. Principe général

Le développement des fonctionnalités listées en section 4 se fait **étape par étape**, chaque étape étant un lot de travail livrable, testé, documenté et validé par l'utilisateur avant de passer à la suivante. Le suivi de ces étapes est fait via des fichiers markdown (pas via la mémoire de conversation), pour rester consultable/reprenable d'une session à l'autre.

## 2. Arborescence de suivi

À créer au moment où le travail par étapes démarre réellement (pas maintenant) :

```
docs/step/
  MAIN.md                                   # vue d'ensemble : liste de toutes les étapes + statut
  steps/
    {id}_{slug}_{status}.md                 # un fichier par étape
                                             # status ∈ {on-hold, in-progress, done}
  action/
    {order}_{slug}.md                       # un fichier par action du protocole (section 3),
                                             # réutilisé identiquement pour chaque étape
```

Note : `action/{order}_{slug}.md` documente le *protocole* (les 8 actions de la section 3, une fois chacune), pas une instance par étape. Si en pratique il s'avère plus utile de suivre l'avancement de chaque action *pour chaque étape individuellement*, adapter en `steps/{id}/action/{order}_{slug}.md` — à trancher au moment de la mise en place plutôt que maintenant.

## 3. Protocole à suivre pour chaque étape

Dans l'ordre :

1. **Lire `./RELEASE.md`** pour prendre connaissance de l'historique et du contexte déjà accumulé.
2. **Réaliser le travail demandé** par l'étape.
3. **Commenter le code en anglais** — deux niveaux de commentaires attendus sur chaque fichier/classe modifié :
   - Un commentaire principal décrivant le rôle du fichier/de la classe.
   - Un commentaire par fonction/méthode, avec `@param`, `@return`, et une description de l'action effectuée.
4. **Tester avec PHPUnit** — aucune dépréciation tolérée (cohérent avec `failOnDeprecation` déjà activé dans `cms/phpunit.dist.xml`).
5. **Monter la version du projet** dans `cms/composer.json` (`version`) et `cms/.env` (`VERSION`).
6. **Journaliser dans `./RELEASE.md`** (avec le numéro de version) : erreurs rencontrées, changements de structure, décisions prises pendant l'étape.
7. **Commit puis push.**
8. **Faire un rapport de l'étape à l'utilisateur, puis demander l'autorisation explicite avant de démarrer l'étape suivante.** Ne jamais enchaîner deux étapes sans validation.

## 4. Principes d'architecture

- **Réutiliser l'administration existante** (`cms/assets/adm`, dashboard, nav, auth) — ne pas en recréer une autre.
- **Découpage par plugin** : chaque fonctionnalité listée en section 5 est un plugin indépendant (voir `plugin/page/` comme référence — c'est un exemple volontairement non trivial : entité/API dans `plugin/page/src/`, UI admin chargée dynamiquement en Module Federation ; **garder cette même logique** pour les nouveaux plugins), **sauf** les fonctionnalités explicitement marquées « pas un plugin » en section 5, qui vont dans `cms/*`.
- **`cms/src/*` reste générique** : n'y placer que du code réellement transverse/réutilisable par plusieurs plugins (ex. service d'envoi d'email générique pour la newsletter). Le code métier propre à une fonctionnalité va dans son plugin.
- **Le site public peut avoir besoin de sa propre étape** pour chaque fonctionnalité qui a un rendu front public (pas seulement une gestion en admin) — à évaluer fonctionnalité par fonctionnalité.
- **Gestion des fichiers centralisée** (pas un plugin — `cms/*`) : une entity/service d'upload générique, utilisable par n'importe quel plugin ayant besoin de fichiers/images (image dans une page du builder, actualité, réalisation, etc.) — chaque plugin ne doit pas réinventer son propre stockage.
- **Multilangue partout** : tout plugin, le site public et les templates doivent pouvoir afficher leur contenu dans plusieurs langues (français + anglais pour démarrer). La gestion des langues disponibles et des traductions se fait via un plugin dédié, pas en dur dans chaque fonctionnalité.

## 5. Backlog des fonctionnalités

### Gestion des fichiers (pas un plugin — `cms/*`)
- Entity/service d'upload générique (stockage dans `upload/`), exposé pour être réutilisé par n'importe quel plugin ayant besoin de fichiers/images (éditeur de contenu, actualités, réalisations, etc.).
- Un gestionnaire de fichiers en admin (parcourir/uploader/supprimer les fichiers déjà envoyés).
- Une **image par défaut** affichée quand le fichier référencé n'existe pas/plus, côté public comme admin.

### Multilangue (plugin)
- Tous les plugins, le site public et les templates doivent pouvoir afficher leur contenu dans plusieurs langues — au démarrage : **français et anglais uniquement**.
- Menu dédié en administration pour ajouter des langues et gérer les traductions (contenu et/ou interface, à préciser à l'implémentation).

### Éditeur de contenu (drag & drop, style page builder)
- Modules disponibles au lancement :
  - **Texte** — éditeur WYSIWYG complet (sortie HTML) : images inline, liens, choix de police, mise en forme (gras/italique/titres/etc.) — le plus complexe des modules.
  - **Slider** — carrousel d'images.
  - **Image** — image simple.
  - **Téléchargement** — bloc proposant un fichier à télécharger (ex. PDF). *(à confirmer avec l'utilisateur au moment de l'implémentation si le sens diffère)*
  - **Appel à l'action (CTA)** — bouton/bloc de conversion.
- Doit être conçu pour qu'on puisse **ajouter d'autres modules plus tard** sans réécrire le système.
- **Améliorer le plugin `page` existant** pour qu'il utilise cet éditeur (au lieu du formulaire titre/contenu actuel).

### Gestionnaires de contenu (plugins)
- **Réalisations** — portfolio de projets/réalisations.
- **Actualités** — articles de blog/news.
- **Page d'accueil** — configuration du contenu de la home (probablement construite avec l'éditeur ci-dessus).

### Newsletter (plugin, avec une brique générale)
- Inscription côté visiteur (site public) et gestion côté admin.
- Envoi en masse **un par un via fetch côté client**, avec barre de progression (pourcentage + compteur envoyés/total) — donc piloté depuis le navigateur admin, pas un job serveur unique bloquant.
- Le **script d'envoi d'email lui-même** (SMTP, etc.) est une brique générale (`cms/src/*`, réutilisable ailleurs) ; la **gestion des campagnes newsletter** (liste des abonnés, création de campagne, déclenchement de l'envoi en masse) reste dans le plugin.

### Utilisateurs (pas un plugin — `cms/*`)
- Connexion, inscription, vérification d'adresse email.
- Profil membre + édition du compte (côté public).
- Page d'administration complète (liste, édition, rôles, etc. — au-delà du seul admin existant).

### Configuration du site (pas un plugin — `cms/*`)
- Paramètres SMTP + formulaire de test d'envoi.
- Nom du site, logo, favicon.

### Menus (pas un plugin — `cms/*`)
- Gestion admin : créer/nommer/organiser des menus.
- Le template public liste les menus disponibles (par nom) et les affiche.

### Statistiques (plugin)
Suivi complet des visites du site public. Métriques attendues au minimum (liste non exhaustive — prévoir une structure extensible pour en ajouter d'autres plus tard) :
- Navigateur, OS, langue du visiteur, résolution d'écran
- Pages vues, temps moyen passé par page
- Pourcentage de scroll moyen
- Clics sur boutons/liens
- Origine géographique (pays)
- Détection des crawlers/bots (détaillée)

## 6. Découpage en étapes

Chaque fonctionnalité de la section 5 est découpée en une ou plusieurs étapes (autant que nécessaire), suivant le protocole de la section 3. Le découpage précis se fait au moment de démarrer chaque fonctionnalité, pas à l'avance.

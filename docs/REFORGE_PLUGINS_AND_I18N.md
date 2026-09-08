# Restructurations des plugins :

## Plugin Page et i18n :

Déplacer ces plugins dans CMS, ce ne sont plus des plugins.

## i18n :

- Modifier tous le site et tous les plugin afin d'ajouter le multi langue sur l'administration et site public.
- Avoir les fichiers de langue dans chaque plugin, template et admin.
- Créer les traductions Française et Anglaise.
- Ajouter un bouton de changement de langue dans l'administration.
- Ajouter dans la section (admin) Configuration les langues disponible dans et celle qui sont activé dans la section publique du site
- Ajouter dans la section publique du site un menu de changement de langue, si une seulle langbue est sélectionné alor ce menu n'apparait pas.
- Remplacer les fichier PO dans i18n pour respecter le standard de Symfony.
- Ajouter dans les formulaires du site, le multi language, explication :
    - Ne pas ajouter cette fonctionnalité dans les plugin, mais créer un menu qui permet de sélectionner une langue dans les formulaires d'ajout de contenue.
    - Il est important que i18n n'est pas de dépendance avec un plugin ou qu'un plugin est une dépendance avec i18n.
    - Si tu as le moindre doute pose moi des questions.
    - Pour comprendre l'objectif de ces modifications pense à ce qui est fait sur Wordpress ou Prestashop.

## Global :

- L'entrés du site doit ce faire dans ./public/index.php, déplace ce fichier et reconfigure docker pour que cela fonctionne.
- .env, .env.dev, .env.test (fichier d'environement) doivent se trouver à la racine du projet.
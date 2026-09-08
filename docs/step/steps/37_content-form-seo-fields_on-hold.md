# Étape 37 — Formulaire enrichi : SEO, réseaux sociaux, image à la une

> Statut : **on-hold**. Fait partie du découpage documenté dans `../MAIN.md`. Ajoutée sur demande explicite de l'utilisateur, hors backlog initial de `../../INIT_ETAPES.md` — spécification complète dans `../../FORM_ADD_OPTION.md`.
> Ne pas démarrer sans demande explicite de l'utilisateur.

**Fonctionnalité** : Formulaires enrichis — Page / Réalisations / Actualités
**Slug** : `content-form-seo-fields`

## Objectif

Appliquer aux formulaires Page, Réalisations et Actualités (voir `../../FORM_ADD_OPTION.md`, section "Pour tous les formulaires") :
- Slug auto-généré à partir du titre — déjà en place sur les trois (`refreshSlug()`), rien à faire.
- Un groupe de champs S.E.O. & réseaux sociaux : titre S.E.O., meta description, image OG (via le média picker), type OG (liste : website/article/product/profile), URL canonique.
- Statut de publication (liste : brouillon/publié/archivé) — déjà en place sur les trois, rien à faire.
- Une image à la une.
- Réorganisation du formulaire en 2 colonnes :
  - Gauche : card Identité (titre, slug), card Contenu (page builder), card S.E.O. & réseaux sociaux.
  - Droite : Publication, Image à la une.

## Portée / dépendances

Modifie les trois plugins existants `plugin/page`, `plugin/portfolio`, `plugin/news` (aucun nouveau plugin créé). S'appuie sur le média picker (03) pour l'image OG et l'image à la une. **Page n'a aujourd'hui aucun champ d'image à la une** — à ajouter uniquement pour Page ; Réalisations/Actualités ont déjà `coverImageUrl`/`coverImageAlt` (étapes 17/19) à réutiliser tel quel, sans champ dupliqué (voir "Notes ouvertes" de `../MAIN.md`). Bloquant pour 38 et 39, qui s'appuient sur la même réorganisation de formulaire (section de droite).

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

# Thème WordPress NOVA IMMOBILIER

Thème sur mesure qui permet au gérant de l’agence de **tout gérer depuis l’administration WordPress**, sans toucher au code :
ajouter, modifier ou supprimer un bien, fixer les prix, les surfaces, le nombre de pièces, les photos, le statut (vendu, loué…),
la mise en avant sur l’accueil, ainsi que les coordonnées de l’agence et les textes de la page d’accueil.

Aucune extension n’est nécessaire (pas d’ACF) : tout est intégré au thème.

---

## 1. Guide du gérant

### Ajouter un bien
1. Menu **Biens immobiliers › Ajouter un bien**.
2. **Titre** de l’annonce, puis la **description** complète dans l’éditeur.
3. **Photos du bien** : bouton *Ajouter des photos* (plusieurs à la fois). Glissez-les pour changer l’ordre : la première est la photo principale. Sans photo, une illustration est affichée.
4. **Détails du bien** :
   - *Prix et statut* : transaction (Vente / Location), type, typologie (F3, Villa R+1…), **prix en DA** (loyer mensuel pour une location ; l’équivalent en centimes s’affiche sous le champ), disponibilité, case **Bien à la une**.
   - *Localisation* : wilaya (liste des 58 wilayas), commune, quartier.
   - *Caractéristiques* : surface, terrain, **pièces, chambres, salles de bain**, étage, année.
   - *Équipements* : cases à cocher et champ libre (un par ligne).
5. **Résumé** : 1–2 phrases affichées sur les cartes.
6. Cliquez sur **Publier**. La référence (NOVA-xxxx) est créée automatiquement si vous la laissez vide.

### Modifier un prix, des pièces ou n’importe quel détail
**Biens immobiliers › Tous les biens**, cliquez sur le bien, modifiez, puis **Mettre à jour**. Le site est à jour immédiatement.

### Bien vendu ou loué
Changez **Disponibilité** en *Vendu* ou *Loué* : un badge s’affiche sur le site. Pour le retirer complètement des listes,
cochez *Masquer les biens vendus ou loués* dans **Réglages agence**, ou supprimez le bien.

### Supprimer un bien
Dans la liste, survolez le bien et cliquez sur **Mettre à la corbeille**. Il disparaît du site (restaurable pendant 30 jours).

### Retrouver un bien
La liste se trie par prix et se filtre par transaction, type et statut. La recherche fonctionne aussi par référence et par commune.

### Demandes des clients
Chaque message envoyé depuis le site (page Contact ou fiche d’un bien) apparaît dans **Demandes clients**, avec le bien concerné,
le téléphone et un lien *Répondre sur WhatsApp*. Une copie est envoyée par email à l’adresse des réglages.

### Coordonnées et page d’accueil
**Réglages agence** : téléphone, numéro WhatsApp, email, adresse, horaires, réseaux sociaux, titre et texte d’accueil,
images de l’accueil et de la section « L’agence », chiffres clés.

### Menu
**Apparence › Menus** : modifiez le *Menu principal* (Acheter / Louer pointent vers `/biens/?transaction=vente|location`).

---

## 2. Installer sur un hébergement

1. Hébergement WordPress (PHP 8.0+, MySQL/MariaDB, HTTPS). Installez WordPress en **français**.
2. **Apparence › Thèmes › Ajouter › Téléverser** : envoyez `nova-immobilier.zip`, puis **Activer**.
3. **Réglages › Permaliens** : choisissez *Titre de la publication*, puis Enregistrer.
4. Créez les pages (slugs exacts) : `accueil`, `a-propos`, `contact`, `favoris`, `politique-de-confidentialite`.
   Dans **Réglages › Lecture**, choisissez une page statique et définissez *Accueil* comme page d’accueil.
   Avec WP-CLI, la commande `wp nova seed` fait tout cela et importe les 12 biens de démonstration.
5. Remplissez **Réglages agence**, puis ajoutez les vrais biens et photos.
6. Emails : installez une extension SMTP (WP Mail SMTP, par exemple) pour fiabiliser l’envoi des notifications.
7. Recommandé : une extension de sauvegarde, une extension de cache et une extension SEO (Yoast ou Rank Math).

---

## 3. Développement local (Docker)

```bash
cd wordpress
docker compose up -d                                   # http://localhost:8080
docker compose run --rm cli wp core install --url=http://localhost:8080 --title="NOVA IMMOBILIER" \
  --admin_user=admin --admin_password=changez-moi --admin_email=vous@exemple.com --skip-email
docker compose run --rm cli wp theme activate nova-immobilier
docker compose run --rm cli wp rewrite structure '/%postname%/' --hard
docker compose run --rm cli wp nova seed               # biens + pages + menu de démonstration
```

Le dossier `nova-immobilier/` est monté dans le conteneur : les modifications PHP sont visibles immédiatement.

### Assets partagés avec la démo React
Les styles (`src/styles`), le générateur d’illustrations et les données de démonstration viennent du projet React.
Après une modification, depuis la racine du dépôt :

```bash
npm run theme:build   # → assets/js/theme.js, assets/css/*.css, inc/seed-data.json
npm run theme:zip     # → wordpress/nova-immobilier.zip (à téléverser dans WordPress)
```

## 4. Structure du thème

```
nova-immobilier/
├── functions.php            chargement des modules, assets, tailles d’images
├── inc/
│   ├── fields.php           liste unique de tous les champs d’un bien (58 wilayas, statuts, équipements)
│   ├── post-types.php       types « Biens » (/biens/) et « Demandes »
│   ├── meta-boxes.php       formulaire d’édition (détails + photos), enregistrement et contrôles
│   ├── admin-columns.php    colonnes, tri par prix, filtres, recherche par référence, widget du tableau de bord
│   ├── settings.php         page « Réglages agence »
│   ├── query.php            filtres et tri de /biens/, biens à la une, biens similaires
│   ├── leads.php            formulaire de contact : validation, anti-spam, enregistrement, email
│   ├── seo.php              titres, meta description, Open Graph, données structurées
│   ├── helpers.php          format des prix (DA, centimes), WhatsApp, icônes, images
│   └── seed.php             commande `wp nova seed`
├── template-parts/          carte de bien, recherche, filtres, galerie, formulaire, en-têtes
├── front-page.php, archive-property.php, single-property.php,
├── page-contact.php, page-a-propos.php, page-favoris.php, page.php, 404.php, index.php
└── assets/                  css/, js/theme.js (site), js/admin.js (administration)
```

Les champs sont aussi exposés dans l’API REST (`/wp-json/wp/v2/biens`) pour une utilisation « headless » éventuelle.

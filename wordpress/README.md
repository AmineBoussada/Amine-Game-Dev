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

### Version arabe et mode sombre
- Les visiteurs choisissent **ع / FR** (langue) et la **lune / le soleil** (clair ou sombre) en haut du site ; leur choix est mémorisé.
- Chaque bien a un cadre **Version arabe du site** : titre, résumé et description en arabe. Facultatif : si un champ est vide,
  le texte français s’affiche.
- **Réglages agence › Textes de la version arabe** : titre et texte d’accueil, présentation, adresse, chiffres clés en arabe.
- Les menus, boutons, filtres et formulaires sont traduits automatiquement. Les demandes des clients arrivent toujours en français
  dans **Demandes** (seul le message est écrit par le client).

---

## 2. Installer sur un hébergement

1. Hébergement WordPress (PHP 8.0+, MySQL/MariaDB, HTTPS). Installez WordPress en **français**.
2. **Installer le thème depuis le fichier zip**, en trois clics :
   1. Allez dans **Apparence › Thèmes**, puis cliquez sur le bouton **Téléverser un thème** en haut de la page.
      *Le formulaire n’apparaît qu’après ce clic.*
   2. Cliquez sur **Choisir un fichier**, sélectionnez `nova-immobilier.zip`, puis cliquez sur **Installer maintenant**.
      *Le bouton reste grisé tant qu’aucun fichier n’est choisi.*
   3. Une fois l’installation terminée, cliquez sur **Activer**.

   **Méthode de secours (FTP ou gestionnaire de fichiers de l’hébergeur)** : décompressez le zip, puis envoyez le dossier
   `nova-immobilier/` dans `wp-content/themes/`. Le thème apparaît ensuite dans **Apparence › Thèmes** et il suffit de l’activer.

   Si l’installation échoue, vérifiez que la version de PHP de l’hébergement est 8.0 ou supérieure (menu de l’hébergeur),
   et que la taille maximale de téléversement est d’au moins 10 Mo (`upload_max_filesize`).
3. **Réglages › Permaliens** : choisissez *Titre de la publication*, puis Enregistrer.
4. Créez les pages (slugs exacts) : `accueil`, `a-propos`, `contact`, `favoris`, `politique-de-confidentialite`.
   Dans **Réglages › Lecture**, choisissez une page statique et définissez *Accueil* comme page d’accueil.
   Avec WP-CLI, la commande `wp nova seed` fait tout cela et importe les 12 biens de démonstration.
5. Remplissez **Réglages agence**, puis ajoutez les vrais biens et photos.
6. Emails : installez une extension SMTP (WP Mail SMTP, par exemple) pour fiabiliser l’envoi des notifications.
7. Recommandé : une extension de sauvegarde, une extension de cache et une extension SEO (Yoast ou Rank Math).

---

## 3. Nouveau client : générer un thème à son nom

Le skill Claude Code **`real-estate-wp-theme`** (`.claude/skills/real-estate-wp-theme/`) crée un thème complet pour une autre agence
à partir de ce thème : nom, logo, couleurs, polices, ville, coordonnées, préfixe de code et préfixe des références (ex. ATL-1024).
Dans Claude Code, demandez simplement : « crée le thème pour l’agence X à Oran ». Le skill pose les questions manquantes,
génère `wordpress/<slug>/` et `wordpress/<slug>.zip`, vérifie le résultat et peut le tester dans Docker.

Sans Claude, depuis la racine du dépôt :

```bash
cp .claude/skills/real-estate-wp-theme/config.example.json wordpress/mon-agence.config.json   # puis éditez-le
node .claude/skills/real-estate-wp-theme/scripts/generate-theme.mjs wordpress/mon-agence.config.json
.claude/skills/real-estate-wp-theme/scripts/check-theme.sh wordpress/<slug>
```

Chaque site a le choix français / arabe et clair / sombre. Deux designs : `"layout": "nova"` (celui-ci ; exemple :
Bahia Immobilier en bleu marine, `wordpress/bahia-immobilier/`) ou `"layout": "riviera"` (exemple : `config.riviera.example.json`).

Les thèmes clients et leurs fichiers de configuration ne sont pas versionnés (`.gitignore`). Exception : `bahia-immobilier/`, agence fictive de démonstration.

## 4. Développement local (Docker)

```bash
cd wordpress
docker compose up -d                                   # http://localhost:8080
# autre thème, base séparée : THEME=<slug> WP_PORT=8081 docker compose -p <prefixe> up -d
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

## 5. Structure du thème

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

# Migration vers WordPress

Objectif : permettre au gérant de l’agence de gérer biens, photos, prix, statuts, coordonnées et contenu de l’accueil
depuis l’administration WordPress, sans toucher au code.

Le front-end a été conçu pour cela :

- **Une seule source de données** : `src/data/properties.ts` et `src/data/site.ts`. Aucun composant ne contient de bien en dur.
- **Un modèle typé** : `src/types/property.ts` décrit exactement les champs à créer dans WordPress.
- **Des composants autonomes** (`PropertyCard`, `Gallery`, `FilterPanel`, `ContactForm`, …) qui reçoivent leurs données en props.
- **Un CSS sans framework**, basé sur des variables, réutilisable tel quel dans un thème.

---

## 1. Modèle de données WordPress

### Custom Post Type `property` (« Biens »)

| Champ front (`Property`) | WordPress |
|---|---|
| `title` | Titre de l’article |
| `slug` | Slug (permalien `/biens/{slug}`) |
| `description[]` | Contenu de l’éditeur |
| `excerpt` | Extrait |
| `images[]` | Image mise en avant + champ ACF **Galerie** (`gallery`) |
| `createdAt` | Date de publication |
| `id` | ID de l’article |

### Taxonomies

| Taxonomie | Valeurs |
|---|---|
| `transaction` | Vente, Location |
| `property_type` | Appartement, Villa, Terrain, Local commercial |
| `wilaya` (hiérarchique) | Alger › Hydra, Kouba… ; Oran › … (wilaya parent / commune enfant) |

### Champs personnalisés (ACF, groupe « Détails du bien »)

| Clé ACF | Type | Notes |
|---|---|---|
| `reference` | Texte | ex. NOVA-1024 |
| `price` | Nombre | en DZD ; loyer mensuel si location |
| `typology` | Texte | F2, F3, Villa R+1… |
| `quartier` | Texte | facultatif |
| `surface` | Nombre | m² |
| `land_surface` | Nombre | m², villas |
| `rooms` / `bedrooms` / `bathrooms` | Nombre | |
| `floor` | Texte | facultatif |
| `year_built` | Nombre | facultatif |
| `features` | Case à cocher ou répéteur | équipements |
| `featured` | Vrai/Faux | « Bien à la une » sur l’accueil |
| `status` | Sélection | disponible / sous-compromis / vendu / loué |
| `gallery` | Galerie | photos du bien |

### Options du site (ACF Options page « Agence »)

Correspond à `src/data/site.ts` : téléphone, mobile WhatsApp, email, adresse, horaires, réseaux sociaux,
textes du hero (titre, sous-titre, image), bloc « À propos », chiffres clés, valeurs.

---

## 2. Deux stratégies possibles

### A. Thème WordPress classique (recommandé pour une agence)

Hébergement mutualisé simple, SEO natif, aucun serveur Node.

1. Créer un thème `nova-immobilier/` et copier `src/styles/*.css` dans `assets/css/`.
2. Déclarer le CPT, les taxonomies et les champs ACF (plugin ou `functions.php`).
3. Convertir chaque composant en template PHP :
   - `Header.tsx` → `header.php` (+ `wp_nav_menu`)
   - `Footer.tsx` → `footer.php`
   - `pages/Home.tsx` → `front-page.php`
   - `pages/Listings.tsx` → `archive-property.php` (filtres via `WP_Query` + `tax_query` / `meta_query`
     sur les mêmes paramètres d’URL : `transaction`, `type`, `wilaya`, `min`, `max`, `surface`, `pieces`, `tri`)
   - `pages/PropertyDetail.tsx` → `single-property.php`
   - `PropertyCard.tsx` → `template-parts/property-card.php`
   - `pages/Contact.tsx` → `page-contact.php`
4. Garder les interactions en JavaScript léger : galerie/lightbox, favoris (localStorage), menu mobile, tiroir de filtres.
5. Formulaires : Contact Form 7, WPForms ou Fluent Forms, avec le même balisage et les mêmes classes CSS.

### B. WordPress « headless » + ce front React

WordPress sert uniquement d’interface d’administration et d’API ; ce projet React reste le site public.

1. Activer `show_in_rest` sur le CPT et les taxonomies, et exposer les champs ACF dans l’API REST.
2. Remplacer les imports de `src/data/properties.ts` par une couche d’accès aux données, par exemple :

   ```ts
   // src/data/api.ts
   const API = import.meta.env.VITE_WP_API; // https://admin.nova-immobilier.dz/wp-json
   export async function fetchProperties(): Promise<Property[]> {
     const res = await fetch(`${API}/wp/v2/property?per_page=100&_embed`);
     return (await res.json()).map(mapWpToProperty); // conversion des champs ACF → Property
   }
   ```

3. Formulaires : envoyer le même contenu à l’endpoint REST du plugin de formulaire.
4. SEO : prévoir un pré-rendu (prerender ou SSR) pour l’indexation des fiches biens.

---

## 3. Mise en ligne

1. **Domaine** : par exemple `nova-immobilier.dz` (via un registrar agréé `.dz`) ou un `.com`.
2. **Hébergement** : offre WordPress avec PHP 8.2+, MySQL/MariaDB, HTTPS (Let’s Encrypt) et sauvegardes automatiques.
3. **Installation** : WordPress, thème NOVA, ACF Pro, plugin de formulaire, plugin SEO (Yoast ou Rank Math), plugin de cache.
4. **Contenu** : importer les biens réels avec leurs photos compressées (WebP, environ 1 600 px de large).
5. **Configuration** : réglages de l’agence, menus, page de confidentialité, Google Search Console, Google Business Profile.
6. **Recette** : tests mobile, formulaires (réception des emails via SMTP), vitesse (PageSpeed), liens WhatsApp.
7. **Formation** : courte prise en main pour le gérant (ajouter un bien, changer un prix, marquer « vendu »).

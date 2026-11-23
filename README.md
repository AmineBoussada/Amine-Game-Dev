# NOVA IMMOBILIER — Site vitrine (démo)

Site de démonstration premium pour une agence immobilière algérienne fictive, **NOVA IMMOBILIER**.
Phase 1 : front-end uniquement (aucun backend), données de démonstration centralisées, prêt à être converti en thème WordPress.

> Tous les biens, prix, références et coordonnées sont **fictifs**.

## Lancer le projet

Prérequis : Node.js 18+ (testé avec Node 22).

```bash
npm install
npm run dev       # serveur de développement → http://localhost:5173
npm run build     # build de production dans dist/
npm run preview   # prévisualiser le build → http://localhost:4173
```

Le site est une SPA : en production, configurez le serveur pour renvoyer `index.html` sur toutes les routes
(Apache : `FallbackResource /index.html` ; Nginx : `try_files $uri /index.html;`).

## Pages

| Route | Page |
|---|---|
| `/` | Accueil : hero, recherche, biens à la une, pourquoi nous, catégories, agence, CTA |
| `/biens` | Liste des biens + filtres (transaction, type, wilaya, prix, surface, pièces) et tri |
| `/biens?transaction=vente` / `location` | Acheter / Louer |
| `/biens/:slug` | Fiche bien : galerie + lightbox, caractéristiques, équipements, localisation, formulaire, WhatsApp, visite |
| `/contact` | Coordonnées, horaires, formulaire validé (envoi simulé) |
| `/a-propos` | Mission, méthode, engagements, équipe |
| `/favoris` | Biens sauvegardés (localStorage) |
| `/politique-de-confidentialite` | Politique de confidentialité |

Les filtres sont stockés dans l’URL : une recherche peut être partagée ou mise en favori.

## Stack

- **Vite 5 + React 18 + TypeScript** (strict)
- **React Router 6** — seule dépendance d’exécution supplémentaire
- **CSS natif** organisé en design tokens (`src/styles/tokens.css`) — pas de framework CSS, pour faciliter le portage vers un thème WordPress
- Polices Google : Manrope + Playfair Display
- Icônes SVG inline (aucune librairie)

## Structure

```
src/
├── data/
│   ├── properties.ts      # ← les 12 biens de démo (source unique)
│   └── site.ts            # ← infos agence, navigation, textes de l’accueil
├── types/property.ts      # modèle Property (= futur CPT + champs ACF)
├── utils/format.ts        # prix DZD, liens WhatsApp, filtres/tri, URL ↔ filtres
├── hooks/                 # favoris, meta SEO, animations d’apparition
├── components/            # Header, Footer, PropertyCard, SearchBar, FilterPanel,
│                          # Gallery, ContactForm, SmartImage, Icon, …
├── pages/                 # Home, Listings, PropertyDetail, Contact, About, …
└── styles/                # tokens.css, base.css, components.css, pages.css
docs/WORDPRESS.md          # plan de migration vers WordPress
```

## Modifier le contenu

- **Biens** : `src/data/properties.ts`
- **Coordonnées, réseaux sociaux, horaires, textes de l’accueil** : `src/data/site.ts`
- **Couleurs, typographie, espacements** : `src/styles/tokens.css`

## Images

Photos Unsplash chargées par URL, en lazy-loading, avec un cadre à ratio fixe.
Si une image ne se charge pas, `SmartImage` affiche un visuel de remplacement sobre aux couleurs de la marque.
Pour la production, remplacez-les par les photos réelles de l’agence (via la médiathèque WordPress).

## Passage à WordPress

Voir **[docs/WORDPRESS.md](docs/WORDPRESS.md)**.

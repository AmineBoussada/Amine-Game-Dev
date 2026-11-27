import type { PropertyType, TransactionType } from '../types/property';
import type { Scene } from '../utils/illustrations';

/**
 * Site-wide content (agency info, navigation, homepage copy).
 * In WordPress this maps to an ACF Options page / Customizer settings.
 * All contact details are DEMO values.
 */
export const agency = {
  name: 'NOVA IMMOBILIER',
  tagline: 'Agence immobilière à Alger',
  description:
    'Agence immobilière basée à Alger, NOVA IMMOBILIER accompagne particuliers et entreprises dans l’achat, la vente et la location de biens en Algérie.',
  phone: '+213 23 00 00 00',
  phoneHref: 'tel:+21323000000',
  mobile: '+213 550 00 00 00',
  whatsapp: '213550000000',
  email: 'contact@nova-immobilier.dz',
  address: '12, chemin des Glycines (adresse de démonstration)',
  city: 'Hydra, Alger',
  hours: [
    { days: 'Dimanche – Jeudi', time: '08h30 – 17h30' },
    { days: 'Samedi', time: '09h00 – 13h00' },
    { days: 'Vendredi', time: 'Fermé' },
  ],
  mapQuery: 'Hydra, Alger, Algérie',
  socials: [
    { name: 'Facebook', href: 'https://www.facebook.com/', icon: 'facebook' },
    { name: 'Instagram', href: 'https://www.instagram.com/', icon: 'instagram' },
    { name: 'LinkedIn', href: 'https://www.linkedin.com/', icon: 'linkedin' },
    { name: 'YouTube', href: 'https://www.youtube.com/', icon: 'youtube' },
  ] as const,
};

export const navigation = [
  { label: 'Accueil', to: '/' },
  { label: 'Acheter', to: '/biens?transaction=vente' },
  { label: 'Louer', to: '/biens?transaction=location' },
  { label: 'Nos biens', to: '/biens' },
  { label: 'À propos', to: '/a-propos' },
  { label: 'Contact', to: '/contact' },
];

export const transactionLabels: Record<TransactionType, string> = {
  vente: 'Vente',
  location: 'Location',
};

export const typeLabels: Record<PropertyType, string> = {
  appartement: 'Appartement',
  villa: 'Villa',
  terrain: 'Terrain',
  local: 'Local commercial',
};

const img = (id: string, w = 1200) =>
  `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&w=${w}&q=80`;

export const hero = {
  title: 'Le bien qui vous ressemble, au bon endroit.',
  text: 'Découvrez une sélection de biens immobiliers soigneusement choisis pour concrétiser vos projets en Algérie.',
  image: img('1600596542815-ffad4c1539a9', 2000),
  imageAlt: 'Villa contemporaine aux lignes épurées baignée de lumière naturelle',
  scene: 'villa-pool' as Scene,
};

export const categories: { type: PropertyType; label: string; text: string; image: string; scene: Scene }[] = [
  { type: 'appartement', label: 'Appartements', text: 'Du F2 au F5, en résidence ou en centre-ville', image: img('1502672260266-1c1ef2d93688', 900), scene: 'building' },
  { type: 'villa', label: 'Villas', text: 'Villas familiales et demeures d’exception', image: img('1580587771525-78b9dba3b914', 900), scene: 'villa-pool' },
  { type: 'terrain', label: 'Terrains', text: 'Terrains constructibles avec acte', image: img('1500382017468-9049fed747ef', 900), scene: 'land' },
  { type: 'local', label: 'Locaux commerciaux', text: 'Commerces, bureaux et showrooms', image: img('1441986300917-64674bd600d8', 900), scene: 'shop' },
];

export const values = [
  { icon: 'selection', title: 'Sélection de biens', text: 'Chaque bien est visité, vérifié et documenté avant d’être proposé à nos clients.' },
  { icon: 'support', title: 'Accompagnement personnalisé', text: 'Un conseiller dédié vous suit de la première visite jusqu’à la signature chez le notaire.' },
  { icon: 'map', title: 'Expertise locale', text: 'Une connaissance fine des quartiers d’Alger et des prix réels du marché algérien.' },
  { icon: 'shield', title: 'Transparence et confiance', text: 'Des informations claires, des documents vérifiés et des honoraires annoncés dès le départ.' },
] as const;

export const about = {
  title: 'Une agence à taille humaine, exigeante sur chaque détail.',
  text: 'Chez NOVA IMMOBILIER, nous accompagnons nos clients dans chaque étape de leur projet immobilier. Notre mission est de vous aider à trouver le bien idéal grâce à une approche personnalisée, transparente et professionnelle.',
  image: img('1600607687939-ce8a6c25118c', 1200),
  scene: 'living' as Scene,
  stats: [
    { value: '12', label: 'ans d’expérience' },
    { value: '850+', label: 'clients accompagnés' },
    { value: '24', label: 'communes couvertes' },
  ],
};

export const team = [
  { name: 'Conseiller transaction', role: 'Vente & achat résidentiel', initials: 'CT' },
  { name: 'Conseillère location', role: 'Location & gestion locative', initials: 'CL' },
  { name: 'Responsable entreprises', role: 'Locaux & immobilier d’entreprise', initials: 'RE' },
];

export const budgetOptions = {
  vente: [5_000_000, 10_000_000, 15_000_000, 20_000_000, 30_000_000, 50_000_000, 80_000_000, 120_000_000, 200_000_000],
  location: [30_000, 50_000, 80_000, 100_000, 150_000, 250_000, 400_000],
};

import type { Property, PropertyFilters, SortOption } from '../types/property';
import { agency } from '../data/site';

const nf = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 });

/** 38500000 → "38 500 000 DA" */
export const formatPrice = (value: number) => `${nf.format(value).replace(/ | /g, ' ')} DA`;

/** Short form used in selects: 15000000 → "15 M DA", 80000 → "80 000 DA" */
export const formatPriceShort = (value: number) =>
  value >= 1_000_000 ? `${nf.format(value / 1_000_000)} M DA` : formatPrice(value);

/** Local habit: prices are often spoken in centimes (1 DA = 100 centimes). */
export const formatCentimes = (value: number) => {
  const centimes = value * 100;
  if (centimes >= 1_000_000_000) {
    const v = centimes / 1_000_000_000;
    return `≈ ${v.toLocaleString('fr-FR', { maximumFractionDigits: 2 })} milliard${v >= 2 ? 's' : ''} de centimes`;
  }
  return `≈ ${(centimes / 1_000_000).toLocaleString('fr-FR', { maximumFractionDigits: 0 })} millions de centimes`;
};

export const priceSuffix = (p: Property) => (p.transaction === 'location' ? ' / mois' : '');

export const whatsappLink = (message: string) =>
  `https://wa.me/${agency.whatsapp}?text=${encodeURIComponent(message)}`;

export const propertyWhatsappMessage = (p: Property) =>
  `Bonjour NOVA IMMOBILIER, je suis intéressé(e) par le bien « ${p.title} » (réf. ${p.reference}) à ${p.commune}. Pouvez-vous me recontacter ?`;

export const mapsLink = (query: string) =>
  `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;

export function filterProperties(list: Property[], f: PropertyFilters) {
  return list.filter(
    (p) =>
      (!f.transaction || p.transaction === f.transaction) &&
      (!f.type || p.type === f.type) &&
      (!f.wilaya || p.wilaya === f.wilaya) &&
      (!f.minPrice || p.price >= f.minPrice) &&
      (!f.maxPrice || p.price <= f.maxPrice) &&
      (!f.minSurface || p.surface >= f.minSurface) &&
      (!f.rooms || p.rooms >= f.rooms),
  );
}

export function sortProperties(list: Property[], sort: SortOption) {
  const copy = [...list];
  switch (sort) {
    case 'price-asc':
      return copy.sort((a, b) => a.price - b.price);
    case 'price-desc':
      return copy.sort((a, b) => b.price - a.price);
    case 'surface-desc':
      return copy.sort((a, b) => b.surface - a.surface);
    default:
      return copy.sort((a, b) => b.createdAt.localeCompare(a.createdAt));
  }
}

/** Parse filters from URL search params (keeps listings shareable). */
export function filtersFromParams(params: URLSearchParams): PropertyFilters {
  const num = (k: string) => {
    const v = Number(params.get(k));
    return Number.isFinite(v) && v > 0 ? v : undefined;
  };
  const t = params.get('transaction');
  const ty = params.get('type');
  return {
    transaction: t === 'vente' || t === 'location' ? t : '',
    type: ty === 'appartement' || ty === 'villa' || ty === 'terrain' || ty === 'local' ? ty : '',
    wilaya: params.get('wilaya') ?? '',
    minPrice: num('min'),
    maxPrice: num('max'),
    minSurface: num('surface'),
    rooms: num('pieces'),
  };
}

export function filtersToParams(f: PropertyFilters, sort?: SortOption) {
  const p = new URLSearchParams();
  if (f.transaction) p.set('transaction', f.transaction);
  if (f.type) p.set('type', f.type);
  if (f.wilaya) p.set('wilaya', f.wilaya);
  if (f.minPrice) p.set('min', String(f.minPrice));
  if (f.maxPrice) p.set('max', String(f.maxPrice));
  if (f.minSurface) p.set('surface', String(f.minSurface));
  if (f.rooms) p.set('pieces', String(f.rooms));
  if (sort && sort !== 'recent') p.set('tri', sort);
  return p;
}

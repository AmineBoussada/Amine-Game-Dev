import type { Scene } from '../utils/illustrations';

/**
 * Property model.
 * Mirrors the future WordPress Custom Post Type `property` and its custom fields
 * (see docs/WORDPRESS.md). Keep this as the single contract between data and UI.
 */
export type TransactionType = 'vente' | 'location';
export type PropertyType = 'appartement' | 'villa' | 'terrain' | 'local';
export type PropertyStatus = 'disponible' | 'sous-compromis' | 'vendu' | 'loue';

export interface PropertyImage {
  src: string;
  alt: string;
  /** Illustration shown when the photo is unavailable. */
  scene: Scene;
}

export interface Property {
  id: string;
  slug: string;
  reference: string;
  title: string;
  /** Price in DZD. For rentals, monthly rent. */
  price: number;
  transaction: TransactionType;
  type: PropertyType;
  /** Short typology label, e.g. "F3", "Villa R+1". */
  typology: string;
  wilaya: string;
  commune: string;
  /** Neighbourhood / landmark shown in the location block. */
  quartier?: string;
  /** Surface in m² (habitable, or land area for terrains). */
  surface: number;
  /** Land surface for villas, in m². */
  landSurface?: number;
  rooms: number;
  bedrooms: number;
  bathrooms: number;
  floor?: string;
  yearBuilt?: number;
  excerpt: string;
  description: string[];
  features: string[];
  images: PropertyImage[];
  featured: boolean;
  status: PropertyStatus;
  /** ISO date, used for "Plus récents" sorting. */
  createdAt: string;
}

export interface PropertyFilters {
  transaction?: TransactionType | '';
  type?: PropertyType | '';
  wilaya?: string;
  minPrice?: number;
  maxPrice?: number;
  minSurface?: number;
  rooms?: number;
}

export type SortOption = 'recent' | 'price-asc' | 'price-desc' | 'surface-desc';

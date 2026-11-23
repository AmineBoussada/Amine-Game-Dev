import type { Property } from '../types/property';
import { PropertyCard } from './PropertyCard';

export function PropertyGrid({ items }: { items: Property[] }) {
  return (
    <div className="property-grid">
      {items.map((p, i) => (
        <PropertyCard key={p.id} property={p} className={`reveal reveal-delay-${i % 3}`} />
      ))}
    </div>
  );
}

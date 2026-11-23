import { Link } from 'react-router-dom';
import type { Property } from '../types/property';
import { transactionLabels, typeLabels } from '../data/site';
import { formatPrice, priceSuffix } from '../utils/format';
import { FavoriteButton } from './FavoriteButton';
import { Icon } from './Icon';
import { SmartImage } from './SmartImage';

export function PropertyCard({ property: p, className = '' }: { property: Property; className?: string }) {
  const href = `/biens/${p.slug}`;
  return (
    <article className={`property-card ${className}`}>
      <div className="property-card__media">
        <Link to={href} tabIndex={-1} aria-hidden="true">
          <SmartImage src={p.images[0].src.replace('w=1600', 'w=900')} alt={p.images[0].alt} frameClassName="ratio-4-3" />
        </Link>
        <span className={`badge badge--${p.transaction}`}>{transactionLabels[p.transaction]}</span>
        <FavoriteButton id={p.id} title={p.title} className="property-card__fav" />
        <span className="property-card__count" aria-hidden="true">
          <Icon name="image" size={14} /> {p.images.length}
        </span>
      </div>

      <div className="property-card__body">
        <div className="property-card__meta">
          <span>{p.type === 'appartement' ? `${typeLabels[p.type]} ${p.typology}` : p.typology}</span>
          <span className="dot" aria-hidden="true" />
          <span className="property-card__ref">Réf. {p.reference}</span>
        </div>
        <h3 className="property-card__title">
          <Link to={href} className="stretched-link">
            {p.title}
          </Link>
        </h3>
        <p className="property-card__location">
          <Icon name="pin" size={16} /> {p.commune}, {p.wilaya}
        </p>

        <ul className="property-card__specs" aria-label="Caractéristiques">
          <li>
            <Icon name="area" size={16} />
            <span>{p.surface} m²</span>
          </li>
          {p.rooms > 0 && (
            <li>
              <Icon name="rooms" size={16} />
              <span>
                {p.rooms} pièce{p.rooms > 1 ? 's' : ''}
              </span>
            </li>
          )}
          {p.bedrooms > 0 && (
            <li>
              <Icon name="bed" size={16} />
              <span>
                {p.bedrooms} ch.
              </span>
            </li>
          )}
          {p.type === 'terrain' && (
            <li>
              <Icon name="layers" size={16} />
              <span>Constructible</span>
            </li>
          )}
        </ul>

        <div className="property-card__footer">
          <p className="property-card__price">
            {formatPrice(p.price)}
            {priceSuffix(p) && <small>{priceSuffix(p)}</small>}
          </p>
          <span className="property-card__more" aria-hidden="true">
            Voir le bien <Icon name="arrow" size={16} />
          </span>
        </div>
      </div>
    </article>
  );
}

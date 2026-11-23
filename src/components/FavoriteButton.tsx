import { useFavorites } from '../hooks/useFavorites';
import { Icon } from './Icon';

export function FavoriteButton({ id, title, className = '' }: { id: string; title: string; className?: string }) {
  const { isFavorite, toggleFavorite } = useFavorites();
  const active = isFavorite(id);
  return (
    <button
      type="button"
      className={`fav-btn ${active ? 'is-active' : ''} ${className}`}
      aria-pressed={active}
      aria-label={active ? `Retirer « ${title} » des favoris` : `Ajouter « ${title} » aux favoris`}
      title={active ? 'Retirer des favoris' : 'Ajouter aux favoris'}
      onClick={(e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleFavorite(id);
      }}
    >
      <Icon name="heart" size={18} />
    </button>
  );
}

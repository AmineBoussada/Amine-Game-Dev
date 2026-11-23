import { Link } from 'react-router-dom';
import { Icon } from '../components/Icon';
import { PageHeader } from '../components/PageHeader';
import { PropertyGrid } from '../components/PropertyGrid';
import { properties } from '../data/properties';
import { useDocumentMeta } from '../hooks/useDocumentMeta';
import { useFavorites } from '../hooks/useFavorites';

export default function Favorites() {
  useDocumentMeta('Mes favoris', 'Retrouvez les biens que vous avez sauvegardés sur NOVA IMMOBILIER.');
  const { favorites } = useFavorites();
  const items = properties.filter((p) => favorites.includes(p.id));

  return (
    <>
      <PageHeader
        title="Mes favoris"
        subtitle="Les biens que vous avez sauvegardés sont conservés sur cet appareil."
        crumbs={[{ label: 'Mes favoris' }]}
      />
      <section className="section">
        <div className="container">
          {items.length ? (
            <PropertyGrid items={items} />
          ) : (
            <div className="empty-state">
              <span className="empty-state__icon">
                <Icon name="heart" size={26} />
              </span>
              <h2>Aucun favori pour le moment</h2>
              <p>Cliquez sur le cœur d’une annonce pour la retrouver facilement ici et la comparer avec d’autres biens.</p>
              <div className="empty-state__actions">
                <Link to="/biens" className="btn btn--primary btn--sm">
                  Parcourir nos biens
                </Link>
              </div>
            </div>
          )}
        </div>
      </section>
    </>
  );
}

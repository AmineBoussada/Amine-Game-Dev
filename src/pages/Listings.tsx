import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { FilterPanel } from '../components/FilterPanel';
import { Icon } from '../components/Icon';
import { PageHeader } from '../components/PageHeader';
import { PropertyGrid } from '../components/PropertyGrid';
import { properties } from '../data/properties';
import { transactionLabels, typeLabels } from '../data/site';
import { useDocumentMeta } from '../hooks/useDocumentMeta';
import type { PropertyFilters, SortOption } from '../types/property';
import { filterProperties, filtersFromParams, filtersToParams, formatPriceShort, sortProperties } from '../utils/format';

const sortLabels: Record<SortOption, string> = {
  recent: 'Plus récents',
  'price-asc': 'Prix croissant',
  'price-desc': 'Prix décroissant',
  'surface-desc': 'Surface décroissante',
};

export default function Listings() {
  const [params, setParams] = useSearchParams();
  const filters = useMemo(() => filtersFromParams(params), [params]);
  const sortParam = params.get('tri') as SortOption | null;
  const sort: SortOption = sortParam && sortParam in sortLabels ? sortParam : 'recent';
  const [drawerOpen, setDrawerOpen] = useState(false);

  const results = useMemo(() => sortProperties(filterProperties(properties, filters), sort), [filters, sort]);

  const heading =
    filters.transaction === 'vente'
      ? 'Biens à vendre'
      : filters.transaction === 'location'
        ? 'Biens à louer'
        : 'Nos biens immobiliers';

  useDocumentMeta(
    heading,
    'Consultez les appartements, villas, terrains et locaux commerciaux à vendre et à louer proposés par NOVA IMMOBILIER à Alger et en Algérie.',
  );

  useEffect(() => {
    document.body.classList.toggle('no-scroll', drawerOpen);
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setDrawerOpen(false);
    window.addEventListener('keydown', onKey);
    return () => {
      window.removeEventListener('keydown', onKey);
      document.body.classList.remove('no-scroll');
    };
  }, [drawerOpen]);

  const update = (next: PropertyFilters, nextSort: SortOption = sort) =>
    setParams(filtersToParams(next, nextSort), { replace: true, preventScrollReset: true });
  const reset = () => setParams(new URLSearchParams(), { replace: true });

  const chips: { label: string; clear: Partial<PropertyFilters> }[] = [];
  if (filters.transaction) chips.push({ label: transactionLabels[filters.transaction], clear: { transaction: '' } });
  if (filters.type) chips.push({ label: typeLabels[filters.type], clear: { type: '' } });
  if (filters.wilaya) chips.push({ label: filters.wilaya, clear: { wilaya: '' } });
  if (filters.minPrice) chips.push({ label: `Dès ${formatPriceShort(filters.minPrice)}`, clear: { minPrice: undefined } });
  if (filters.maxPrice) chips.push({ label: `Jusqu’à ${formatPriceShort(filters.maxPrice)}`, clear: { maxPrice: undefined } });
  if (filters.minSurface) chips.push({ label: `${filters.minSurface} m² +`, clear: { minSurface: undefined } });
  if (filters.rooms) chips.push({ label: `${filters.rooms}+ pièces`, clear: { rooms: undefined } });

  return (
    <>
      <PageHeader
        title={heading}
        subtitle="Trouvez le bien qui correspond à votre projet."
        crumbs={[{ label: 'Nos biens' }]}
      />

      <section className="section listings">
        <div className="container listings__layout">
          <aside className={`listings__sidebar ${drawerOpen ? 'is-open' : ''}`} aria-label="Filtres de recherche">
            <div className="listings__sidebar-head">
              <h2>Filtrer</h2>
              <button type="button" className="icon-btn listings__close" onClick={() => setDrawerOpen(false)} aria-label="Fermer les filtres">
                <Icon name="close" />
              </button>
            </div>
            <FilterPanel filters={filters} onChange={(f) => update(f)} onReset={reset} />
            <div className="listings__drawer-footer">
              <button type="button" className="btn btn--primary btn--block" onClick={() => setDrawerOpen(false)}>
                Voir {results.length} résultat{results.length > 1 ? 's' : ''}
              </button>
            </div>
          </aside>
          {drawerOpen && <div className="listings__backdrop" onClick={() => setDrawerOpen(false)} aria-hidden="true" />}

          <div className="listings__main">
            <div className="listings__toolbar">
              <p className="listings__count" aria-live="polite">
                <strong>{results.length}</strong> bien{results.length > 1 ? 's' : ''} trouvé{results.length > 1 ? 's' : ''}
              </p>
              <div className="listings__tools">
                <button type="button" className="btn btn--outline btn--sm listings__filter-btn" onClick={() => setDrawerOpen(true)}>
                  <Icon name="filter" size={18} /> Filtres {chips.length > 0 && <span className="pill-count">{chips.length}</span>}
                </button>
                <label className="sort-select">
                  <span className="sr-only">Trier par</span>
                  <select value={sort} onChange={(e) => update(filters, e.target.value as SortOption)}>
                    {Object.entries(sortLabels).map(([k, v]) => (
                      <option key={k} value={k}>
                        {v}
                      </option>
                    ))}
                  </select>
                </label>
              </div>
            </div>

            {chips.length > 0 && (
              <ul className="active-filters" aria-label="Filtres actifs">
                {chips.map((c) => (
                  <li key={c.label}>
                    <button type="button" className="chip chip--removable" onClick={() => update({ ...filters, ...c.clear })}>
                      {c.label} <Icon name="close" size={14} />
                      <span className="sr-only">Retirer ce filtre</span>
                    </button>
                  </li>
                ))}
                <li>
                  <button type="button" className="active-filters__clear" onClick={reset}>
                    Tout effacer
                  </button>
                </li>
              </ul>
            )}

            {results.length > 0 ? (
              <PropertyGrid items={results} />
            ) : (
              <div className="empty-state">
                <span className="empty-state__icon">
                  <Icon name="search" size={26} />
                </span>
                <h2>Aucun bien ne correspond à vos critères</h2>
                <p>
                  Élargissez votre recherche ou confiez-nous votre projet : nous vous informerons dès qu’un bien correspondant
                  arrive en exclusivité.
                </p>
                <div className="empty-state__actions">
                  <button type="button" className="btn btn--outline btn--sm" onClick={reset}>
                    Réinitialiser les filtres
                  </button>
                  <Link to="/contact?sujet=recherche" className="btn btn--primary btn--sm">
                    Confier ma recherche
                  </Link>
                </div>
              </div>
            )}
          </div>
        </div>
      </section>
    </>
  );
}

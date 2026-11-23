import type { PropertyFilters, PropertyType, TransactionType } from '../types/property';
import { getWilayas } from '../data/properties';
import { budgetOptions, typeLabels } from '../data/site';
import { formatPriceShort } from '../utils/format';

interface Props {
  filters: PropertyFilters;
  onChange: (next: PropertyFilters) => void;
  onReset: () => void;
  idPrefix?: string;
}

const surfaces = [50, 80, 100, 150, 200, 300, 500];
const roomsOptions = [1, 2, 3, 4, 5];

export function FilterPanel({ filters: f, onChange, onReset, idPrefix = 'f' }: Props) {
  const set = (patch: Partial<PropertyFilters>) => onChange({ ...f, ...patch });
  const budgets = f.transaction
    ? budgetOptions[f.transaction]
    : [...budgetOptions.location, ...budgetOptions.vente].sort((a, b) => a - b);
  const invalidRange = !!f.minPrice && !!f.maxPrice && f.minPrice > f.maxPrice;

  return (
    <div className="filters">
      <fieldset className="filters__group">
        <legend className="field__label">Transaction</legend>
        <div className="segmented">
          {([
            ['', 'Tous'],
            ['vente', 'Vente'],
            ['location', 'Location'],
          ] as [TransactionType | '', string][]).map(([value, label]) => (
            <button
              key={label}
              type="button"
              aria-pressed={(f.transaction ?? '') === value}
              className={(f.transaction ?? '') === value ? 'is-active' : ''}
              onClick={() => set({ transaction: value, minPrice: undefined, maxPrice: undefined })}
            >
              {label}
            </button>
          ))}
        </div>
      </fieldset>

      <label className="field" htmlFor={`${idPrefix}-type`}>
        <span className="field__label">Type de bien</span>
        <select id={`${idPrefix}-type`} value={f.type ?? ''} onChange={(e) => set({ type: e.target.value as PropertyType | '' })}>
          <option value="">Tous les types</option>
          {Object.entries(typeLabels).map(([k, v]) => (
            <option key={k} value={k}>
              {v}
            </option>
          ))}
        </select>
      </label>

      <label className="field" htmlFor={`${idPrefix}-wilaya`}>
        <span className="field__label">Wilaya</span>
        <select id={`${idPrefix}-wilaya`} value={f.wilaya ?? ''} onChange={(e) => set({ wilaya: e.target.value })}>
          <option value="">Toutes les wilayas</option>
          {getWilayas().map((w) => (
            <option key={w} value={w}>
              {w}
            </option>
          ))}
        </select>
      </label>

      <div className="filters__row">
        <label className="field" htmlFor={`${idPrefix}-min`}>
          <span className="field__label">Prix min.</span>
          <select
            id={`${idPrefix}-min`}
            value={f.minPrice ?? ''}
            onChange={(e) => set({ minPrice: e.target.value ? Number(e.target.value) : undefined })}
          >
            <option value="">Min.</option>
            {budgets.map((b) => (
              <option key={b} value={b}>
                {formatPriceShort(b)}
              </option>
            ))}
          </select>
        </label>
        <label className="field" htmlFor={`${idPrefix}-max`}>
          <span className="field__label">Prix max.</span>
          <select
            id={`${idPrefix}-max`}
            value={f.maxPrice ?? ''}
            aria-invalid={invalidRange}
            onChange={(e) => set({ maxPrice: e.target.value ? Number(e.target.value) : undefined })}
          >
            <option value="">Max.</option>
            {budgets.map((b) => (
              <option key={b} value={b}>
                {formatPriceShort(b)}
              </option>
            ))}
          </select>
        </label>
      </div>
      {invalidRange && (
        <p className="field__error" role="alert">
          Le prix minimum est supérieur au prix maximum.
        </p>
      )}

      <label className="field" htmlFor={`${idPrefix}-surface`}>
        <span className="field__label">Surface minimum</span>
        <select
          id={`${idPrefix}-surface`}
          value={f.minSurface ?? ''}
          onChange={(e) => set({ minSurface: e.target.value ? Number(e.target.value) : undefined })}
        >
          <option value="">Indifférente</option>
          {surfaces.map((s) => (
            <option key={s} value={s}>
              {s} m² et plus
            </option>
          ))}
        </select>
      </label>

      <fieldset className="filters__group">
        <legend className="field__label">Pièces</legend>
        <div className="chips">
          {roomsOptions.map((r) => (
            <button
              key={r}
              type="button"
              aria-pressed={f.rooms === r}
              className={`chip ${f.rooms === r ? 'is-active' : ''}`}
              onClick={() => set({ rooms: f.rooms === r ? undefined : r })}
            >
              {r}
              {r === 5 ? '+' : ''}
              <span className="sr-only"> pièce{r > 1 ? 's' : ''} minimum</span>
            </button>
          ))}
        </div>
      </fieldset>

      <button type="button" className="btn btn--outline btn--block btn--sm" onClick={onReset}>
        Réinitialiser les filtres
      </button>
    </div>
  );
}

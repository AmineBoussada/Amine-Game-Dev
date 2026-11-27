import { useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import type { PropertyFilters, PropertyType, TransactionType } from '../types/property';
import { getWilayas } from '../data/properties';
import { budgetOptions, typeLabels } from '../data/site';
import { filtersToParams, formatPriceShort } from '../utils/format';
import { Icon } from './Icon';

/** Homepage search: builds the listing URL so results are shareable. */
export function SearchBar() {
  const navigate = useNavigate();
  const [transaction, setTransaction] = useState<TransactionType>('vente');
  const [type, setType] = useState<PropertyType | ''>('');
  const [wilaya, setWilaya] = useState('');
  const [min, setMin] = useState('');
  const [max, setMax] = useState('');
  const budgets = budgetOptions[transaction];
  const invalidRange = !!min && !!max && Number(min) > Number(max);

  const onTransaction = (t: TransactionType) => {
    setTransaction(t);
    setMin('');
    setMax('');
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (invalidRange) return;
    const f: PropertyFilters = {
      transaction,
      type,
      wilaya,
      minPrice: min ? Number(min) : undefined,
      maxPrice: max ? Number(max) : undefined,
    };
    navigate(`/biens?${filtersToParams(f).toString()}`);
  };

  return (
    <form className="search-bar" onSubmit={submit} role="search" aria-label="Rechercher un bien">
      <div className="search-bar__tabs" role="radiogroup" aria-label="Type de transaction">
        {(['vente', 'location'] as const).map((t) => (
          <button
            key={t}
            type="button"
            role="radio"
            aria-checked={transaction === t}
            className={transaction === t ? 'is-active' : ''}
            onClick={() => onTransaction(t)}
          >
            {t === 'vente' ? 'Acheter' : 'Louer'}
          </button>
        ))}
      </div>

      <div className="search-bar__fields">
        <label className="field">
          <span className="field__label">Type de bien</span>
          <select value={type} onChange={(e) => setType(e.target.value as PropertyType | '')}>
            <option value="">Tous les biens</option>
            {Object.entries(typeLabels).map(([k, v]) => (
              <option key={k} value={k}>
                {v}
              </option>
            ))}
          </select>
        </label>

        <label className="field">
          <span className="field__label">Wilaya</span>
          <select value={wilaya} onChange={(e) => setWilaya(e.target.value)}>
            <option value="">Toutes les wilayas</option>
            {getWilayas().map((w) => (
              <option key={w} value={w}>
                {w}
              </option>
            ))}
          </select>
        </label>

        <label className="field">
          <span className="field__label">Budget min.</span>
          <select value={min} onChange={(e) => setMin(e.target.value)}>
            <option value="">Indifférent</option>
            {budgets.map((b) => (
              <option key={b} value={b}>
                {formatPriceShort(b)}
              </option>
            ))}
          </select>
        </label>

        <label className="field">
          <span className="field__label">Budget max.</span>
          <select value={max} onChange={(e) => setMax(e.target.value)} aria-invalid={invalidRange}>
            <option value="">Indifférent</option>
            {budgets.map((b) => (
              <option key={b} value={b}>
                {formatPriceShort(b)}
              </option>
            ))}
          </select>
        </label>

        <button type="submit" className="btn btn--primary search-bar__submit" disabled={invalidRange}>
          <Icon name="search" size={18} /> Rechercher
        </button>
      </div>
      {invalidRange && (
        <p className="search-bar__error" role="alert">
          Le budget minimum doit être inférieur au budget maximum.
        </p>
      )}
    </form>
  );
}

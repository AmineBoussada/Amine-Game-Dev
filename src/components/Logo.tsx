import { Link } from 'react-router-dom';

export function Logo({ light = false, onClick }: { light?: boolean; onClick?: () => void }) {
  return (
    <Link to="/" className={`logo ${light ? 'logo--light' : ''}`} aria-label="NOVA IMMOBILIER — Accueil" onClick={onClick}>
      <svg className="logo__mark" viewBox="0 0 40 40" aria-hidden="true">
        <rect x="0.75" y="0.75" width="38.5" height="38.5" rx="2" fill="none" stroke="currentColor" strokeWidth="1.5" />
        <path d="M12 29V11l16 18V11" fill="none" stroke="var(--c-bronze)" strokeWidth="2.2" strokeLinecap="square" />
      </svg>
      <span className="logo__text">
        <span className="logo__name">NOVA</span>
        <span className="logo__sub">IMMOBILIER</span>
      </span>
    </Link>
  );
}

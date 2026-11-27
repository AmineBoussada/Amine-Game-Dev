import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';

interface Props {
  title: string;
  subtitle?: string;
  crumbs?: { label: string; to?: string }[];
  children?: ReactNode;
}

export function PageHeader({ title, subtitle, crumbs = [], children }: Props) {
  return (
    <section className="page-header">
      <div className="container">
        <nav className="breadcrumb" aria-label="Fil d’Ariane">
          <ol>
            <li>
              <Link to="/">Accueil</Link>
            </li>
            {crumbs.map((c) => (
              <li key={c.label}>{c.to ? <Link to={c.to}>{c.label}</Link> : <span aria-current="page">{c.label}</span>}</li>
            ))}
          </ol>
        </nav>
        <h1>{title}</h1>
        {subtitle && <p className="lead">{subtitle}</p>}
        {children}
      </div>
    </section>
  );
}

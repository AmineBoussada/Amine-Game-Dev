import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ContactForm } from '../components/ContactForm';
import { FavoriteButton } from '../components/FavoriteButton';
import { Gallery } from '../components/Gallery';
import { Icon, type IconName } from '../components/Icon';
import { PropertyCard } from '../components/PropertyCard';
import { getPropertyBySlug, properties } from '../data/properties';
import { agency, transactionLabels, typeLabels } from '../data/site';
import { useDocumentMeta } from '../hooks/useDocumentMeta';
import { formatCentimes, formatPrice, mapsLink, priceSuffix, propertyWhatsappMessage, whatsappLink } from '../utils/format';
import NotFound from './NotFound';

const statusLabels = { disponible: 'Disponible', 'sous-compromis': 'Sous compromis', vendu: 'Vendu', loue: 'Loué' };

/** Keyed by slug so gallery/form state resets when navigating between properties. */
export default function PropertyDetail() {
  const { slug = '' } = useParams();
  return <PropertyDetailView key={slug} slug={slug} />;
}

function PropertyDetailView({ slug }: { slug: string }) {
  const p = getPropertyBySlug(slug);
  const [subject, setSubject] = useState(p?.transaction === 'location' ? 'Location d’un bien' : 'Achat d’un bien');
  const [shared, setShared] = useState(false);

  useDocumentMeta(
    p ? `${p.title} — ${p.commune}` : 'Bien introuvable',
    p ? `${typeLabels[p.type]} ${p.typology} à ${p.commune} (${p.wilaya}) — ${p.surface} m², ${formatPrice(p.price)}${priceSuffix(p)}. ${p.excerpt}` : undefined,
  );

  if (!p) return <NotFound />;

  const similar = properties
    .filter((x) => x.id !== p.id)
    .map((x) => ({ x, score: (x.type === p.type ? 2 : 0) + (x.transaction === p.transaction ? 1 : 0) + (x.wilaya === p.wilaya ? 1 : 0) }))
    .sort((a, b) => b.score - a.score)
    .slice(0, 3)
    .map(({ x }) => x);

  const wa = whatsappLink(propertyWhatsappMessage(p));

  const requestVisit = () => {
    setSubject('Demande de visite');
    document.getElementById('contact-bien')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    window.setTimeout(() => document.querySelector<HTMLInputElement>('#contact-bien input')?.focus({ preventScroll: true }), 600);
  };

  const share = async () => {
    const data = { title: p.title, text: `${p.title} — NOVA IMMOBILIER`, url: window.location.href };
    try {
      if (navigator.share) await navigator.share(data);
      else {
        await navigator.clipboard.writeText(data.url);
        setShared(true);
        window.setTimeout(() => setShared(false), 2200);
      }
    } catch {
      /* user cancelled */
    }
  };

  const specs: { icon: IconName; label: string; value: string }[] = [
    { icon: 'area', label: p.type === 'terrain' ? 'Superficie' : 'Surface habitable', value: `${p.surface} m²` },
    ...(p.landSurface ? [{ icon: 'layers' as IconName, label: 'Terrain', value: `${p.landSurface} m²` }] : []),
    ...(p.rooms ? [{ icon: 'rooms' as IconName, label: 'Pièces', value: String(p.rooms) }] : []),
    ...(p.bedrooms ? [{ icon: 'bed' as IconName, label: 'Chambres', value: String(p.bedrooms) }] : []),
    ...(p.bathrooms ? [{ icon: 'bath' as IconName, label: 'Salles de bain', value: String(p.bathrooms) }] : []),
    ...(p.floor ? [{ icon: 'floor' as IconName, label: 'Étage', value: p.floor }] : []),
    ...(p.yearBuilt ? [{ icon: 'calendar' as IconName, label: 'Construction', value: String(p.yearBuilt) }] : []),
  ];

  const details: [string, string][] = [
    ['Référence', p.reference],
    ['Transaction', transactionLabels[p.transaction]],
    ['Type de bien', typeLabels[p.type]],
    ['Typologie', p.typology],
    ['Wilaya', p.wilaya],
    ['Commune', p.commune],
    ['Disponibilité', statusLabels[p.status]],
    [p.transaction === 'location' ? 'Loyer mensuel' : 'Prix', `${formatPrice(p.price)}${priceSuffix(p)}`],
  ];

  return (
    <article className="detail">
      <div className="container">
        <nav className="breadcrumb detail__crumbs" aria-label="Fil d’Ariane">
          <ol>
            <li>
              <Link to="/">Accueil</Link>
            </li>
            <li>
              <Link to="/biens">Nos biens</Link>
            </li>
            <li>
              <Link to={`/biens?transaction=${p.transaction}`}>{p.transaction === 'vente' ? 'Acheter' : 'Louer'}</Link>
            </li>
            <li>
              <span aria-current="page">{p.reference}</span>
            </li>
          </ol>
        </nav>

        <header className="detail__header">
          <div>
            <div className="detail__badges">
              <span className={`badge badge--${p.transaction}`}>{transactionLabels[p.transaction]}</span>
              <span className="badge badge--soft">{p.typology}</span>
              <span className="detail__ref">Réf. {p.reference}</span>
            </div>
            <h1>{p.title}</h1>
            <p className="detail__location">
              <Icon name="pin" size={18} /> {p.quartier ? `${p.quartier} · ` : ''}
              {p.commune}, {p.wilaya}
            </p>
          </div>
          <div className="detail__price-block">
            <p className="detail__price">
              {formatPrice(p.price)}
              {priceSuffix(p) && <small>{priceSuffix(p)}</small>}
            </p>
            {p.transaction === 'vente' && <p className="detail__centimes">{formatCentimes(p.price)}</p>}
            <div className="detail__actions">
              <FavoriteButton id={p.id} title={p.title} className="fav-btn--outline" />
              <button type="button" className="fav-btn fav-btn--outline" onClick={share} aria-label="Partager ce bien" title="Partager">
                <Icon name="share" size={18} />
              </button>
              {shared && <span className="detail__toast" role="status">Lien copié</span>}
            </div>
          </div>
        </header>

        <Gallery images={p.images} title={p.title} />

        <div className="detail__layout">
          <div className="detail__content">
            <ul className="detail__specs" aria-label="Caractéristiques principales">
              {specs.map((s) => (
                <li key={s.label}>
                  <Icon name={s.icon} size={22} />
                  <span>
                    <small>{s.label}</small>
                    <strong>{s.value}</strong>
                  </span>
                </li>
              ))}
            </ul>

            <section className="detail__section" aria-labelledby="desc-title">
              <h2 id="desc-title">Description</h2>
              <p className="detail__excerpt">{p.excerpt}</p>
              {p.description.map((para) => (
                <p key={para.slice(0, 24)}>{para}</p>
              ))}
            </section>

            <section className="detail__section" aria-labelledby="specs-title">
              <h2 id="specs-title">Caractéristiques</h2>
              <dl className="detail__table">
                {details.map(([k, v]) => (
                  <div key={k}>
                    <dt>{k}</dt>
                    <dd>{v}</dd>
                  </div>
                ))}
              </dl>
            </section>

            <section className="detail__section" aria-labelledby="feat-title">
              <h2 id="feat-title">Équipements et prestations</h2>
              <ul className="detail__features">
                {p.features.map((f) => (
                  <li key={f}>
                    <Icon name="check" size={16} /> {f}
                  </li>
                ))}
              </ul>
            </section>

            <section className="detail__section" aria-labelledby="loc-title">
              <h2 id="loc-title">Localisation</h2>
              <div className="detail__map">
                <div className="detail__map-visual" aria-hidden="true">
                  <span className="detail__map-pin">
                    <Icon name="pin" size={26} />
                  </span>
                </div>
                <div className="detail__map-info">
                  <p>
                    <strong>
                      {p.commune}, {p.wilaya}
                    </strong>
                    {p.quartier && <span>{p.quartier}</span>}
                  </p>
                  <p className="muted">
                    Pour préserver la confidentialité du propriétaire, l’adresse exacte est communiquée lors de la prise de
                    rendez-vous.
                  </p>
                  <a href={mapsLink(`${p.commune}, ${p.wilaya}, Algérie`)} target="_blank" rel="noopener noreferrer" className="link-arrow">
                    Voir le secteur sur Google Maps <Icon name="arrow" size={16} />
                  </a>
                </div>
              </div>
            </section>

            <section className="detail__section detail__form" id="contact-bien" aria-labelledby="form-title">
              <h2 id="form-title">Ce bien vous intéresse ?</h2>
              <p className="muted">
                Laissez-nous vos coordonnées : un conseiller vous recontacte rapidement pour répondre à vos questions ou organiser
                une visite.
              </p>
              <ContactForm
                subject={subject}
                propertyRef={p.reference}
                defaultMessage={`Bonjour, je suis intéressé(e) par le bien réf. ${p.reference} (${p.typology} à ${p.commune}). `}
                submitLabel="Envoyer ma demande"
              />
            </section>
          </div>

          <aside className="detail__aside" aria-label="Contacter l’agence">
            <div className="agent-card">
              <p className="agent-card__price">
                {formatPrice(p.price)}
                {priceSuffix(p) && <small>{priceSuffix(p)}</small>}
              </p>
              <p className="agent-card__status">
                <span className="status-dot" /> {statusLabels[p.status]}
              </p>
              <div className="agent-card__agent">
                <span className="agent-card__avatar" aria-hidden="true">
                  NI
                </span>
                <div>
                  <strong>Votre conseiller NOVA</strong>
                  <span>Réponse sous 24 h ouvrées</span>
                </div>
              </div>
              <div className="agent-card__actions">
                <button type="button" className="btn btn--primary btn--block" onClick={requestVisit}>
                  <Icon name="calendar" size={18} /> Demander une visite
                </button>
                <a href={wa} target="_blank" rel="noopener noreferrer" className="btn btn--whatsapp btn--block">
                  <Icon name="whatsapp" size={18} /> Contacter sur WhatsApp
                </a>
                <a href={agency.phoneHref} className="btn btn--outline btn--block">
                  <Icon name="phone" size={18} /> {agency.phone}
                </a>
              </div>
              <p className="agent-card__note">
                Prix indicatif — contenu de démonstration. Honoraires d’agence communiqués sur demande.
              </p>
            </div>
          </aside>
        </div>
      </div>

      <section className="section section--white detail__similar" aria-labelledby="similar-title">
        <div className="container">
          <div className="section-heading">
            <div>
              <span className="eyebrow">À découvrir aussi</span>
              <h2 id="similar-title" className="section-heading__title">
                Biens similaires
              </h2>
            </div>
            <Link to={`/biens?type=${p.type}`} className="link-arrow">
              Tous les biens de ce type <Icon name="arrow" size={16} />
            </Link>
          </div>
          <div className="property-grid">
            {similar.map((s) => (
              <PropertyCard key={s.id} property={s} />
            ))}
          </div>
        </div>
      </section>

      {/* Mobile sticky conversion bar */}
      <div className="detail__mobile-bar">
        <a href={agency.phoneHref} className="btn btn--outline" aria-label="Appeler l’agence">
          <Icon name="phone" size={18} />
        </a>
        <a href={wa} target="_blank" rel="noopener noreferrer" className="btn btn--whatsapp">
          <Icon name="whatsapp" size={18} /> WhatsApp
        </a>
        <button type="button" className="btn btn--primary" onClick={requestVisit}>
          Visite
        </button>
      </div>
    </article>
  );
}

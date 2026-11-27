import { Link } from 'react-router-dom';
import { CtaBanner } from '../components/CtaBanner';
import { Icon } from '../components/Icon';
import { PropertyGrid } from '../components/PropertyGrid';
import { SearchBar } from '../components/SearchBar';
import { SectionHeading } from '../components/SectionHeading';
import { SmartImage } from '../components/SmartImage';
import { getFeaturedProperties, properties } from '../data/properties';
import { about, categories, hero, values } from '../data/site';
import { useDocumentMeta } from '../hooks/useDocumentMeta';

export default function Home() {
  useDocumentMeta(
    '',
    'NOVA IMMOBILIER, agence immobilière à Alger : appartements, villas, terrains et locaux commerciaux à vendre et à louer en Algérie.',
  );
  const featured = getFeaturedProperties().slice(0, 6);
  const countByType = (t: string) => properties.filter((p) => p.type === t).length;

  return (
    <>
      {/* ---------- Hero ---------- */}
      <section className="hero" aria-labelledby="hero-title">
        <div className="hero__media">
          <SmartImage src={hero.image} alt={hero.imageAlt} scene={hero.scene} priority frameClassName="hero__img" />
          <div className="hero__overlay" />
        </div>
        <div className="container hero__content">
          <span className="eyebrow hero__eyebrow">Agence immobilière · Alger</span>
          <h1 id="hero-title" className="hero__title">
            {hero.title}
          </h1>
          <p className="hero__text">{hero.text}</p>
          <div className="hero__actions">
            <Link to="/biens" className="btn btn--bronze">
              Découvrir nos biens <Icon name="arrow" size={18} />
            </Link>
            <Link to="/contact" className="btn btn--ghost-light">
              Contactez-nous
            </Link>
          </div>
        </div>
      </section>

      {/* ---------- Search ---------- */}
      <section className="home-search" aria-label="Recherche de biens">
        <div className="container">
          <SearchBar />
        </div>
      </section>

      {/* ---------- Featured ---------- */}
      <section className="section" aria-labelledby="featured-title">
        <div className="container">
          <SectionHeading
              id="featured-title"
              eyebrow="Sélection du moment"
              title="Biens à la une"
              text="Une sélection de biens récemment confiés à notre agence, visités et vérifiés par nos conseillers."
              action={
                <Link to="/biens" className="link-arrow">
                  Voir tous les biens <Icon name="arrow" size={16} />
                </Link>
              }
            />
          <PropertyGrid items={featured} />
        </div>
      </section>

      {/* ---------- Why us ---------- */}
      <section className="section section--white" aria-labelledby="why-title">
        <div className="container why">
          <div className="why__intro reveal">
            <span className="eyebrow">Pourquoi nous choisir ?</span>
            <h2 id="why-title">Un accompagnement exigeant, du premier appel à la remise des clés.</h2>
            <p className="lead">
              Acheter, vendre ou louer un bien en Algérie demande du temps, des vérifications et de la confiance. Nous
              prenons tout cela en charge.
            </p>
            <Link to="/a-propos" className="link-arrow">
              Notre approche <Icon name="arrow" size={16} />
            </Link>
          </div>
          <ul className="why__grid">
            {values.map((v, i) => (
              <li key={v.title} className={`why__item reveal reveal-delay-${i}`}>
                <span className="why__icon">
                  <Icon name={v.icon} size={26} />
                </span>
                <h3>{v.title}</h3>
                <p>{v.text}</p>
              </li>
            ))}
          </ul>
        </div>
      </section>

      {/* ---------- Categories ---------- */}
      <section className="section" aria-labelledby="cat-title">
        <div className="container">
          <SectionHeading
              id="cat-title"
              eyebrow="Explorer"
              title="Trouvez par type de bien"
              text="Appartements, villas, terrains ou locaux : accédez directement aux biens qui correspondent à votre projet."
            />
          <div className="categories">
            {categories.map((c, i) => (
              <Link key={c.type} to={`/biens?type=${c.type}`} className={`category-card reveal reveal-delay-${i}`}>
                <SmartImage src={c.image} alt={c.label} scene={c.scene} frameClassName="category-card__img" />
                <div className="category-card__body">
                  <span className="category-card__count">
                    {countByType(c.type)} bien{countByType(c.type) > 1 ? 's' : ''}
                  </span>
                  <h3>{c.label}</h3>
                  <p>{c.text}</p>
                  <span className="category-card__arrow" aria-hidden="true">
                    <Icon name="arrow" size={18} />
                  </span>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* ---------- About ---------- */}
      <section className="section section--beige" aria-labelledby="about-title">
        <div className="container about-teaser">
          <div className="about-teaser__media reveal">
            <SmartImage src={about.image} scene={about.scene} alt="Intérieur élégant sélectionné par NOVA IMMOBILIER" frameClassName="ratio-4-5" />
            <div className="about-teaser__badge">
              <strong>12</strong>
              <span>ans au service de l’immobilier algérois</span>
            </div>
          </div>
          <div className="about-teaser__content reveal reveal-delay-1">
            <span className="eyebrow">L’agence</span>
            <h2 id="about-title">{about.title}</h2>
            <p className="lead">{about.text}</p>
            <ul className="stats">
              {about.stats.map((s) => (
                <li key={s.label}>
                  <strong>{s.value}</strong>
                  <span>{s.label}</span>
                </li>
              ))}
            </ul>
            <Link to="/a-propos" className="btn btn--primary">
              En savoir plus sur notre agence <Icon name="arrow" size={18} />
            </Link>
          </div>
        </div>
      </section>

      <CtaBanner />
    </>
  );
}

import { Link } from 'react-router-dom';
import { CtaBanner } from '../components/CtaBanner';
import { Icon } from '../components/Icon';
import { PageHeader } from '../components/PageHeader';
import { SmartImage } from '../components/SmartImage';
import { about, team, values } from '../data/site';
import { useDocumentMeta } from '../hooks/useDocumentMeta';

const steps = [
  { n: '01', title: 'Écoute', text: 'Nous prenons le temps de comprendre votre projet, votre budget et vos priorités.' },
  { n: '02', title: 'Sélection', text: 'Nous identifions et visitons les biens qui correspondent réellement à vos critères.' },
  { n: '03', title: 'Vérification', text: 'Actes, livret foncier, situation juridique : chaque dossier est contrôlé.' },
  { n: '04', title: 'Signature', text: 'Nous vous accompagnons jusqu’à la signature chez le notaire et la remise des clés.' },
];

export default function About() {
  useDocumentMeta(
    'À propos de l’agence',
    'Découvrez NOVA IMMOBILIER, agence immobilière à Alger : notre mission, nos valeurs et notre méthode d’accompagnement.',
  );
  return (
    <>
      <PageHeader title="À propos de NOVA IMMOBILIER" subtitle="Une agence algéroise, une exigence européenne." crumbs={[{ label: 'À propos' }]} />

      <section className="section">
        <div className="container about-teaser">
          <div className="about-teaser__media reveal">
            <SmartImage src={about.image} alt="Intérieur contemporain lumineux" frameClassName="ratio-4-5" />
          </div>
          <div className="about-teaser__content reveal reveal-delay-1">
            <span className="eyebrow">Notre mission</span>
            <h2>{about.title}</h2>
            <p className="lead">{about.text}</p>
            <p className="muted">
              Fondée à Hydra, notre agence intervient sur l’ensemble de la wilaya d’Alger ainsi qu’à Oran et Tipaza. Nous
              travaillons avec un nombre volontairement limité de biens afin de garantir à chaque client un suivi attentif et
              des informations fiables.
            </p>
            <ul className="stats">
              {about.stats.map((s) => (
                <li key={s.label}>
                  <strong>{s.value}</strong>
                  <span>{s.label}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </section>

      <section className="section section--white">
        <div className="container">
          <div className="section-heading section-heading--center reveal">
            <div>
              <span className="eyebrow">Notre méthode</span>
              <h2 className="section-heading__title">Quatre étapes, un seul interlocuteur</h2>
            </div>
          </div>
          <ol className="steps">
            {steps.map((s, i) => (
              <li key={s.n} className={`reveal reveal-delay-${i}`}>
                <span className="steps__n">{s.n}</span>
                <h3>{s.title}</h3>
                <p>{s.text}</p>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="section-heading reveal">
            <div>
              <span className="eyebrow">Nos engagements</span>
              <h2 className="section-heading__title">Ce qui nous distingue</h2>
            </div>
          </div>
          <ul className="why__grid why__grid--4">
            {values.map((v) => (
              <li key={v.title} className="why__item reveal">
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

      <section className="section section--beige">
        <div className="container">
          <div className="section-heading reveal">
            <div>
              <span className="eyebrow">L’équipe</span>
              <h2 className="section-heading__title">Des conseillers spécialisés</h2>
              <p className="lead">Chaque projet est suivi par un conseiller dédié selon la nature de votre recherche.</p>
            </div>
            <Link to="/contact" className="link-arrow">
              Prendre rendez-vous <Icon name="arrow" size={16} />
            </Link>
          </div>
          <ul className="team">
            {team.map((m, i) => (
              <li key={m.name} className={`team__card reveal reveal-delay-${i}`}>
                <span className="team__avatar" aria-hidden="true">
                  {m.initials}
                </span>
                <h3>{m.name}</h3>
                <p>{m.role}</p>
              </li>
            ))}
          </ul>
        </div>
      </section>

      <CtaBanner />
    </>
  );
}

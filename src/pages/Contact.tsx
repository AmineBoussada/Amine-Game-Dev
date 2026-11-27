import { Link, useSearchParams } from 'react-router-dom';
import { ContactForm } from '../components/ContactForm';
import { Icon } from '../components/Icon';
import { PageHeader } from '../components/PageHeader';
import { agency } from '../data/site';
import { useDocumentMeta } from '../hooks/useDocumentMeta';
import { mapsLink, whatsappLink } from '../utils/format';

const subjectFromParam: Record<string, string> = {
  recherche: 'Recherche personnalisée',
  vendre: 'Vendre ou louer mon bien',
  estimation: 'Estimation de mon bien',
  visite: 'Demande de visite',
};

export default function Contact() {
  useDocumentMeta(
    'Contact',
    'Contactez NOVA IMMOBILIER à Hydra, Alger : téléphone, WhatsApp, email et formulaire de contact pour vos projets d’achat, de vente ou de location.',
  );
  const [params] = useSearchParams();
  const subject = subjectFromParam[params.get('sujet') ?? ''] ?? '';

  const cards = [
    { icon: 'phone' as const, title: 'Téléphone', value: agency.phone, href: agency.phoneHref, note: 'Du dimanche au jeudi' },
    {
      icon: 'whatsapp' as const,
      title: 'WhatsApp',
      value: agency.mobile,
      href: whatsappLink('Bonjour NOVA IMMOBILIER, je souhaite avoir des informations.'),
      note: 'Réponse rapide',
      external: true,
    },
    { icon: 'mail' as const, title: 'Email', value: agency.email, href: `mailto:${agency.email}`, note: 'Réponse sous 24 h ouvrées' },
    { icon: 'pin' as const, title: 'Agence', value: agency.city, href: mapsLink(agency.mapQuery), note: agency.address, external: true },
  ];

  return (
    <>
      <PageHeader
        title="Contactez-nous"
        subtitle="Une question, un projet d’achat, de vente ou de location ? Notre équipe vous répond rapidement."
        crumbs={[{ label: 'Contact' }]}
      />

      <section className="section contact">
        <div className="container">
          <ul className="contact__cards">
            {cards.map((c, i) => (
              <li key={c.title} className={`reveal reveal-delay-${i}`}>
                <a href={c.href} className="contact-card" {...(c.external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}>
                  <span className={`contact-card__icon ${c.icon === 'whatsapp' ? 'is-wa' : ''}`}>
                    <Icon name={c.icon} size={22} />
                  </span>
                  <span className="contact-card__title">{c.title}</span>
                  <strong>{c.value}</strong>
                  <span className="contact-card__note">{c.note}</span>
                </a>
              </li>
            ))}
          </ul>

          <div className="contact__layout">
            <div className="contact__form-wrap reveal" id="formulaire">
              <span className="eyebrow">Écrivez-nous</span>
              <h2>Parlez-nous de votre projet</h2>
              <p className="muted">Tous les champs marqués d’un astérisque (*) sont obligatoires.</p>
              <ContactForm subject={subject} />
            </div>

            <aside className="contact__aside reveal reveal-delay-1" aria-label="Informations pratiques">
              <div className="contact__box">
                <h3>Horaires d’ouverture</h3>
                <ul className="hours">
                  {agency.hours.map((h) => (
                    <li key={h.days}>
                      <span>{h.days}</span>
                      <strong>{h.time}</strong>
                    </li>
                  ))}
                </ul>
              </div>
              <div className="contact__box contact__box--dark">
                <h3>Vous souhaitez vendre ou louer votre bien ?</h3>
                <p>Bénéficiez d’une estimation gratuite et d’un plan de commercialisation sur mesure.</p>
                <Link to="/contact?sujet=estimation#formulaire" className="link-arrow">
                  Demander une estimation <Icon name="arrow" size={16} />
                </Link>
              </div>
              <div className="contact__box">
                <h3>Suivez-nous</h3>
                <ul className="socials socials--dark">
                  {agency.socials.map((s) => (
                    <li key={s.name}>
                      <a href={s.href} target="_blank" rel="noopener noreferrer" aria-label={`NOVA IMMOBILIER sur ${s.name}`}>
                        <Icon name={s.icon} size={18} />
                      </a>
                    </li>
                  ))}
                </ul>
              </div>
            </aside>
          </div>
        </div>
      </section>
    </>
  );
}

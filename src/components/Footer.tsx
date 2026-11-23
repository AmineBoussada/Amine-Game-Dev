import { Link } from 'react-router-dom';
import { agency, categories, navigation } from '../data/site';
import { whatsappLink } from '../utils/format';
import { Icon } from './Icon';
import { Logo } from './Logo';

export function Footer() {
  const year = new Date().getFullYear();
  return (
    <footer className="site-footer">
      <div className="container">
        <div className="site-footer__grid">
          <div className="site-footer__brand">
            <Logo light />
            <p>{agency.description}</p>
            <ul className="socials" aria-label="Réseaux sociaux">
              {agency.socials.map((s) => (
                <li key={s.name}>
                  <a href={s.href} target="_blank" rel="noopener noreferrer" aria-label={`NOVA IMMOBILIER sur ${s.name}`}>
                    <Icon name={s.icon} size={18} />
                  </a>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h2 className="site-footer__title">Navigation</h2>
            <ul className="site-footer__links">
              {navigation.map((n) => (
                <li key={n.label}>
                  <Link to={n.to}>{n.label}</Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h2 className="site-footer__title">Nos biens</h2>
            <ul className="site-footer__links">
              {categories.map((c) => (
                <li key={c.type}>
                  <Link to={`/biens?type=${c.type}`}>{c.label}</Link>
                </li>
              ))}
              <li>
                <Link to="/favoris">Mes favoris</Link>
              </li>
            </ul>
          </div>

          <div>
            <h2 className="site-footer__title">Contact</h2>
            <ul className="site-footer__contact">
              <li>
                <Icon name="pin" size={18} />
                <span>
                  {agency.address}
                  <br />
                  {agency.city}
                </span>
              </li>
              <li>
                <Icon name="phone" size={18} />
                <a href={agency.phoneHref}>{agency.phone}</a>
              </li>
              <li>
                <Icon name="whatsapp" size={18} />
                <a href={whatsappLink('Bonjour NOVA IMMOBILIER, je souhaite avoir des informations.')} target="_blank" rel="noopener noreferrer">
                  {agency.mobile}
                </a>
              </li>
              <li>
                <Icon name="mail" size={18} />
                <a href={`mailto:${agency.email}`}>{agency.email}</a>
              </li>
            </ul>
          </div>
        </div>

        <div className="site-footer__bottom">
          <p>© {year} NOVA IMMOBILIER. Tous droits réservés.</p>
          <p className="site-footer__demo">Site de démonstration — biens, prix et coordonnées fictifs.</p>
          <Link to="/politique-de-confidentialite">Politique de confidentialité</Link>
        </div>
      </div>
    </footer>
  );
}

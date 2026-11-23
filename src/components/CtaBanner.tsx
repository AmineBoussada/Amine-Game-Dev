import { Link } from 'react-router-dom';
import { whatsappLink } from '../utils/format';
import { Icon } from './Icon';

export function CtaBanner() {
  return (
    <section className="cta-banner" aria-labelledby="cta-title">
      <div className="container">
        <div className="cta-banner__inner reveal">
          <div>
            <span className="eyebrow">Parlons de votre projet</span>
            <h2 id="cta-title">Vous avez un projet immobilier ?</h2>
            <p>Notre équipe est à votre écoute pour vous accompagner.</p>
          </div>
          <div className="cta-banner__actions">
            <Link to="/contact" className="btn btn--light">
              Contactez-nous <Icon name="arrow" size={18} />
            </Link>
            <a
              href={whatsappLink('Bonjour NOVA IMMOBILIER, j’ai un projet immobilier et je souhaite être accompagné(e).')}
              className="btn btn--whatsapp"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Icon name="whatsapp" size={18} /> WhatsApp
            </a>
          </div>
        </div>
      </div>
    </section>
  );
}

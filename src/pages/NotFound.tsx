import { Link } from 'react-router-dom';
import { Icon } from '../components/Icon';
import { useDocumentMeta } from '../hooks/useDocumentMeta';

export default function NotFound() {
  useDocumentMeta('Page introuvable');
  return (
    <section className="not-found">
      <div className="container">
        <span className="not-found__code serif">404</span>
        <h1>Cette page est introuvable</h1>
        <p className="lead">Le bien ou la page que vous recherchez n’existe plus ou a été déplacé.</p>
        <div className="not-found__actions">
          <Link to="/biens" className="btn btn--primary">
            Voir nos biens <Icon name="arrow" size={18} />
          </Link>
          <Link to="/" className="btn btn--outline">
            Retour à l’accueil
          </Link>
        </div>
      </div>
    </section>
  );
}

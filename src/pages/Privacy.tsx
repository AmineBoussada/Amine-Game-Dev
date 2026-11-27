import { Link } from 'react-router-dom';
import { PageHeader } from '../components/PageHeader';
import { agency } from '../data/site';
import { useDocumentMeta } from '../hooks/useDocumentMeta';

export default function Privacy() {
  useDocumentMeta('Politique de confidentialité', 'Politique de confidentialité et de protection des données personnelles de NOVA IMMOBILIER.');
  return (
    <>
      <PageHeader title="Politique de confidentialité" crumbs={[{ label: 'Politique de confidentialité' }]} />
      <section className="section">
        <div className="container prose">
          <p className="lead">
            NOVA IMMOBILIER attache une grande importance à la protection de vos données personnelles, conformément à la loi
            n° 18-07 du 10 juin 2018 relative à la protection des personnes physiques dans le traitement des données à caractère
            personnel.
          </p>
          <h2>1. Données collectées</h2>
          <p>
            Lorsque vous utilisez nos formulaires, nous collectons uniquement les informations nécessaires au traitement de votre
            demande : nom, téléphone, adresse email, sujet et contenu de votre message, ainsi que la référence du bien concerné.
          </p>
          <h2>2. Finalités</h2>
          <p>
            Ces données sont utilisées exclusivement pour vous recontacter, organiser des visites et vous accompagner dans votre
            projet immobilier. Elles ne sont jamais revendues à des tiers.
          </p>
          <h2>3. Durée de conservation</h2>
          <p>Vos données sont conservées pendant une durée maximale de trois ans à compter de notre dernier échange.</p>
          <h2>4. Favoris</h2>
          <p>
            La liste de vos biens favoris est enregistrée uniquement dans le stockage local de votre navigateur. Elle n’est pas
            transmise à nos serveurs.
          </p>
          <h2>5. Vos droits</h2>
          <p>
            Vous disposez d’un droit d’accès, de rectification et de suppression de vos données. Pour l’exercer, écrivez-nous à{' '}
            <a href={`mailto:${agency.email}`}>{agency.email}</a> ou via notre <Link to="/contact">formulaire de contact</Link>.
          </p>
          <p className="muted">
            <em>Ce site est une démonstration : aucune donnée saisie dans les formulaires n’est transmise ni enregistrée.</em>
          </p>
        </div>
      </section>
    </>
  );
}

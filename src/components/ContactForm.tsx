import { useEffect, useId, useRef, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { Icon } from './Icon';

export const subjects = [
  'Achat d’un bien',
  'Location d’un bien',
  'Demande de visite',
  'Vendre ou louer mon bien',
  'Estimation de mon bien',
  'Recherche personnalisée',
  'Autre demande',
] as const;

interface Values {
  name: string;
  phone: string;
  email: string;
  subject: string;
  message: string;
  consent: boolean;
}

type Errors = Partial<Record<keyof Values, string>>;

interface Props {
  /** Pre-selected subject (e.g. "Demande de visite"). Changing it updates the form. */
  subject?: string;
  defaultMessage?: string;
  /** Property reference, sent along with the request. */
  propertyRef?: string;
  compact?: boolean;
  submitLabel?: string;
}

const phoneRe = /^(?:(?:\+|00)213|0)(?:[5-7]\d{8}|[2-4]\d{7})$/;
const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export function validate(v: Values): Errors {
  const e: Errors = {};
  if (v.name.trim().length < 3) e.name = 'Veuillez indiquer votre nom complet.';
  if (!phoneRe.test(v.phone.replace(/[\s.-]/g, '')))
    e.phone = 'Numéro invalide. Exemple : 0550 12 34 56 ou +213 550 12 34 56.';
  if (!emailRe.test(v.email.trim())) e.email = 'Veuillez saisir une adresse email valide.';
  if (!v.subject) e.subject = 'Veuillez choisir un sujet.';
  if (v.message.trim().length < 10) e.message = 'Votre message doit contenir au moins 10 caractères.';
  if (!v.consent) e.consent = 'Votre accord est nécessaire pour être recontacté(e).';
  return e;
}

/**
 * Contact form with client-side validation and a simulated submission.
 * WordPress phase: POST the same payload to Contact Form 7 / Gravity Forms REST endpoint.
 */
export function ContactForm({ subject = '', defaultMessage = '', propertyRef, compact = false, submitLabel = 'Envoyer le message' }: Props) {
  const uid = useId();
  const [values, setValues] = useState<Values>({ name: '', phone: '', email: '', subject, message: defaultMessage, consent: false });
  const [errors, setErrors] = useState<Errors>({});
  const [touched, setTouched] = useState<Partial<Record<keyof Values, boolean>>>({});
  const [status, setStatus] = useState<'idle' | 'sending' | 'sent'>('idle');
  const formRef = useRef<HTMLFormElement>(null);

  useEffect(() => {
    setValues((v) => ({ ...v, subject: subject || v.subject }));
  }, [subject]);

  const set = <K extends keyof Values>(key: K, value: Values[K]) => {
    const next = { ...values, [key]: value };
    setValues(next);
    if (touched[key]) setErrors(validate(next));
  };

  const blur = (key: keyof Values) => {
    setTouched((t) => ({ ...t, [key]: true }));
    setErrors(validate(values));
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const errs = validate(values);
    setErrors(errs);
    setTouched({ name: true, phone: true, email: true, subject: true, message: true, consent: true });
    if (Object.keys(errs).length) {
      const first = formRef.current?.querySelector<HTMLElement>('[aria-invalid="true"]');
      first?.focus();
      return;
    }
    setStatus('sending');
    // Simulated network request — no backend in this phase.
    window.setTimeout(() => setStatus('sent'), 1100);
  };

  const showError = (k: keyof Values) => (touched[k] && errors[k] ? errors[k] : undefined);
  const fieldProps = (k: keyof Values) => ({
    id: `${uid}-${k}`,
    'aria-invalid': !!showError(k),
    'aria-describedby': showError(k) ? `${uid}-${k}-err` : undefined,
    onBlur: () => blur(k),
  });

  if (status === 'sent') {
    return (
      <div className="form-success" role="status">
        <span className="form-success__icon">
          <Icon name="check" size={28} />
        </span>
        <h3>Merci {values.name.split(' ')[0]}, votre message a bien été envoyé.</h3>
        <p>
          Un conseiller NOVA IMMOBILIER vous recontactera sous 24 h ouvrées au <strong>{values.phone}</strong>
          {propertyRef && (
            <>
              {' '}au sujet du bien <strong>{propertyRef}</strong>
            </>
          )}
          .
        </p>
        <p className="form-success__demo">Démonstration : aucun message n’a été réellement transmis.</p>
        <button
          type="button"
          className="btn btn--outline btn--sm"
          onClick={() => {
            setValues({ name: '', phone: '', email: '', subject, message: defaultMessage, consent: false });
            setTouched({});
            setErrors({});
            setStatus('idle');
          }}
        >
          Envoyer un autre message
        </button>
      </div>
    );
  }

  return (
    <form ref={formRef} className={`contact-form ${compact ? 'contact-form--compact' : ''}`} onSubmit={submit} noValidate>
      {propertyRef && <input type="hidden" name="reference" value={propertyRef} />}
      <div className="contact-form__grid">
        <div className={`field ${showError('name') ? 'has-error' : ''}`}>
          <label className="field__label" htmlFor={`${uid}-name`}>
            Nom complet *
          </label>
          <input type="text" autoComplete="name" value={values.name} onChange={(e) => set('name', e.target.value)} {...fieldProps('name')} />
          {showError('name') && <span className="field__error" id={`${uid}-name-err`}>{errors.name}</span>}
        </div>

        <div className={`field ${showError('phone') ? 'has-error' : ''}`}>
          <label className="field__label" htmlFor={`${uid}-phone`}>
            Téléphone *
          </label>
          <input
            type="tel"
            inputMode="tel"
            autoComplete="tel"
            placeholder="0550 12 34 56"
            value={values.phone}
            onChange={(e) => set('phone', e.target.value)}
            {...fieldProps('phone')}
          />
          {showError('phone') && <span className="field__error" id={`${uid}-phone-err`}>{errors.phone}</span>}
        </div>

        <div className={`field ${showError('email') ? 'has-error' : ''}`}>
          <label className="field__label" htmlFor={`${uid}-email`}>
            Email *
          </label>
          <input
            type="email"
            inputMode="email"
            autoComplete="email"
            placeholder="vous@exemple.com"
            value={values.email}
            onChange={(e) => set('email', e.target.value)}
            {...fieldProps('email')}
          />
          {showError('email') && <span className="field__error" id={`${uid}-email-err`}>{errors.email}</span>}
        </div>

        <div className={`field ${showError('subject') ? 'has-error' : ''}`}>
          <label className="field__label" htmlFor={`${uid}-subject`}>
            Sujet *
          </label>
          <select value={values.subject} onChange={(e) => set('subject', e.target.value)} {...fieldProps('subject')}>
            <option value="">Choisir un sujet</option>
            {subjects.map((s) => (
              <option key={s} value={s}>
                {s}
              </option>
            ))}
          </select>
          {showError('subject') && <span className="field__error" id={`${uid}-subject-err`}>{errors.subject}</span>}
        </div>

        <div className={`field field--full ${showError('message') ? 'has-error' : ''}`}>
          <label className="field__label" htmlFor={`${uid}-message`}>
            Message *
          </label>
          <textarea
            rows={compact ? 4 : 6}
            value={values.message}
            placeholder="Décrivez votre projet, vos disponibilités pour une visite…"
            onChange={(e) => set('message', e.target.value)}
            {...fieldProps('message')}
          />
          {showError('message') && <span className="field__error" id={`${uid}-message-err`}>{errors.message}</span>}
        </div>

        <div className={`field field--full ${showError('consent') ? 'has-error' : ''}`}>
          <label className="checkbox" htmlFor={`${uid}-consent`}>
            <input type="checkbox" checked={values.consent} onChange={(e) => set('consent', e.target.checked)} {...fieldProps('consent')} />
            <span>
              J’accepte que NOVA IMMOBILIER utilise mes coordonnées pour me recontacter au sujet de ma demande. Voir la{' '}
              <Link to="/politique-de-confidentialite">politique de confidentialité</Link>
              .
            </span>
          </label>
          {showError('consent') && <span className="field__error" id={`${uid}-consent-err`}>{errors.consent}</span>}
        </div>
      </div>

      <button type="submit" className="btn btn--primary contact-form__submit" disabled={status === 'sending'}>
        {status === 'sending' ? (
          <>
            <span className="spinner" aria-hidden="true" /> Envoi en cours…
          </>
        ) : (
          <>
            {submitLabel} <Icon name="arrow" size={18} />
          </>
        )}
      </button>
    </form>
  );
}

import { useLocation } from 'react-router-dom';
import { whatsappLink } from '../utils/format';
import { Icon } from './Icon';

export function WhatsappFloat() {
  const { pathname } = useLocation();
  // The property page has its own sticky WhatsApp CTA on mobile.
  if (pathname.startsWith('/biens/')) return null;
  return (
    <a
      className="wa-float"
      href={whatsappLink('Bonjour NOVA IMMOBILIER, je souhaite avoir des informations.')}
      target="_blank"
      rel="noopener noreferrer"
      aria-label="Discuter avec nous sur WhatsApp"
    >
      <Icon name="whatsapp" size={26} />
    </a>
  );
}

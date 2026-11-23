import { useEffect, useState } from 'react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import { agency, navigation } from '../data/site';
import { useFavorites } from '../hooks/useFavorites';
import { Icon } from './Icon';
import { Logo } from './Logo';

export function Header() {
  const { pathname, search } = useLocation();
  const { favorites } = useFavorites();
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);
  const overHero = pathname === '/';
  const solid = !overHero || scrolled || open;

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 40);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  // Close the mobile menu on navigation.
  useEffect(() => setOpen(false), [pathname, search]);

  useEffect(() => {
    document.body.classList.toggle('no-scroll', open);
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open]);

  /** Query-string aware active state for Acheter / Louer / Nos biens. */
  const isActive = (to: string) => {
    const [path, qs = ''] = to.split('?');
    if (path !== pathname) return false;
    if (path !== '/biens') return true;
    const current = new URLSearchParams(search).get('transaction') ?? '';
    const target = new URLSearchParams(qs).get('transaction') ?? '';
    return current === target;
  };

  return (
    <>
      <header className={`site-header ${solid ? 'is-solid' : 'is-transparent'}`}>
        <div className="container site-header__inner">
          <Logo light={!solid} />
  
          <nav className="main-nav" aria-label="Navigation principale">
            <ul>
              {navigation.map((item) => (
                <li key={item.label}>
                  <NavLink to={item.to} className={() => `main-nav__link ${isActive(item.to) ? 'is-active' : ''}`}>
                    {item.label}
                  </NavLink>
                </li>
              ))}
            </ul>
          </nav>
  
          <div className="site-header__actions">
            <Link to="/favoris" className="icon-btn header-fav" aria-label={`Mes favoris (${favorites.length})`}>
              <Icon name="heart" />
              {favorites.length > 0 && <span className="header-fav__count">{favorites.length}</span>}
            </Link>
            <a href={agency.phoneHref} className="header-phone">
              <Icon name="phone" size={16} />
              <span>{agency.phone}</span>
            </a>
            <Link to="/contact" className="btn btn--primary btn--sm header-cta">
              Nous contacter
            </Link>
            <button
              type="button"
              className="icon-btn menu-toggle"
              aria-expanded={open}
              aria-controls="mobile-nav"
              aria-label={open ? 'Fermer le menu' : 'Ouvrir le menu'}
              onClick={() => setOpen((o) => !o)}
            >
              <Icon name={open ? 'close' : 'menu'} size={22} />
            </button>
          </div>
        </div>
      </header>

      <div id="mobile-nav" className={`mobile-nav ${open ? 'is-open' : ''}`} aria-hidden={!open}>
        <nav aria-label="Navigation mobile">
          <ul>
            {navigation.map((item, i) => (
              <li key={item.label} style={{ transitionDelay: open ? `${60 + i * 40}ms` : '0ms' }}>
                <Link to={item.to} className={isActive(item.to) ? 'is-active' : ''} tabIndex={open ? 0 : -1}>
                  {item.label}
                  <Icon name="arrow" size={18} />
                </Link>
              </li>
            ))}
            <li style={{ transitionDelay: open ? '300ms' : '0ms' }}>
              <Link to="/favoris" tabIndex={open ? 0 : -1}>
                Mes favoris {favorites.length > 0 && `(${favorites.length})`}
                <Icon name="heart" size={18} />
              </Link>
            </li>
          </ul>
        </nav>
        <div className="mobile-nav__footer">
          <Link to="/contact" className="btn btn--primary btn--block" tabIndex={open ? 0 : -1}>
            Nous contacter
          </Link>
          <div className="mobile-nav__contacts">
            <a href={agency.phoneHref} tabIndex={open ? 0 : -1}>
              <Icon name="phone" size={16} /> {agency.phone}
            </a>
            <a href={`mailto:${agency.email}`} tabIndex={open ? 0 : -1}>
              <Icon name="mail" size={16} /> {agency.email}
            </a>
          </div>
        </div>
      </div>
    </>
  );
}

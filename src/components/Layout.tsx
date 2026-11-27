import { Suspense } from 'react';
import { Outlet } from 'react-router-dom';
import { useReveal } from '../hooks/useReveal';
import { Footer } from './Footer';
import { Header } from './Header';
import { ScrollToTop } from './ScrollToTop';
import { WhatsappFloat } from './WhatsappFloat';

export function Layout() {
  useReveal();
  return (
    <>
      <a href="#main" className="skip-link">
        Aller au contenu
      </a>
      <ScrollToTop />
      <Header />
      <main id="main">
        <Suspense fallback={<div className="page-loading" aria-busy="true" aria-label="Chargement" />}>
          <Outlet />
        </Suspense>
      </main>
      <Footer />
      <WhatsappFloat />
    </>
  );
}

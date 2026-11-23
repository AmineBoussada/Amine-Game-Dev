import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, HashRouter } from 'react-router-dom';
import App from './App';
import { FavoritesProvider } from './hooks/useFavorites';
import './styles/tokens.css';
import './styles/base.css';
import './styles/components.css';
import './styles/pages.css';

// Hash routing for static hosts that cannot rewrite URLs (artifact preview build).
const Router = import.meta.env.VITE_HASH_ROUTER === 'true' ? HashRouter : BrowserRouter;

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <Router>
      <FavoritesProvider>
        <App />
      </FavoritesProvider>
    </Router>
  </StrictMode>,
);

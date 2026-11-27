import { lazy } from 'react';
import { Route, Routes } from 'react-router-dom';
import { Layout } from './components/Layout';
import Home from './pages/Home';

// Secondary pages are code-split to keep the homepage fast.
const Listings = lazy(() => import('./pages/Listings'));
const PropertyDetail = lazy(() => import('./pages/PropertyDetail'));
const Contact = lazy(() => import('./pages/Contact'));
const About = lazy(() => import('./pages/About'));
const Favorites = lazy(() => import('./pages/Favorites'));
const Privacy = lazy(() => import('./pages/Privacy'));
const NotFound = lazy(() => import('./pages/NotFound'));

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<Home />} />
        <Route path="biens" element={<Listings />} />
        <Route path="biens/:slug" element={<PropertyDetail />} />
        <Route path="a-propos" element={<About />} />
        <Route path="contact" element={<Contact />} />
        <Route path="favoris" element={<Favorites />} />
        <Route path="politique-de-confidentialite" element={<Privacy />} />
        <Route path="*" element={<NotFound />} />
      </Route>
    </Routes>
  );
}

/**
 * NOVA IMMOBILIER — WordPress theme behaviour (vanilla TS, built to assets/js/theme.js).
 * Ports the React demo interactions: header, mobile menu, reveal, favorites, search,
 * filters, gallery + lightbox, contact form validation, share, illustrations.
 */
import { illustration, type Scene } from '../src/utils/illustrations';

const $ = <T extends Element = HTMLElement>(sel: string, root: ParentNode = document) => root.querySelector<T>(sel);
const $$ = <T extends Element = HTMLElement>(sel: string, root: ParentNode = document) => Array.from(root.querySelectorAll<T>(sel));
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* Visitor-facing messages. A layout can translate them with window.NOVA_I18N (same keys). */
const L = {
  menuOpen: 'Ouvrir le menu',
  menuClose: 'Fermer le menu',
  favAdd: 'Ajouter aux favoris',
  favRemove: 'Retirer des favoris',
  errName: 'Veuillez indiquer votre nom complet.',
  errPhone: 'Numéro invalide. Exemple : 0550 12 34 56 ou +213 550 12 34 56.',
  errEmail: 'Veuillez saisir une adresse email valide.',
  errSubject: 'Veuillez choisir un sujet.',
  errMessage: 'Votre message doit contenir au moins 10 caractères.',
  errConsent: 'Votre accord est nécessaire pour être recontacté(e).',
  sending: 'Envoi en cours…',
  ...((window as unknown as { NOVA_I18N?: Record<string, string> }).NOVA_I18N ?? {}),
};

/* ---------- Illustrations for properties without photos ---------- */
function fillIllustrations(root: ParentNode = document) {
  $$<HTMLImageElement>('img[data-scene]', root).forEach((img) => {
    img.src = illustration(img.dataset.scene as Scene, img.dataset.seed ?? '');
    img.removeAttribute('data-scene');
  });
}

/* ---------- Header + mobile menu ---------- */
function initHeader() {
  const header = $('[data-header]');
  if (!header) return;
  const overHero = header.hasAttribute('data-over-hero');
  const nav = $('[data-mobile-nav]');
  const toggle = $<HTMLButtonElement>('[data-menu-toggle]');
  let open = false;

  const paint = () => {
    const solid = !overHero || open || window.scrollY > 40;
    header.classList.toggle('is-solid', solid);
    header.classList.toggle('is-transparent', !solid);
    if (overHero) {
      $('[data-logo-dark]', header)!.hidden = !solid;
      $('[data-logo-light]', header)!.hidden = solid;
    }
  };
  const setOpen = (value: boolean) => {
    open = value;
    nav?.classList.toggle('is-open', open);
    nav?.setAttribute('aria-hidden', String(!open));
    $$('a', nav ?? document).forEach((a) => (a.tabIndex = open ? 0 : -1));
    toggle?.setAttribute('aria-expanded', String(open));
    toggle?.setAttribute('aria-label', open ? L.menuClose : L.menuOpen);
    $('[data-icon-open]', toggle!)!.hidden = open;
    $('[data-icon-close]', toggle!)!.hidden = !open;
    document.body.classList.toggle('no-scroll', open);
    paint();
  };
  toggle?.addEventListener('click', () => setOpen(!open));
  window.addEventListener('keydown', (e) => e.key === 'Escape' && open && setOpen(false));
  window.addEventListener('scroll', paint, { passive: true });
  paint();
}

/* ---------- Reveal on scroll ---------- */
function initReveal() {
  const els = $$('.reveal');
  if (reduceMotion || !('IntersectionObserver' in window)) {
    els.forEach((el) => el.classList.add('is-visible'));
    return;
  }
  const io = new IntersectionObserver(
    (entries) =>
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add('is-visible');
          io.unobserve(e.target);
        }
      }),
    { rootMargin: '0px 0px -6% 0px', threshold: 0.04 },
  );
  els.forEach((el) => io.observe(el));
}

/* ---------- Favorites (localStorage + cookie for the server-rendered page) ---------- */
const FAV_KEY = 'nova:favorites';
function readFavs(): string[] {
  try {
    const v = JSON.parse(localStorage.getItem(FAV_KEY) || '[]');
    if (Array.isArray(v)) return v.map(String);
  } catch {
    /* storage unavailable */
  }
  const m = document.cookie.match(/(?:^|; )nova_favs=([^;]*)/);
  return m ? decodeURIComponent(m[1]).split(',').filter(Boolean) : [];
}
function writeFavs(ids: string[]) {
  try {
    localStorage.setItem(FAV_KEY, JSON.stringify(ids));
  } catch {
    /* ignore */
  }
  document.cookie = `nova_favs=${encodeURIComponent(ids.join(','))}; path=/; max-age=31536000; SameSite=Lax`;
}
function initFavorites() {
  let favs = readFavs();
  writeFavs(favs); // keep the cookie in sync with localStorage
  const paint = () => {
    $$<HTMLButtonElement>('[data-fav]').forEach((btn) => {
      const on = favs.includes(btn.dataset.fav!);
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-pressed', String(on));
      btn.title = on ? L.favRemove : L.favAdd;
    });
    $$('[data-fav-count]').forEach((el) => {
      el.textContent = String(favs.length);
      el.hidden = favs.length === 0;
    });
  };
  document.addEventListener('click', (e) => {
    const btn = (e.target as Element).closest<HTMLButtonElement>('[data-fav]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const id = btn.dataset.fav!;
    favs = favs.includes(id) ? favs.filter((x) => x !== id) : [...favs, id];
    writeFavs(favs);
    paint();
    const grid = btn.closest('[data-favorites-grid]');
    if (grid && !favs.includes(id)) {
      btn.closest('.property-card')?.remove();
      if (!grid.children.length) window.location.reload();
    }
  });
  paint();
}

/* ---------- Helpers for GET forms: drop empty params for clean URLs ---------- */
function submitClean(form: HTMLFormElement) {
  const fields = $$<HTMLInputElement | HTMLSelectElement>('[name]', form).concat(
    $$<HTMLSelectElement>(`[form="${form.id}"]`),
  );
  fields.forEach((f) => {
    if (!f.value) f.disabled = true;
  });
  form.submit();
}

/* ---------- Homepage search ---------- */
function initSearchBar() {
  const form = $<HTMLFormElement>('[data-search-bar]');
  if (!form) return;
  const tabs = $$<HTMLInputElement>('input[name="transaction"]', form);
  const budgets = $$<HTMLSelectElement>('[data-budget]', form);
  const error = $('[data-budget-error]', form)!;
  const setTx = () => {
    const tx = tabs.find((t) => t.checked)?.value ?? 'vente';
    tabs.forEach((t) => t.parentElement!.classList.toggle('is-active', t.checked));
    budgets.forEach((sel) => {
      sel.value = '';
      $$<HTMLOptionElement>('option[data-tx]', sel).forEach((o) => {
        o.hidden = o.disabled = o.dataset.tx !== tx;
      });
    });
    error.hidden = true;
  };
  tabs.forEach((t) => t.addEventListener('change', setTx));
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const [min, max] = budgets.map((s) => Number(s.value));
    if (min && max && min > max) {
      error.hidden = false;
      return;
    }
    submitClean(form);
  });
  setTx();
}

/* ---------- Listing filters ---------- */
function initFilters() {
  const form = $<HTMLFormElement>('[data-filters]');
  if (!form) return;
  form.addEventListener('change', (e) => {
    const target = e.target as HTMLInputElement;
    if (target.hasAttribute('data-reset-budget')) {
      $$<HTMLSelectElement>('select[name="prix_min"], select[name="prix_max"]', form).forEach((s) => (s.value = ''));
    }
    $$<HTMLInputElement>('input[type=radio]', form).forEach((r) => r.parentElement!.classList.toggle('is-active', r.checked));
    submitClean(form);
  });
  $$<HTMLSelectElement>('[data-autosubmit]').forEach((s) => s.addEventListener('change', () => submitClean(form)));

  const drawer = $('[data-filter-drawer]');
  const backdrop = $('.listings__backdrop');
  const setOpen = (open: boolean) => {
    drawer?.classList.toggle('is-open', open);
    if (backdrop) backdrop.hidden = !open;
    document.body.classList.toggle('no-scroll', open);
  };
  $$('[data-filter-open]').forEach((b) => b.addEventListener('click', () => setOpen(true)));
  $$('[data-filter-close]').forEach((b) => b.addEventListener('click', () => setOpen(false)));
  window.addEventListener('keydown', (e) => e.key === 'Escape' && setOpen(false));
}

/* ---------- Gallery + lightbox ---------- */
function initGallery() {
  const root = $('[data-gallery]');
  if (!root) return;
  const slides = $$('[data-slide]', root);
  const thumbs = $$<HTMLButtonElement>('[data-gallery-thumb]', root);
  const lightbox = $('[data-lightbox]', root)!;
  const lbImg = $<HTMLImageElement>('[data-lightbox-img]', root)!;
  const lbCaption = $('[data-lightbox-caption]', root)!;
  const count = slides.length;
  let index = 0;
  let touchX: number | null = null;

  const show = (i: number) => {
    index = (i + count) % count;
    slides.forEach((s, n) => (s.hidden = n !== index));
    thumbs.forEach((t, n) => {
      t.classList.toggle('is-active', n === index);
      t.setAttribute('aria-selected', String(n === index));
    });
    // Scroll only the thumbnail strip (scrollIntoView would also move the page).
    const thumb = thumbs[index];
    const strip = thumb?.parentElement;
    if (thumb && strip) {
      strip.scrollTo({ left: thumb.offsetLeft - strip.offsetLeft - (strip.clientWidth - thumb.clientWidth) / 2, behavior: reduceMotion ? 'auto' : 'smooth' });
    }
    $$('[data-gallery-index]', root).forEach((el) => (el.textContent = String(index + 1)));
    const img = $<HTMLImageElement>('img', slides[index]);
    if (img) {
      img.loading = 'eager';
      lbImg.src = img.currentSrc || img.src;
      lbImg.alt = img.alt;
      lbCaption.textContent = img.alt;
    }
  };
  const setLightbox = (open: boolean) => {
    lightbox.hidden = !open;
    document.body.classList.toggle('no-scroll', open);
    if (open) $<HTMLButtonElement>('[data-lightbox-close]', lightbox)?.focus();
  };

  $$('[data-gallery-prev]', root).forEach((b) => b.addEventListener('click', () => show(index - 1)));
  $$('[data-gallery-next]', root).forEach((b) => b.addEventListener('click', () => show(index + 1)));
  thumbs.forEach((t) => t.addEventListener('click', () => show(Number(t.dataset.galleryThumb))));
  $$('[data-gallery-open]', root).forEach((b) => b.addEventListener('click', () => setLightbox(true)));
  $('[data-lightbox-close]', root)?.addEventListener('click', () => setLightbox(false));
  lightbox.addEventListener('click', (e) => e.target === lightbox && setLightbox(false));
  window.addEventListener('keydown', (e) => {
    if (lightbox.hidden) return;
    if (e.key === 'Escape') setLightbox(false);
    if (e.key === 'ArrowRight') show(index + 1);
    if (e.key === 'ArrowLeft') show(index - 1);
  });
  [root.querySelector('.gallery__main'), lightbox].forEach((el) => {
    el?.addEventListener('touchstart', (e) => (touchX = (e as TouchEvent).touches[0].clientX), { passive: true });
    el?.addEventListener('touchend', (e) => {
      if (touchX === null) return;
      const dx = (e as TouchEvent).changedTouches[0].clientX - touchX;
      if (Math.abs(dx) > 40) show(index + (dx < 0 ? 1 : -1));
      touchX = null;
    });
  });
  show(0);
}

/* ---------- Contact form validation ---------- */
const phoneRe = /^(?:(?:\+|00)213|0)(?:[5-7]\d{8}|[2-4]\d{7})$/;
const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
function initContactForms() {
  $$<HTMLFormElement>('[data-contact-form]').forEach((form) => {
    const get = (name: string) => form.elements.namedItem(name) as HTMLInputElement | null;
    const rules: Record<string, () => string> = {
      name: () => (get('name')!.value.trim().length < 3 ? L.errName : ''),
      phone: () => (!phoneRe.test(get('phone')!.value.replace(/[\s.-]/g, '')) ? L.errPhone : ''),
      email: () => (!emailRe.test(get('email')!.value.trim()) ? L.errEmail : ''),
      subject: () => (!get('subject')!.value ? L.errSubject : ''),
      message: () => (get('message')!.value.trim().length < 10 ? L.errMessage : ''),
      consent: () => (!get('consent')!.checked ? L.errConsent : ''),
    };
    const touched = new Set<string>();
    const check = (name: string) => {
      const msg = rules[name]();
      const wrap = $(`[data-field="${name}"]`, form)!;
      const input = get(name)!;
      const err = $('.field__error', wrap)!;
      const show = touched.has(name) && !!msg;
      wrap.classList.toggle('has-error', show);
      input.setAttribute('aria-invalid', String(show));
      if (show) input.setAttribute('aria-describedby', err.id);
      else input.removeAttribute('aria-describedby');
      err.textContent = msg;
      err.hidden = !show;
      return !msg;
    };
    Object.keys(rules).forEach((name) => {
      const input = get(name);
      input?.addEventListener('blur', () => {
        touched.add(name);
        check(name);
      });
      input?.addEventListener(input.type === 'checkbox' || input.tagName === 'SELECT' ? 'change' : 'input', () => touched.has(name) && check(name));
    });
    form.addEventListener('submit', (e) => {
      Object.keys(rules).forEach((n) => touched.add(n));
      const ok = Object.keys(rules).map(check).every(Boolean);
      if (!ok) {
        e.preventDefault();
        $<HTMLElement>('[aria-invalid="true"]', form)?.focus();
        return;
      }
      const btn = $<HTMLButtonElement>('button[type=submit]', form)!;
      btn.disabled = true;
      $('[data-submit-label]', btn)!.innerHTML = `<span class="spinner" aria-hidden="true"></span> ${L.sending}`;
    });
  });
}

/* ---------- Property page actions ---------- */
function initPropertyActions() {
  $$('[data-request-visit]').forEach((btn) =>
    btn.addEventListener('click', () => {
      const select = $<HTMLSelectElement>('#formulaire [data-subject]');
      if (select) {
        select.value = 'Demande de visite';
        select.dispatchEvent(new Event('change'));
      }
      $('#formulaire')?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
      window.setTimeout(() => $<HTMLInputElement>('#formulaire input:not([type=hidden])')?.focus({ preventScroll: true }), 600);
    }),
  );
  const share = $('[data-share]');
  share?.addEventListener('click', async () => {
    const url = window.location.href;
    try {
      if (navigator.share) await navigator.share({ title: document.title, url });
      else {
        await navigator.clipboard.writeText(url);
        const toast = $('[data-share-toast]')!;
        toast.hidden = false;
        window.setTimeout(() => (toast.hidden = true), 2200);
      }
    } catch {
      /* cancelled */
    }
  });
}

/* ---------- Light / dark display (remembered on this device) ---------- */
function initThemeToggle() {
  const root = document.documentElement;
  const paint = () =>
    $$('[data-theme-toggle]').forEach((b) => b.setAttribute('aria-pressed', String(root.dataset.theme === 'sombre')));
  $$('[data-theme-toggle]').forEach((btn) =>
    btn.addEventListener('click', () => {
      const dark = root.dataset.theme !== 'sombre';
      if (dark) root.setAttribute('data-theme', 'sombre');
      else root.removeAttribute('data-theme');
      const value = dark ? 'sombre' : 'clair';
      try {
        localStorage.setItem('nova_theme', value);
      } catch {
        /* storage unavailable */
      }
      document.cookie = `nova_theme=${value}; path=/; max-age=31536000; SameSite=Lax`;
      paint();
    }),
  );
  paint();
}

fillIllustrations();
initThemeToggle();
initHeader();
initReveal();
initFavorites();
initSearchBar();
initFilters();
initGallery();
initContactForms();
initPropertyActions();

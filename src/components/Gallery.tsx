import { useCallback, useEffect, useRef, useState, type TouchEvent } from 'react';
import type { PropertyImage } from '../types/property';
import { Icon } from './Icon';
import { SmartImage } from './SmartImage';

/** Property gallery: main image, thumbnails, keyboard/swipe navigation and fullscreen lightbox. */
export function Gallery({ images, title }: { images: PropertyImage[]; title: string }) {
  const [index, setIndex] = useState(0);
  const [lightbox, setLightbox] = useState(false);
  const touchX = useRef<number | null>(null);
  const thumbsRef = useRef<HTMLDivElement>(null);
  const closeRef = useRef<HTMLButtonElement>(null);
  const count = images.length;

  const go = useCallback((delta: number) => setIndex((i) => (i + delta + count) % count), [count]);

  useEffect(() => {
    const thumb = thumbsRef.current?.children[index] as HTMLElement | undefined;
    thumb?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
  }, [index]);

  useEffect(() => {
    if (!lightbox) return;
    document.body.classList.add('no-scroll');
    closeRef.current?.focus();
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setLightbox(false);
      if (e.key === 'ArrowRight') go(1);
      if (e.key === 'ArrowLeft') go(-1);
    };
    window.addEventListener('keydown', onKey);
    return () => {
      document.body.classList.remove('no-scroll');
      window.removeEventListener('keydown', onKey);
    };
  }, [lightbox, go]);

  const onTouchStart = (e: TouchEvent) => (touchX.current = e.touches[0].clientX);
  const onTouchEnd = (e: TouchEvent) => {
    if (touchX.current === null) return;
    const dx = e.changedTouches[0].clientX - touchX.current;
    if (Math.abs(dx) > 40) go(dx < 0 ? 1 : -1);
    touchX.current = null;
  };

  const current = images[index];

  return (
    <div className="gallery">
      <div className="gallery__main" onTouchStart={onTouchStart} onTouchEnd={onTouchEnd}>
        <button
          type="button"
          className="gallery__open"
          onClick={() => setLightbox(true)}
          aria-label={`Agrandir la photo ${index + 1} sur ${count}`}
        >
          <SmartImage src={current.src} alt={current.alt} priority={index === 0} frameClassName="ratio-3-2" />
        </button>
        {count > 1 && (
          <>
            <button type="button" className="gallery__nav gallery__nav--prev" onClick={() => go(-1)} aria-label="Photo précédente">
              <Icon name="chevronLeft" size={22} />
            </button>
            <button type="button" className="gallery__nav gallery__nav--next" onClick={() => go(1)} aria-label="Photo suivante">
              <Icon name="chevronRight" size={22} />
            </button>
          </>
        )}
        <span className="gallery__counter" aria-live="polite">
          {index + 1} / {count}
        </span>
        <button type="button" className="gallery__expand" onClick={() => setLightbox(true)} aria-label="Afficher en plein écran">
          <Icon name="expand" size={18} />
        </button>
      </div>

      {count > 1 && (
        <div className="gallery__thumbs" ref={thumbsRef} role="tablist" aria-label={`Photos — ${title}`}>
          {images.map((img, i) => (
            <button
              key={img.src}
              type="button"
              role="tab"
              aria-selected={i === index}
              aria-label={`Photo ${i + 1} : ${img.alt}`}
              className={`gallery__thumb ${i === index ? 'is-active' : ''}`}
              onClick={() => setIndex(i)}
            >
              <SmartImage src={img.src.replace('w=1600', 'w=300')} alt="" frameClassName="ratio-4-3" />
            </button>
          ))}
        </div>
      )}

      {lightbox && (
        <div
          className="lightbox"
          role="dialog"
          aria-modal="true"
          aria-label={`Galerie photos — ${title}`}
          onTouchStart={onTouchStart}
          onTouchEnd={onTouchEnd}
          onClick={(e) => e.target === e.currentTarget && setLightbox(false)}
        >
          <button ref={closeRef} type="button" className="lightbox__close" onClick={() => setLightbox(false)} aria-label="Fermer la galerie">
            <Icon name="close" size={24} />
          </button>
          <figure className="lightbox__figure">
            <SmartImage src={current.src.replace('w=1600', 'w=2200')} alt={current.alt} priority frameClassName="lightbox__img" />
            <figcaption>
              {current.alt} <span>· {index + 1} / {count}</span>
            </figcaption>
          </figure>
          {count > 1 && (
            <>
              <button type="button" className="lightbox__nav lightbox__nav--prev" onClick={() => go(-1)} aria-label="Photo précédente">
                <Icon name="chevronLeft" size={28} />
              </button>
              <button type="button" className="lightbox__nav lightbox__nav--next" onClick={() => go(1)} aria-label="Photo suivante">
                <Icon name="chevronRight" size={28} />
              </button>
            </>
          )}
        </div>
      )}
    </div>
  );
}

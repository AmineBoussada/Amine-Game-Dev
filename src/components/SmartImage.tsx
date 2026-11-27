import { useState, type ImgHTMLAttributes } from 'react';
import { illustration, type Scene } from '../utils/illustrations';
import { Icon } from './Icon';

/** When true (static preview build), skip remote photos and show illustrations directly. */
const ILLUSTRATIONS_ONLY = import.meta.env.VITE_ILLUSTRATIONS_ONLY === 'true';

interface Props extends ImgHTMLAttributes<HTMLImageElement> {
  src: string;
  alt: string;
  /** Default visual used when the photo cannot be loaded. */
  scene?: Scene;
  /** Eager-load above-the-fold images (hero, gallery main). */
  priority?: boolean;
  /** Optional class for the wrapper. */
  frameClassName?: string;
}

/**
 * Image with lazy loading, a soft loading state and a graceful fallback:
 * remote photo → generated illustration of the same scene → branded placeholder.
 */
export function SmartImage({ src, alt, scene, priority, frameClassName = '', className = '', ...rest }: Props) {
  // Seed ignores the requested width so thumbnails match the large image.
  const fallback = scene ? illustration(scene, src.replace(/([?&])w=\d+/, '$1')) : undefined;
  const [state, setState] = useState<'loading' | 'loaded' | 'fallback' | 'error'>(
    ILLUSTRATIONS_ONLY && fallback ? 'fallback' : 'loading',
  );

  if (state === 'error' || (state === 'fallback' && !fallback)) {
    return (
      <div className={`img-frame is-error ${frameClassName}`}>
        <div className="img-fallback" role="img" aria-label={alt}>
          <Icon name="home" size={30} />
          <span>NOVA IMMOBILIER</span>
        </div>
      </div>
    );
  }

  const showFallback = state === 'fallback';
  return (
    <div className={`img-frame is-${showFallback ? 'loaded is-illustration' : state} ${frameClassName}`}>
      <img
        key={showFallback ? 'fallback' : src}
        src={showFallback ? fallback : src}
        alt={alt}
        loading={priority ? 'eager' : 'lazy'}
        decoding="async"
        {...(priority ? { fetchpriority: 'high' } : {})}
        onLoad={() => !showFallback && setState('loaded')}
        onError={() => setState(showFallback || !fallback ? 'error' : 'fallback')}
        className={className}
        {...rest}
      />
    </div>
  );
}

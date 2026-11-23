import { useState, type ImgHTMLAttributes } from 'react';
import { Icon } from './Icon';

interface Props extends ImgHTMLAttributes<HTMLImageElement> {
  src: string;
  alt: string;
  /** Eager-load above-the-fold images (hero, gallery main). */
  priority?: boolean;
  /** Optional class for the wrapper. */
  frameClassName?: string;
}

/**
 * Image with lazy loading, a soft loading state and a graceful fallback
 * when an external URL fails. The wrapper controls the aspect ratio.
 */
export function SmartImage({ src, alt, priority, frameClassName = '', className = '', ...rest }: Props) {
  const [state, setState] = useState<'loading' | 'loaded' | 'error'>('loading');

  return (
    <div className={`img-frame is-${state} ${frameClassName}`}>
      {state === 'error' ? (
        <div className="img-fallback" role="img" aria-label={alt}>
          <Icon name="home" size={30} />
          <span>NOVA IMMOBILIER</span>
        </div>
      ) : (
        <img
          key={src}
          src={src}
          alt={alt}
          loading={priority ? 'eager' : 'lazy'}
          decoding="async"
          {...(priority ? { fetchpriority: 'high' } : {})}
          onLoad={() => setState('loaded')}
          onError={() => setState('error')}
          className={className}
          {...rest}
        />
      )}
    </div>
  );
}

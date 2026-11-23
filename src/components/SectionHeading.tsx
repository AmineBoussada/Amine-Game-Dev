import type { ReactNode } from 'react';

interface Props {
  eyebrow?: string;
  title: string;
  text?: string;
  align?: 'left' | 'center';
  action?: ReactNode;
  as?: 'h1' | 'h2';
  id?: string;
}

export function SectionHeading({ eyebrow, title, text, align = 'left', action, as: Tag = 'h2', id }: Props) {
  return (
    <div className={`section-heading section-heading--${align} reveal`}>
      <div>
        {eyebrow && <span className="eyebrow">{eyebrow}</span>}
        <Tag id={id} className="section-heading__title">{title}</Tag>
        {text && <p className="lead">{text}</p>}
      </div>
      {action && <div className="section-heading__action">{action}</div>}
    </div>
  );
}

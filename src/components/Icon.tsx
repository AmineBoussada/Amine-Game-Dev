import type { SVGProps } from 'react';

/** Lightweight inline icon set (stroke icons, 24×24). No icon library needed. */
const paths: Record<string, JSX.Element> = {
  search: (<><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></>),
  heart: <path d="M12 20.5s-7.5-4.6-9.3-9.4C1.5 7.7 3.7 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.3 0 5.5 3.2 4.3 6.6-1.8 4.8-9.3 9.4-9.3 9.4Z" />,
  pin: (<><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z" /><circle cx="12" cy="9.5" r="2.5" /></>),
  area: (<><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" /></>),
  bed: (<><path d="M3 18v-8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8M3 14h18M3 18v2M21 18v2" /><path d="M7 8V6h4v2" /></>),
  bath: (<><path d="M4 12h16v3a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-3ZM6 12V6a2 2 0 0 1 3.5-1.3" /><path d="M7 20l-1 1.5M17 20l1 1.5" /></>),
  rooms: (<><rect x="3.5" y="3.5" width="17" height="17" rx="1.5" /><path d="M3.5 12h9M12.5 3.5v6M12.5 14.5v6" /></>),
  floor: (<><path d="M4 20h4v-4h4v-4h4V8h4" /></>),
  calendar: (<><rect x="3.5" y="5" width="17" height="15" rx="1.5" /><path d="M3.5 10h17M8 3v4M16 3v4" /></>),
  phone: <path d="M5 4h3l1.5 4.5-2 1.3a12 12 0 0 0 6.7 6.7l1.3-2L20 16v3a1.5 1.5 0 0 1-1.6 1.5A16.5 16.5 0 0 1 3.5 5.6 1.5 1.5 0 0 1 5 4Z" />,
  mail: (<><rect x="3" y="5" width="18" height="14" rx="1.5" /><path d="m3.5 6 8.5 7 8.5-7" /></>),
  clock: (<><circle cx="12" cy="12" r="8.5" /><path d="M12 7.5V12l3 2" /></>),
  arrow: <path d="M5 12h14M13 6l6 6-6 6" />,
  arrowLeft: <path d="M19 12H5M11 6l-6 6 6 6" />,
  chevronDown: <path d="m6 9 6 6 6-6" />,
  chevronLeft: <path d="m15 6-6 6 6 6" />,
  chevronRight: <path d="m9 6 6 6-6 6" />,
  close: <path d="M6 6l12 12M18 6 6 18" />,
  menu: <path d="M4 7h16M4 12h16M4 17h16" />,
  filter: <path d="M4 6h16M7 12h10M10 18h4" />,
  check: <path d="m5 12.5 4.5 4.5L19 7.5" />,
  expand: <path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" />,
  share: (<><circle cx="6" cy="12" r="2.5" /><circle cx="18" cy="6" r="2.5" /><circle cx="18" cy="18" r="2.5" /><path d="m8.2 10.8 7.6-3.6M8.2 13.2l7.6 3.6" /></>),
  home: (<><path d="M3.5 11 12 4l8.5 7" /><path d="M5.5 9.5V20h13V9.5" /><path d="M10 20v-5h4v5" /></>),
  key: (<><circle cx="8" cy="15" r="4" /><path d="m11 12 8-8M16 7l2 2M14 9l2 2" /></>),
  selection: (<><path d="M4 20h16" /><path d="M6 20V9l6-4 6 4v11" /><path d="m9.5 13 2 2 3.5-3.5" /></>),
  support: (<><circle cx="12" cy="8" r="3.5" /><path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5" /></>),
  map: (<><path d="m9 4-5.5 2v14L9 18l6 2 5.5-2V4L15 6 9 4Z" /><path d="M9 4v14M15 6v14" /></>),
  shield: (<><path d="M12 3.5 5 6v5.5c0 4.3 3 7.9 7 9 4-1.1 7-4.7 7-9V6l-7-2.5Z" /><path d="m9 12 2 2 4-4" /></>),
  layers: (<><path d="m12 4 8.5 4.5L12 13 3.5 8.5 12 4Z" /><path d="m3.5 12.5 8.5 4.5 8.5-4.5M3.5 16.5 12 21l8.5-4.5" /></>),
  image: (<><rect x="3.5" y="4.5" width="17" height="15" rx="1.5" /><circle cx="9" cy="10" r="1.8" /><path d="m4 18 5-5 4 4 2.5-2.5L20 19" /></>),
  facebook: <path d="M14 8.5h2.5V5H14a3.5 3.5 0 0 0-3.5 3.5V11H8v3.5h2.5V21H14v-6.5h2.5L17 11h-3V9a.5.5 0 0 1 .5-.5Z" />,
  instagram: (<><rect x="3.5" y="3.5" width="17" height="17" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.2" cy="6.8" r="0.6" fill="currentColor" /></>),
  linkedin: (<><rect x="3.5" y="3.5" width="17" height="17" rx="2" /><path d="M8 10.5V16.5M8 7.6v.1M11.5 16.5v-6M11.5 13c0-1.6 1-2.6 2.5-2.6s2 1 2 2.6v3.5" /></>),
  youtube: (<><rect x="2.5" y="5.5" width="19" height="13" rx="3.5" /><path d="m10 9.2 5 2.8-5 2.8V9.2Z" /></>),
};

/** WhatsApp glyph is a filled brand mark. */
const whatsapp = (
  <path
    fill="currentColor"
    stroke="none"
    d="M12 2.2a9.7 9.7 0 0 0-8.3 14.8L2.3 21.8l4.9-1.3A9.7 9.7 0 1 0 12 2.2Zm0 17.7a8 8 0 0 1-4.1-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A8 8 0 1 1 12 19.9Zm4.4-6c-.2-.1-1.4-.7-1.7-.8-.2-.1-.4-.1-.5.1l-.8 1c-.1.2-.3.2-.5.1a6.6 6.6 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.5-.4h-.5a.9.9 0 0 0-.7.3 2.8 2.8 0 0 0-.9 2.1 4.9 4.9 0 0 0 1 2.6 11.2 11.2 0 0 0 4.3 3.8c1.6.7 2.2.7 3 .6a2.5 2.5 0 0 0 1.7-1.2 2 2 0 0 0 .1-1.2c0-.1-.2-.2-.5-.3Z"
  />
);

export type IconName = keyof typeof paths | 'whatsapp';

interface Props extends SVGProps<SVGSVGElement> {
  name: IconName;
  size?: number;
}

export function Icon({ name, size = 20, ...rest }: Props) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={1.6}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      focusable="false"
      {...rest}
    >
      {name === 'whatsapp' ? whatsapp : paths[name]}
    </svg>
  );
}

export const swatchStyle = (bg: string) => `background-color:${bg};`;

export const sharedParameters = {
  layout: 'fullscreen' as const,
  controls: {disable: true},
  actions: {disable: true},
};

export type SwatchGroupDef = {
  group: string;
  fills: [string, string, string]; // [loud, normal, quiet]
  borders: [string, string, string];
  ons: [string, string, string]; // text on fill-loud, fill-normal, fill-quiet
};

export const groups: SwatchGroupDef[] = [
  {
    group: 'neutral',
    fills: [
      'var(--c-color-neutral-fill-loud)',
      'var(--c-color-neutral-fill-normal)',
      'var(--c-color-neutral-fill-quiet)',
    ],
    borders: [
      'var(--c-color-neutral-border-loud)',
      'var(--c-color-neutral-border-normal)',
      'var(--c-color-neutral-border-quiet)',
    ],
    ons: [
      'var(--c-color-neutral-on-loud)',
      'var(--c-color-neutral-on-normal)',
      'var(--c-color-neutral-on-quiet)',
    ],
  },
  {
    group: 'brand',
    fills: [
      'var(--c-color-brand-fill-loud)',
      'var(--c-color-brand-fill-quiet)',
      'var(--c-color-brand-fill-quiet)',
    ],
    borders: [
      'var(--c-color-brand-border-loud)',
      'var(--c-color-brand-border-quiet)',
      'var(--c-color-brand-border-quiet)',
    ],
    ons: [
      'var(--c-color-brand-on-loud)',
      'var(--c-color-brand-on-quiet)',
      'var(--c-color-brand-on-quiet)',
    ],
  },
  {
    group: 'accent',
    fills: [
      'var(--c-color-accent-fill-loud)',
      'var(--c-color-accent-fill-normal)',
      'var(--c-color-accent-fill-quiet)',
    ],
    borders: [
      'var(--c-color-accent-border-loud)',
      'var(--c-color-accent-border-normal)',
      'var(--c-color-accent-border-quiet)',
    ],
    ons: [
      'var(--c-color-accent-on-loud)',
      'var(--c-color-accent-on-normal)',
      'var(--c-color-accent-on-quiet)',
    ],
  },
  {
    group: 'info',
    fills: [
      'var(--c-color-info-fill-loud)',
      'var(--c-color-info-fill-normal)',
      'var(--c-color-info-fill-quiet)',
    ],
    borders: [
      'var(--c-color-info-border-loud)',
      'var(--c-color-info-border-normal)',
      'var(--c-color-info-border-quiet)',
    ],
    ons: [
      'var(--c-color-info-on-loud)',
      'var(--c-color-info-on-normal)',
      'var(--c-color-info-on-quiet)',
    ],
  },
  {
    group: 'success',
    fills: [
      'var(--c-color-success-fill-loud)',
      'var(--c-color-success-fill-normal)',
      'var(--c-color-success-fill-quiet)',
    ],
    borders: [
      'var(--c-color-success-border-loud)',
      'var(--c-color-success-border-normal)',
      'var(--c-color-success-border-quiet)',
    ],
    ons: [
      'var(--c-color-success-on-loud)',
      'var(--c-color-success-on-normal)',
      'var(--c-color-success-on-quiet)',
    ],
  },
  {
    group: 'warning',
    fills: [
      'var(--c-color-warning-fill-loud)',
      'var(--c-color-warning-fill-normal)',
      'var(--c-color-warning-fill-quiet)',
    ],
    borders: [
      'var(--c-color-warning-border-loud)',
      'var(--c-color-warning-border-normal)',
      'var(--c-color-warning-border-quiet)',
    ],
    ons: [
      'var(--c-color-warning-on-loud)',
      'var(--c-color-warning-on-normal)',
      'var(--c-color-warning-on-quiet)',
    ],
  },
  {
    group: 'danger',
    fills: [
      'var(--c-color-danger-fill-loud)',
      'var(--c-color-danger-fill-normal)',
      'var(--c-color-danger-fill-quiet)',
    ],
    borders: [
      'var(--c-color-danger-border-loud)',
      'var(--c-color-danger-border-normal)',
      'var(--c-color-danger-border-quiet)',
    ],
    ons: [
      'var(--c-color-danger-on-loud)',
      'var(--c-color-danger-on-normal)',
      'var(--c-color-danger-on-quiet)',
    ],
  },
];

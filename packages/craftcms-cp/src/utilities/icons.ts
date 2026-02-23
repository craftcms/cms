import {registerIconLibrary} from '@awesome.me/webawesome/dist/webawesome.js';

const LEGACY_NAMES: Record<string, string> = {
  alert: 'triangle-exclamation',
  asc: 'arrow-down-short-wide',
  asset: 'image',
  assets: 'image',
  circleuarr: 'circle-arrow-up',
  collapse: 'down-left-and-up-right-to-center',
  condition: 'diamond',
  darr: 'arrow-down',
  date: 'calendar',
  desc: 'arrow-down-wide-short',
  disabled: 'circle-dashed',
  done: 'circle-check',
  downangle: 'angle-down',
  draft: 'scribble',
  edit: 'pencil',
  enabled: 'circle',
  expand: 'up-right-and-down-left-from-center',
  external: 'arrow-up-right-from-square',
  field: 'pen-to-square',
  help: 'circle-question',
  home: 'house',
  info: 'circle-info',
  insecure: 'unlock',
  larr: 'arrow-left',
  layout: 'table-layout',
  leftangle: 'angle-left',
  listrtl: 'list-flip',
  location: 'location-dot',
  mail: 'envelope',
  menu: 'bars',
  move: 'grip-dots',
  newstamp: 'certificate',
  paperplane: 'paper-plane',
  plugin: 'plug',
  rarr: 'arrow-right',
  refresh: 'arrows-rotate',
  remove: 'xmark',
  rightangle: 'angle-right',
  rotate: 'rotate-left',
  routes: 'signs-post',
  search: 'magnifying-glass',
  secure: 'lock',
  settings: 'gear',
  shareleft: 'share-flip',
  shuteye: 'eye-slash',
  'sidebar-left': 'sidebar',
  'sidebar-right': 'sidebar-flip',
  'sidebar-start': 'sidebar', // Note: Was conditional based on $orientation (ltr: 'sidebar', rtl: 'sidebar-flip')
  'sidebar-end': 'sidebar-flip', // Note: Was conditional based on $orientation (ltr: 'sidebar-flip', rtl: 'sidebar')
  structure: 'list-tree',
  structurertl: 'list-tree-flip',
  template: 'file-code',
  time: 'clock',
  tool: 'wrench',
  uarr: 'arrow-up',
  upangle: 'angle-up',
  view: 'eye',
  wand: 'wand-magic-sparkles',
};

/**
 * This is mostly copied from font awesome directly because they don't seem to
 * give you a way to access the default library settings and extend them, only
 * overwrite them entirely.
 *
 * https://github.com/shoelace-style/webawesome/blob/da206a87873e3ab43ded7466a05005225aa50e69/packages/webawesome/src/components/icon/library.default.ts
 */
export function getIconUrl(
  name: string,
  family: string = 'classic',
  variant: string = 'regular'
) {
  let folder = 'solid';
  let resolvedVariant = variant;
  let resolvedName = name.endsWith('.svg') ? name.split('.svg')[0]! : name;

  if (name.includes('/')) {
    const [tmpVariant, ...tmpName] = name.split('/');

    resolvedVariant = tmpVariant ?? resolvedVariant;
    resolvedName = tmpName.join('/');
  }

  if (resolvedVariant === 'thin') {
    folder = 'thin';
  } else if (resolvedVariant === 'light') {
    folder = 'light';
  } else if (resolvedVariant === 'regular') {
    folder = 'regular';
  } else if (resolvedVariant === 'solid') {
    folder = 'solid';
  }

  // Brands
  if (family === 'brands') {
    folder = 'brands';
  }

  if (resolvedVariant === 'custom-icons') {
    folder = 'custom-icons';
  }

  resolvedName = LEGACY_NAMES[resolvedName] ?? resolvedName;

  return `/vendor/craft/icons/${folder}/${resolvedName}.svg`;
}

export function configureIcons() {
  registerIconLibrary('default', {
    resolver: (name: string, family = 'classic', variant = 'solid') => {
      return getIconUrl(name, family, variant);
    },
    mutator: (svg) => svg.setAttribute('fill', 'currentColor'),
  });
}

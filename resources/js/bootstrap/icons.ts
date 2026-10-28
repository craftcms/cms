import {createUrlIconResolver, getIconUrl, setIconResolver} from '@craftcms/ui';

const LEGACY_NAMES = new Map(
  Object.entries({
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
    remove: 'xmark-large',
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
    'sidebar-start': 'sidebar',
    'sidebar-end': 'sidebar-flip',
    structure: 'list-tree',
    structurertl: 'list-tree-flip',
    template: 'file-code',
    time: 'clock',
    tool: 'wrench',
    uarr: 'arrow-up',
    upangle: 'angle-up',
    view: 'eye',
    wand: 'wand-magic-sparkles',
  })
);

/** Names that resolve to the globe for the system's time zone, as `Icons::earth()` picks it. */
const EARTH_ALIASES = new Set(['world', 'earth']);

function normalizeIconName(name: string, earthIcon: string): string {
  const suffix = name.endsWith('.svg') ? '.svg' : '';
  const baseName = suffix ? name.slice(0, -suffix.length) : name;
  const resolve = (iconName: string) =>
    EARTH_ALIASES.has(iconName)
      ? earthIcon
      : (LEGACY_NAMES.get(iconName) ?? iconName);

  if (baseName.includes('/')) {
    const [variant, ...iconName] = baseName.split('/');

    return `${variant}/${resolve(iconName.join('/'))}${suffix}`;
  }

  return `${resolve(baseName)}${suffix}`;
}

export function configureIcons(
  baseUrl: string = '/vendor/craft/icons',
  earthIcon: string = 'earth-americas'
) {
  setIconResolver(
    createUrlIconResolver((name, family, variant) =>
      getIconUrl(normalizeIconName(name, earthIcon), family, variant, baseUrl)
    )
  );
}

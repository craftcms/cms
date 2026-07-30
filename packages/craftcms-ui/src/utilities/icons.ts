import {icons} from '@lion/ui/icon.js';
import {html, nothing, type TemplateResult} from 'lit';
import {unsafeSVG} from 'lit/directives/unsafe-svg.js';

export const CRAFT_ICON_NAMESPACE = 'craft';

type ResolvedIcon = TemplateResult | typeof nothing;
type LionIconResolver = (
  iconset: string,
  icon: string
) =>
  | TemplateResult
  | Promise<TemplateResult>
  | typeof nothing
  | Promise<typeof nothing>;

export type CraftIconResolver = (
  name: string,
  family: string,
  variant: string
) => ResolvedIcon | Promise<ResolvedIcon>;

export type CraftIconUrlResolver = (
  name: string,
  family: string,
  variant: string
) => string | null;

/**
 * Module-level cache of icon fetches, keyed by URL. Failed fetches are
 * evicted so they can retry on the next request.
 */
const iconCache = new Map<string, Promise<ResolvedIcon>>();

function getIconset(family: string, variant: string): string {
  return `${family}/${variant}`;
}

function parseIconset(iconset: string): {family: string; variant: string} {
  const [family = 'classic', variant = 'regular'] = iconset.split('/');

  return {family, variant};
}

async function requestIcon(url: string): Promise<ResolvedIcon> {
  try {
    const response = await fetch(url, {mode: 'cors'});

    if (!response.ok) {
      return nothing;
    }

    const container = document.createElement('div');
    container.innerHTML = await response.text();
    const svg = container.firstElementChild;

    if (svg?.tagName?.toLowerCase() !== 'svg') {
      return nothing;
    }

    svg.setAttribute('fill', 'currentColor');
    svg.setAttribute('part', 'svg');

    return html`${unsafeSVG(svg.outerHTML)}`;
  } catch {
    return nothing;
  }
}

/**
 * Icons that only ship in the `custom-icons` folder, so a bare name resolves
 * there instead of 404ing against Font Awesome's `solid`. Names that exist in
 * both (e.g. `grip-dots`) are deliberately absent — those keep resolving to the
 * Font Awesome copy.
 */
const CUSTOM_ICONS = new Set([
  'asterisk-slash',
  'c-debug',
  'c-outline',
  'clone-dashed',
  'craft-cms',
  'craft-partners',
  'craft-stack-exchange',
  'default-plugin',
  'diamond-slash',
  'duplicate',
  'element-card',
  'element-card-slash',
  'element-cards',
  'gear-slash',
  'graphql',
  'list-flip',
  'list-tree-flip',
  'notification-bottom-left',
  'notification-bottom-right',
  'notification-top-left',
  'notification-top-right',
  'share-flip',
  'slideout-left',
  'slideout-right',
  'thumb-left',
  'thumb-right',
]);

/**
 * Craft's pre-6 icon names, mapped onto the Font Awesome (or custom) icon that
 * replaced them.
 *
 * This lives here rather than in the CP bootstrap because the resolver built
 * from {@link getIconUrl} is installed at module load, while the CP's own
 * bootstrap only runs once its ES module executes — i.e. after the legacy
 * classic scripts have already rendered their first action menus. Aliasing at
 * the URL layer means those early icons resolve correctly too.
 */
const LEGACY_ICON_NAMES: Record<string, string> = {
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
};

/**
 * Resolves an icon name to its URL under the CP's published Font Awesome
 * assets.
 */
export function getIconUrl(
  name: string,
  family: string = 'classic',
  variant: string = 'regular',
  baseUrl: string = '/vendor/craft/icons'
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

  // Alias pre-6 names onto whatever replaced them, before deciding the folder —
  // some of them (`listrtl`, `shareleft`, `structurertl`) resolve to icons that
  // only exist under `custom-icons`.
  resolvedName = LEGACY_ICON_NAMES[resolvedName] ?? resolvedName;

  if (
    family === 'custom-icons' ||
    resolvedVariant === 'custom-icons' ||
    // These ship only as custom icons, so there's no Font Awesome variant to
    // honor — `craft-icon` defaults `variant` to `solid`, which would 404.
    CUSTOM_ICONS.has(resolvedName)
  ) {
    folder = 'custom-icons';
  }

  return `${baseUrl}/${folder}/${resolvedName}.svg`;
}

export function createUrlIconResolver(
  resolveUrl: CraftIconUrlResolver
): CraftIconResolver {
  return (name, family, variant) => {
    const url = resolveUrl(name, family, variant);

    if (url === null) {
      return nothing;
    }

    let request = iconCache.get(url);

    if (!request) {
      request = requestIcon(url);
      iconCache.set(url, request);
    }

    return request.then((icon) => {
      if (icon === nothing) {
        iconCache.delete(url);
      }

      return icon;
    });
  };
}

export const defaultIconResolver: CraftIconResolver =
  createUrlIconResolver(getIconUrl);

export function setIconResolver(resolver: CraftIconResolver) {
  icons.removeIconResolver(CRAFT_ICON_NAMESPACE);
  const lionResolver: LionIconResolver = (iconset, icon) => {
    const {family, variant} = parseIconset(iconset);

    return resolver(icon, family, variant) as ReturnType<LionIconResolver>;
  };

  icons.addIconResolver(CRAFT_ICON_NAMESPACE, lionResolver);
}

export function resolveIcon(
  name: string,
  family: string = 'classic',
  variant: string = 'regular'
): Promise<ResolvedIcon> {
  return Promise.resolve(
    icons.resolveIcon(CRAFT_ICON_NAMESPACE, getIconset(family, variant), name)
  ) as Promise<ResolvedIcon>;
}

setIconResolver(defaultIconResolver);

import {html, nothing, type TemplateResult} from 'lit';
import {unsafeSVG} from 'lit/directives/unsafe-svg.js';

type ResolvedIcon = TemplateResult | typeof nothing;

export type CraftIconResolver = (
  name: string,
  family: string,
  variant: string
) => ResolvedIcon | Promise<ResolvedIcon>;

/**
 * Where to fetch an icon from, and whether to mirror it horizontally, for an
 * icon drawn as the mirror image of another.
 */
export type CraftIconSource = {url: string; flip?: boolean};

export type CraftIconUrlResolver = (
  name: string,
  family: string,
  variant: string
) => string | CraftIconSource | null;

/**
 * Module-level cache of icon fetches, keyed by URL, plus `#flip` for mirrored
 * icons. Failed fetches are evicted so they can retry on the next request.
 */
const iconCache = new Map<string, Promise<ResolvedIcon>>();

async function requestIcon(url: string, flip: boolean): Promise<ResolvedIcon> {
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

    if (flip) {
      const style = svg.getAttribute('style');
      svg.setAttribute(
        'style',
        style ? `${style}; scale: -1 1` : 'scale: -1 1'
      );
    }

    return html`${unsafeSVG(svg.outerHTML)}`;
  } catch {
    return nothing;
  }
}

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
  if (family === 'brands' || resolvedVariant === 'brands') {
    folder = 'brands';
  }

  if (family === 'custom-icons' || resolvedVariant === 'custom-icons') {
    folder = 'custom-icons';
  }

  return `${baseUrl}/${folder}/${resolvedName}.svg`;
}

export function createUrlIconResolver(
  resolveUrl: CraftIconUrlResolver
): CraftIconResolver {
  return (name, family, variant) => {
    const source = resolveUrl(name, family, variant);

    if (source === null) {
      return nothing;
    }

    const {url, flip = false} =
      typeof source === 'string' ? {url: source} : source;
    const key = flip ? `${url}#flip` : url;
    let request = iconCache.get(key);

    if (!request) {
      request = requestIcon(url, flip);
      iconCache.set(key, request);
    }

    return request.then((icon) => {
      if (icon === nothing) {
        iconCache.delete(key);
      }

      return icon;
    });
  };
}

export const defaultIconResolver: CraftIconResolver =
  createUrlIconResolver(getIconUrl);

let currentResolver: CraftIconResolver = defaultIconResolver;

export function setIconResolver(resolver: CraftIconResolver) {
  currentResolver = resolver;
}

export function resolveIcon(
  name: string,
  family: string = 'classic',
  variant: string = 'regular'
): Promise<ResolvedIcon> {
  return Promise.resolve(currentResolver(name, family, variant));
}

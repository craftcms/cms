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

  if (family === 'custom-icons' || resolvedVariant === 'custom-icons') {
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

import {actionClient} from '@craftcms/ui/utilities/api/actionClient';

const cache = new Map<string, Promise<string>>();

/** Fetches an icon's SVG from the server, requesting each name only once. */
export async function iconSvg(name: string): Promise<Element | null> {
  let svg = cache.get(name);

  if (!svg) {
    svg = actionClient
      .post<{iconSvg: string}>('app/icon-svg', {icon: name})
      .then(({data}) => data.iconSvg);
    cache.set(name, svg);
    // Let a later call retry
    svg.catch(() => cache.delete(name));
  }

  const template = document.createElement('template');
  template.innerHTML = (await svg).trim();

  return template.content.firstElementChild;
}

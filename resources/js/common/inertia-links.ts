import {router} from '@inertiajs/vue3';
import {ConfigService} from '@craftcms/ui/services/Config';

/**
 * Opens a plain link to a control panel page with an Inertia visit, as a
 * `CpLink` would. Links in server-rendered HTML, such as elements’ metadata,
 * can’t be `CpLink`s, as the legacy control panel shows the same HTML.
 *
 * Clicks something else already handled, such as a `CpLink`, are left alone, and
 * so are front-end and action URLs, links that only move within the page, open
 * elsewhere, or download, links marked `data-inertia="false"`, links in editable
 * content, which the browser doesn’t follow either, links in slideouts and
 * dialogs, which a visit would navigate behind, and modified clicks. A page that
 * turns out not to be an Inertia page loads in full, as for any Inertia visit.
 */
export function visitControlPanelLink(event: MouseEvent): void {
  if (
    event.defaultPrevented ||
    event.altKey ||
    event.ctrlKey ||
    event.metaKey ||
    event.shiftKey ||
    event.button !== 0
  ) {
    return;
  }

  // Through shadow roots too, for links that web components render, and for
  // opt-outs on the components around them, such as `download` on a
  // `craft-button`, which renders its link in its shadow root.
  const path = event.composedPath();
  const link = path.find(
    (target): target is HTMLAnchorElement =>
      target instanceof HTMLAnchorElement && target.hasAttribute('href')
  );

  if (
    !link ||
    link.isContentEditable ||
    (link.target && link.target !== '_self') ||
    path.some(
      (target) =>
        target instanceof Element &&
        (target.hasAttribute('download') ||
          target.getAttribute('data-inertia') === 'false' ||
          target.matches(
            // Vue and legacy slideouts, dialogs, and Garnish modals. Not `role="dialog"`, which popovers have too.
            '[data-slideout-id], [data-slideout], dialog, craft-dialog, [aria-modal="true"]'
          ))
    )
  ) {
    return;
  }

  const config = ConfigService.getInstance();
  const url = parsedUrl(link.href);

  // An `href` the browser can’t parse isn’t followed anyway.
  if (!url) {
    return;
  }

  const href = link.getAttribute('href') ?? '';
  if (
    url.origin !== window.location.origin ||
    !isWithin(url, new URL(config.getCpUrl(''))) ||
    isWithin(url, new URL(config.getActionUrl(''))) ||
    // An empty `href`, or a bare `#`, stays on the page as much as a `#fragment` does.
    ((href === '' || link.href.includes('#')) &&
      url.pathname === window.location.pathname &&
      url.search === window.location.search)
  ) {
    return;
  }

  event.preventDefault();
  router.visit(link.href);
}

/**
 * Whether a URL is the base URL, or below it, by whole path segments: `/admin`
 * covers `/admin/entries`, but not `/administer`.
 */
function isWithin(url: URL, base: URL): boolean {
  // Decoded, as the server routes them: `/admin/%61ctions` is `/admin/actions`.
  // One that can’t be decoded is left to the browser, as it might be either.
  const path = decodedPath(url);
  const basePath = decodedPath(base)?.replace(/\/+$/, '');

  return (
    path !== null &&
    basePath !== undefined &&
    url.origin === base.origin &&
    (path === basePath || path.startsWith(`${basePath}/`))
  );
}

/**
 * A URL, or `null` if it can’t be parsed. Not `URL.parse()`, which older
 * browsers don’t have.
 */
function parsedUrl(href: string): URL | null {
  try {
    return new URL(href);
  } catch {
    return null;
  }
}

/**
 * A URL’s path, decoded, or `null` if it can’t be, such as for a stray `%`.
 */
function decodedPath(url: URL): string | null {
  try {
    return decodeURIComponent(url.pathname);
  } catch {
    return null;
  }
}

/**
 * Makes plain links to control panel pages anywhere in the document make
 * Inertia visits. Returns a function that stops it.
 *
 * On the window, so handlers delegated to the document, such as plugins’ and
 * jQuery’s, can still handle a click first, whenever they were added.
 */
export function handleControlPanelLinks(): () => void {
  window.addEventListener('click', visitControlPanelLink);

  return () => window.removeEventListener('click', visitControlPanelLink);
}

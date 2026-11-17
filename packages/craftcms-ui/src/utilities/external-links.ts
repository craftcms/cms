import {t} from './translate.js';

/** Marks a link this module set to open in a new tab. */
const MARKER = 'data-external-link';

/** Marks the screen-reader text this module added to a link. */
export const NEW_TAB_CUE_ATTRIBUTE = 'data-new-tab-cue';

/** What a link that opens in a new tab tells screen-reader users. */
export function newTabCue(): string {
  return t('Opens in a new window');
}

/**
 * Whether a URL leads to another host than the page's, as Craft 5 decides
 * which links open in a new tab. URLs without a host, such as relative,
 * `mailto:` and `tel:` ones, don't.
 */
export function isExternalUrl(url: string | null | undefined): boolean {
  if (!url) {
    return false;
  }

  let hostname: string;
  try {
    hostname = new URL(url, document.baseURI).hostname;
  } catch {
    return false;
  }

  return hostname !== '' && hostname !== window.location.hostname;
}

/** The `target` a link gets: its own, or `_blank` when it leads off-site. */
export function linkTarget(
  href: string | null | undefined,
  target: string | null | undefined
): string | null {
  return target || (isExternalUrl(href) ? '_blank' : null);
}

/** A link's `rel`, with `noopener` added when it opens in a new tab. */
export function linkRel(
  target: string | null | undefined,
  rel: string | null | undefined
): string | null {
  if (target !== '_blank') {
    return rel || null;
  }

  const tokens = new Set((rel ?? '').split(/\s+/).filter(Boolean));
  tokens.add('noopener');

  return Array.from(tokens).join(' ');
}

function isEditable(link: HTMLAnchorElement): boolean {
  const editable = link.closest('[contenteditable]');

  return (
    editable !== null && editable.getAttribute('contenteditable') !== 'false'
  );
}

function cueOf(link: HTMLAnchorElement): HTMLElement | null {
  return link.querySelector(`:scope > [${NEW_TAB_CUE_ATTRIBUTE}]`);
}

let cueCount = 0;

function addCue(link: HTMLAnchorElement): void {
  if (cueOf(link)) {
    return;
  }

  const cue = document.createElement('span');
  cue.className = 'sr-only';
  cue.setAttribute(NEW_TAB_CUE_ATTRIBUTE, '');
  cue.textContent = newTabCue();
  link.append(cue);

  // A name from `aria-label` replaces the link's text, so describe it instead.
  if (link.hasAttribute('aria-label') || link.hasAttribute('aria-labelledby')) {
    cue.id = `external-link-cue-${++cueCount}`;
    const describedBy = link.getAttribute('aria-describedby');
    link.setAttribute(
      'aria-describedby',
      describedBy ? `${describedBy} ${cue.id}` : cue.id
    );
  }
}

function removeCue(link: HTMLAnchorElement): void {
  const cue = cueOf(link);
  if (!cue) {
    return;
  }

  if (cue.id) {
    const describedBy = (link.getAttribute('aria-describedby') ?? '')
      .split(/\s+/)
      .filter((id) => id && id !== cue.id);
    if (describedBy.length) {
      link.setAttribute('aria-describedby', describedBy.join(' '));
    } else {
      link.removeAttribute('aria-describedby');
    }
  }

  cue.remove();
}

/**
 * Opens a link to another host in a new tab, as Craft 5 does: gives it
 * `target="_blank"` and `rel="noopener"`, and tells screen-reader users it
 * opens a new window. Links with a `target` of their own and links in
 * editable content are left alone.
 *
 * Safe to call again whenever the link changes: a link this set up that no
 * longer leads off-site, or whose `target` something else changed, is put
 * back, and a cue that went missing, as when a framework re-renders the
 * link's text, is added again.
 */
export function openExternalLinkInNewTab(link: HTMLAnchorElement): void {
  const marked = link.hasAttribute(MARKER);

  if (marked && link.getAttribute('target') !== '_blank') {
    link.removeAttribute(MARKER);
    removeCue(link);
    return;
  }

  if (!marked && (link.hasAttribute('target') || isEditable(link))) {
    return;
  }

  if (!isExternalUrl(link.getAttribute('href'))) {
    if (marked) {
      link.removeAttribute('target');
      link.removeAttribute(MARKER);
      removeCue(link);
    }
    return;
  }

  if (!marked) {
    link.setAttribute('target', '_blank');
    link.setAttribute('rel', linkRel('_blank', link.getAttribute('rel'))!);
    link.setAttribute(MARKER, '');
  }

  addCue(link);
}

/** {@link openExternalLinkInNewTab} for every link in a container, and the container itself. */
export function openExternalLinksInNewTabs(container: ParentNode): void {
  if (
    container instanceof HTMLAnchorElement &&
    container.hasAttribute('href')
  ) {
    openExternalLinkInNewTab(container);
  }

  container.querySelectorAll('a[href]').forEach((link) => {
    if (link instanceof HTMLAnchorElement) {
      openExternalLinkInNewTab(link);
    }
  });
}

/**
 * Keeps every link in a container opening in a new tab when it leads
 * off-site, including links added or changed later. Links in shadow roots are
 * the components' own to handle. Returns a function that stops it.
 */
export function observeExternalLinks(
  container: ParentNode & Node = document.body
): () => void {
  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.type === 'attributes') {
        if (
          mutation.target instanceof HTMLAnchorElement &&
          mutation.target.hasAttribute('href')
        ) {
          openExternalLinkInNewTab(mutation.target);
        }
        continue;
      }

      // Rewriting a link's text drops the cue along with it.
      const parentLink =
        mutation.target instanceof Element
          ? mutation.target.closest('a[href]')
          : null;
      if (parentLink instanceof HTMLAnchorElement) {
        openExternalLinkInNewTab(parentLink);
      }

      mutation.addedNodes.forEach((node) => {
        if (node instanceof Element) {
          openExternalLinksInNewTabs(node);
        }
      });
    }
  });

  observer.observe(container, {
    subtree: true,
    childList: true,
    attributes: true,
    attributeFilter: ['href', 'target'],
  });
  openExternalLinksInNewTabs(container);

  return () => observer.disconnect();
}

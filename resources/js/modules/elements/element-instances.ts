/**
 * Keeps the control panel's element chips and cards up to date when their
 * elements change, in this tab or another one.
 *
 * Chips and cards the server rendered as HTML (`craft-chip`/`craft-card` with a
 * `data-settings` attribute) are re-rendered in place through
 * `app/render-elements`. The parts the page added or owns — action menus, drag
 * handles, hidden inputs, selection state — are kept as they are.
 *
 * Chips and cards Vue draws from data (`ElementChips`, `ElementCards`, and
 * server HTML compiled as a Vue template, like the element index's title
 * cells) carry no `data-settings`: patching their DOM would leave Vue holding
 * nodes it no longer recognizes, so their owners refetch with
 * {@link onElementChanged} instead.
 */

import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {appendBodyHtml, appendHeadHtml} from '@craftcms/ui/utilities/dom';
import {elements as renderElementsAction} from '@actions/App/RenderController';
import {onBroadcast, type OnBroadcastOptions} from '@/common/craft/broadcast';
import {loadElementThumbs} from '@/modules/element-thumb-loader/globals';

type ElementId = string | number;

interface RenderedElements {
  elements: Record<string, Record<string, string>>;
  headHtml?: string;
  bodyHtml?: string;
}

/** Dispatched on `window` to refresh an element's chips and cards, by code that can't import this module. */
export const REFRESH_EVENT = 'craft:refresh-element-instances';

/** The parts of a chip or card that the page, rather than the server, put there. */
const PAGE_OWNED_PARTS = [
  ':scope > [slot="prefix"]',
  ':scope > [slot="suffix"]',
  ':scope > .card-titlebar > .card-actions-container',
  ':scope > input',
];

/** Host attributes that hold the page's state, not the server's. */
const PAGE_OWNED_ATTRIBUTES = new Set([
  'selected',
  'active',
  'collapsed',
  'tabindex',
  'draggable',
]);

/** How long messages about the same element are gathered into one change. */
const COALESCE_MS = 50;

const FOCUSABLE = 'a[href], button, input, select, textarea, [tabindex]';

let installed = false;

/**
 * Re-renders every server-rendered chip and card for an element, with one
 * request per site.
 */
export async function refreshElementInstances(
  elementId: ElementId,
  root: ParentNode = document
): Promise<void> {
  const instances = [
    ...root.querySelectorAll<HTMLElement>(
      `:is(craft-chip, craft-card).element[data-id="${CSS.escape(String(elementId))}"][data-settings]`
    ),
  ];

  const bySite = new Map<string, HTMLElement[]>();
  for (const instance of instances) {
    const siteId = instance.dataset.siteId ?? '';
    bySite.set(siteId, [...(bySite.get(siteId) ?? []), instance]);
  }

  await Promise.all(
    [...bySite.entries()].map(([siteId, group]) =>
      refreshGroup(elementId, siteId, group)
    )
  );
}

async function refreshGroup(
  elementId: ElementId,
  siteId: string,
  instances: HTMLElement[]
): Promise<void> {
  const first = instances[0]!;
  const {data} = await actionClient.post<RenderedElements>(
    renderElementsAction.url(),
    {
      elements: [
        {
          type: first.dataset.type,
          id: elementId,
          fieldId: first.dataset.fieldId ?? null,
          ownerId: first.dataset.ownerId ?? null,
          siteId,
          instances: instances.map((instance) =>
            JSON.parse(instance.dataset.settings ?? '{}')
          ),
        },
      ],
    }
  );

  const rendered = data.elements[String(elementId)] ?? {};
  const template = document.createElement('template');

  instances.forEach((instance, i) => {
    const html = rendered[i];
    if (html === undefined || !instance.isConnected) {
      return;
    }

    template.innerHTML = html.trim();
    const replacement = template.content.firstElementChild;
    if (replacement) {
      patchInstance(instance, replacement);
    }
  });

  if (data.headHtml) {
    await appendHeadHtml(data.headHtml);
  }
  if (data.bodyHtml) {
    await appendBodyHtml(data.bodyHtml);
  }

  loadElementThumbs(instances);
}

/**
 * Updates a chip or card to match a fresh render of it, without replacing the
 * element itself or the parts the page owns, so its selection, listeners and
 * focus survive.
 */
export function patchInstance(target: Element, replacement: Element): void {
  const focused = focusedWithin(target);
  const focusIndex = focused
    ? [...target.querySelectorAll(FOCUSABLE)].indexOf(focused)
    : -1;

  // The page-owned parts of the fresh render are swapped for the existing
  // ones, and the existing elements around them are reused, so none of them
  // has to leave the document.
  const keep = new Map<Element, Element>();
  const reuse = new Map<Element, Element>();
  const orphans: Element[] = [];

  for (const selector of PAGE_OWNED_PARTS) {
    const fresh = [...replacement.querySelectorAll(selector)];

    target.querySelectorAll(selector).forEach((existing, i) => {
      const counterpart = fresh[i];
      if (!counterpart) {
        // Such as an action menu the server only adds when asked for one,
        // which a re-render's settings don't ask for.
        if (existing.parentNode === target) {
          orphans.push(existing);
        }
        return;
      }

      keep.set(counterpart, existing);
      let freshParent = counterpart.parentElement;
      let existingParent = existing.parentElement;
      while (
        freshParent &&
        existingParent &&
        freshParent !== replacement &&
        existingParent !== target
      ) {
        reuse.set(freshParent, existingParent);
        freshParent = freshParent.parentElement;
        existingParent = existingParent.parentElement;
      }
    });
  }

  syncAttributes(target, replacement);
  mergeChildren(target, replacement, keep, reuse, orphans);

  if (focused && !focused.isConnected) {
    restoreFocus(
      target,
      target.querySelectorAll<HTMLElement>(FOCUSABLE)[focusIndex]
    );
  }
}

/**
 * Moves focus to the control that replaced the focused one, once the chip has
 * updated: content just slotted into a chip can't take focus until then.
 */
function restoreFocus(target: Element, equivalent: HTMLElement | undefined) {
  if (!equivalent) {
    return;
  }

  const {updateComplete} = target as {updateComplete?: Promise<unknown>};
  void Promise.resolve(updateComplete).then(() => {
    const active = document.activeElement;
    if (!active || active === document.body) {
      equivalent.focus({preventScroll: true});
    }
  });
}

function focusedWithin(target: Element): HTMLElement | null {
  const active = document.activeElement;

  return active instanceof HTMLElement &&
    active !== target &&
    target.contains(active)
    ? active
    : null;
}

function mergeChildren(
  target: Element,
  replacement: Element,
  keep: Map<Element, Element>,
  reuse: Map<Element, Element>,
  trailing: Node[] = []
): void {
  // Unchanged nodes stay as they are, so nothing in them is re-announced.
  const unchanged = Array.from(target.childNodes);
  const mapped = [...replacement.childNodes].map((child) => {
    const same = unchanged.findIndex((node) => node.isEqualNode(child));
    if (same !== -1) {
      return unchanged.splice(same, 1)[0]!;
    }

    if (!(child instanceof Element)) {
      return child;
    }

    const kept = keep.get(child);
    if (kept) {
      return kept;
    }

    const reused = reuse.get(child);
    if (reused) {
      syncAttributes(reused, child);
      mergeChildren(reused, child, keep, reuse);
      return reused;
    }

    return child;
  });
  const children = [...mapped, ...trailing];

  for (const child of Array.from(target.childNodes)) {
    if (!children.includes(child)) {
      child.remove();
    }
  }

  let next = target.firstChild;
  for (const child of children) {
    if (child === next) {
      next = next.nextSibling;
    } else {
      target.insertBefore(child, next);
    }
  }
}

function syncAttributes(target: Element, replacement: Element): void {
  for (const {name, value} of replacement.attributes) {
    if (name === 'class') {
      continue;
    }
    if (target.getAttribute(name) !== value) {
      target.setAttribute(name, value);
    }
  }

  for (const {name} of Array.from(target.attributes)) {
    if (
      name !== 'class' &&
      !replacement.hasAttribute(name) &&
      !PAGE_OWNED_ATTRIBUTES.has(name) &&
      !name.startsWith('aria-')
    ) {
      target.removeAttribute(name);
    }
  }

  // Classes the page added (selection, drag state) stay; the server's own
  // state classes go when the fresh render doesn't have them.
  const fresh = replacement.classList;
  for (const name of Array.from(target.classList)) {
    if (!fresh.contains(name) && isServerStateClass(name)) {
      target.classList.remove(name);
    }
  }
  if (fresh.length) {
    target.classList.add(...fresh);
  }
}

function isServerStateClass(name: string): boolean {
  return (
    name === 'error' ||
    name.startsWith('cp-color-') ||
    name.startsWith('thumb-')
  );
}

/**
 * Calls `handler` with the ID of each element that's saved, or whose draft is
 * deleted, in another tab — and in this one too, with `includeSelf`. For
 * components that draw chips or cards from data, to refetch it.
 *
 * Messages about the same element that arrive together, such as the
 * `saveElement` and `deleteDraft` an applied draft sends, call it once.
 */
export function onElementChanged(
  handler: (elementId: number) => void,
  options: OnBroadcastOptions = {}
): () => void {
  const pending = new Map<number, ReturnType<typeof setTimeout>>();
  const changed = (id: unknown) => {
    const elementId = Number(id);
    if (!elementId || pending.has(elementId)) {
      return;
    }

    pending.set(
      elementId,
      setTimeout(() => {
        pending.delete(elementId);
        handler(elementId);
      }, COALESCE_MS)
    );
  };

  const stops = [
    onBroadcast('saveElement', ({id}) => changed(id), options),
    onBroadcast(
      'deleteDraft',
      ({canonicalId}) => changed(canonicalId),
      options
    ),
  ];

  return () => {
    stops.forEach((stop) => stop());
    pending.forEach((timer) => clearTimeout(timer));
    pending.clear();
  };
}

/**
 * Refreshes chips and cards whenever an element is saved or a draft deleted,
 * on any open control panel page, and whenever legacy code calls
 * `Craft.refreshElementInstances()`.
 */
export function installElementInstanceRefresh(): void {
  if (installed) {
    return;
  }

  installed = true;

  const refresh = (elementId: ElementId) => {
    refreshElementInstances(elementId).catch((error: unknown) => {
      console.warn(`Couldn’t refresh element ${elementId}:`, error);
    });
  };

  onElementChanged(refresh, {includeSelf: true});
  window.addEventListener(REFRESH_EVENT, (event) => {
    const id = (event as CustomEvent<{id?: ElementId}>).detail?.id;
    if (id) {
      refresh(id);
    }
  });
}

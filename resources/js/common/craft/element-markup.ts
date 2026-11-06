import {PromiseQueue} from '@/common/utils/promise-queue';

/** Helpers for the legacy `div.element` chip markup element select inputs render. */

export interface ElementInfo {
  id: number | null;
  siteId: number | null;
  label: string | null;
  status: string | null;
  url: string | null;
  hasThumb: boolean;
  element: HTMLElement | null;
}

function numberData(value: string | undefined): number | null {
  if (value === undefined || value === '') {
    return null;
  }

  const number = Number(value);

  return Number.isFinite(number) ? number : null;
}

/** The element's data, from it or the first `.element` inside it. */
export function getElementInfo(source: Element | string): ElementInfo {
  let root: Element | null = null;

  if (typeof source === 'string') {
    const template = document.createElement('template');
    template.innerHTML = source.trim();
    root = template.content.firstElementChild;
  } else {
    root = source;
  }

  const element = root?.classList.contains('element')
    ? (root as HTMLElement)
    : (root?.querySelector<HTMLElement>('.element') ?? null);

  return {
    id: numberData(element?.dataset.id),
    siteId: numberData(element?.dataset.siteId),
    label: element?.dataset.label ?? null,
    status: element?.dataset.status ?? null,
    url: element?.dataset.url ?? null,
    hasThumb: element?.classList.contains('has-thumb') ?? false,
    element,
  };
}

/** Switches an element between its `small` and `large` sizes. */
export function setElementSize(element: HTMLElement, size: string): void {
  const newSize = size === 'large' ? 'large' : 'small';

  if (element.classList.contains(newSize)) {
    return;
  }

  element.classList.add(newSize);
  element.classList.remove(newSize === 'small' ? 'large' : 'small');

  const oldImg = element.classList.contains('has-thumb')
    ? element.querySelector<HTMLImageElement>(':scope > .thumb > img')
    : null;

  if (oldImg) {
    const img = document.createElement('img');
    img.sizes = `${newSize === 'small' ? 30 : 100}px`;
    img.srcset =
      oldImg.getAttribute('srcset') ||
      oldImg.getAttribute('data-pfsrcset') ||
      '';
    oldImg.replaceWith(img);
  }
}

type Styles = Record<string, string | number>;

function applyStyles(element: HTMLElement, styles: Styles): void {
  for (const [property, value] of Object.entries(styles)) {
    const name = property.replace(/[A-Z]/g, (c) => `-${c.toLowerCase()}`);
    element.style.setProperty(
      name,
      typeof value === 'number' && name !== 'opacity' && name !== 'z-index'
        ? `${value}px`
        : String(value)
    );
  }
}

const transitions = new PromiseQueue();

/** Animates an element to the given styles, after any animations already running. */
export function animate(
  element: HTMLElement,
  styles: Styles
): Promise<unknown> {
  return animateAll([[element, styles]]);
}

/**
 * Animates several elements at once, in a view transition, after any
 * animations already running. Without view transitions, the styles just apply.
 */
export function animateAll(
  animations: Array<[HTMLElement, Styles]>
): Promise<unknown> {
  return transitions.push(async () => {
    if (!document.startViewTransition) {
      for (const [element, styles] of animations) {
        applyStyles(element, styles);
      }
      return;
    }

    for (const [element] of animations) {
      if (getComputedStyle(element).viewTransitionName === 'none') {
        element.style.viewTransitionName = `vt-${Math.floor(Math.random() * 100000)}`;
      }
    }

    try {
      await document.startViewTransition(() => {
        for (const [element, styles] of animations) {
          applyStyles(element, styles);
        }
      }).finished;
    } catch (error) {
      // An interrupted transition still leaves the new styles in place
      console.warn(error);
    }
  });
}

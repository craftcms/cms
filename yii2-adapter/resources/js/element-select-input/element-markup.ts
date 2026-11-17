import {PromiseQueue} from '@/common/utils/promise-queue';
import {jq} from '@/common/utils/jquery';

/**
 * Helpers for legacy `div.element` markup, which element select inputs still
 * render. They take and return jQuery collections, as their callers do.
 */

// oxlint-disable-next-line typescript/no-explicit-any
type JQueryLike = any;

export interface ElementInfo {
  id: unknown;
  siteId: unknown;
  label: unknown;
  status: unknown;
  url: unknown;
  hasThumb: boolean;
  $element: JQueryLike;
}

/** The element's data, from it or the first `.element` inside it. */
export function getElementInfo(element: unknown): ElementInfo {
  const $: JQueryLike = jq();
  let $element = $(element);

  if (!$element.hasClass('element')) {
    $element = $element.find('.element:first');
  }

  return {
    id: $element.data('id'),
    siteId: $element.data('site-id'),
    label: $element.data('label'),
    status: $element.data('status'),
    url: $element.data('url'),
    hasThumb: $element.hasClass('has-thumb'),
    $element,
  };
}

/** Switches an element between its `small` and `large` sizes. */
export function setElementSize(element: unknown, size: string): void {
  const $: JQueryLike = jq();
  const $element = $(element);
  const newSize = size === 'large' ? 'large' : 'small';

  if ($element.hasClass(newSize)) {
    return;
  }

  $element
    .addClass(newSize)
    .removeClass(newSize === 'small' ? 'large' : 'small');

  if ($element.hasClass('has-thumb')) {
    const $oldImg = $element.find('> .thumb > img');
    $oldImg.replaceWith(
      $('<img/>', {
        sizes: `${newSize === 'small' ? 30 : 100}px`,
        srcset: $oldImg.attr('srcset') || $oldImg.attr('data-pfsrcset'),
      })
    );
  }
}

const transitions = new PromiseQueue();

/** Animates an element to the given CSS, after any animations already running. */
export function animate(
  element: unknown,
  css: Record<string, string | number>
): Promise<unknown> {
  return animateAll([[element, css]]);
}

/**
 * Animates several elements at once, in a view transition, after any
 * animations already running. Without view transitions, the CSS just applies.
 */
export function animateAll(
  animations: Array<[unknown, Record<string, string | number>]>
): Promise<unknown> {
  const $: JQueryLike = jq();

  return transitions.push(async () => {
    const $animations = animations.map(
      ([element, css]) => [$(element), css] as const
    );

    if (!document.startViewTransition) {
      for (const [$element, css] of $animations) {
        $element.css(css);
      }
      return;
    }

    for (const [$element] of $animations) {
      if ($element.css('view-transition-name') === 'none') {
        $element.css(
          'view-transition-name',
          `vt-${Math.floor(Math.random() * 100000)}`
        );
      }
    }

    try {
      await document.startViewTransition(() => {
        for (const [$element, css] of $animations) {
          $element.css(css);
        }
      }).finished;
    } catch (error) {
      // An interrupted transition still leaves the new styles in place
      console.warn(error);
    }
  });
}

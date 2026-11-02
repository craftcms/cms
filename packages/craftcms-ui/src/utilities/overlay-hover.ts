type HoverOptions = {
  /** The element whose hover and keyboard focus show the overlay. */
  invoker: HTMLElement;
  /** The overlay's content. Hovering it keeps the overlay showing. */
  content: HTMLElement;
  show(): void;
  hide(): void;
  delayIn?: number;
  delayOut?: number;
};

export type HoverWatcher = {
  /** Forgets the hover and focus state, for an overlay closed some other way. */
  reset(): void;
  teardown(): void;
};

/**
 * Whether focus arrived the way `:focus-visible` would show it — from the
 * keyboard, not a pointer press. `focusin` is retargeted out of shadow roots,
 * so this checks the element that actually took focus.
 */
function isFocusVisible(event: Event): boolean {
  const [target] = event.composedPath();

  return target instanceof Element && target.matches(':focus-visible');
}

/**
 * Whether the invoker is disabled outright. One kept focusable while disabled
 * (`focusable-when-disabled`) still shows its overlay, which says why.
 */
function isDisabled(invoker: HTMLElement): boolean {
  const node = invoker as HTMLElement & {
    disabled?: boolean;
    focusableWhenDisabled?: boolean;
  };

  return (
    (node.disabled === true ||
      invoker.getAttribute('aria-disabled') === 'true') &&
    !node.focusableWhenDisabled
  );
}

/**
 * Shows an overlay while its invoker or content is hovered, or its invoker
 * has keyboard focus, after `delayIn`, and hides it `delayOut` after neither.
 *
 * Only keyboard focus holds the overlay open. Clicking the invoker focuses it
 * too, and that shouldn't keep it showing once the pointer has left.
 */
export function watchHover({
  invoker,
  content,
  show,
  hide,
  delayIn = 0,
  delayOut = 300,
}: HoverOptions): HoverWatcher {
  let isFocused = false;
  let isHovered = false;
  let delay: ReturnType<typeof setTimeout> | undefined;

  const update = (event: Event) => {
    const {type} = event;

    clearTimeout(delay);
    isFocused =
      type === 'focusout'
        ? false
        : isFocused || (type === 'focusin' && isFocusVisible(event));
    isHovered =
      type === 'mouseleave' ? false : isHovered || type === 'mouseenter';

    if ((isFocused || isHovered) && !isDisabled(invoker)) {
      delay = setTimeout(show, delayIn);
    } else {
      delay = setTimeout(hide, delayOut);
    }
  };

  const listeners: [HTMLElement, string][] = [
    [content, 'mouseenter'],
    [content, 'mouseleave'],
    [invoker, 'mouseenter'],
    [invoker, 'mouseleave'],
    [invoker, 'focusin'],
    [invoker, 'focusout'],
  ];

  for (const [node, type] of listeners) {
    node.addEventListener(type, update);
  }

  return {
    reset() {
      isFocused = false;
      isHovered = false;
    },
    teardown() {
      clearTimeout(delay);
      for (const [node, type] of listeners) {
        node.removeEventListener(type, update);
      }
    },
  };
}

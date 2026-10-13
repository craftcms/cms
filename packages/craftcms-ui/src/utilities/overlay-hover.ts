type HoverController = {
  show(): void;
  hide(): void;
  addEventListener(type: string, listener: EventListener): void;
  removeEventListener(type: string, listener: EventListener): void;
  contentNode?: EventTarget | null;
  invokerNode?: EventTarget | null;
  _hasDisabledInvoker(): boolean;
};

type HoverInteractionOptions = {
  delayIn?: number;
  delayOut?: number;
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
 * Lion's `withHoverInteraction`, with the pending show/hide timer cleared on
 * teardown. Lion leaves it armed, so an overlay torn down mid-hover still calls
 * `show()` on its dead controller once the delay elapses.
 *
 * Only keyboard focus holds the overlay open. Clicking the invoker focuses it
 * too, and that shouldn't keep it showing once the pointer has left.
 */
export function withHoverInteraction({
  delayIn = 0,
  delayOut = 300,
}: HoverInteractionOptions = {}) {
  return {
    visibilityTriggerFunction: ({
      controller,
    }: {
      controller: HoverController;
    }) => {
      let isFocused = false;
      let isHovered = false;
      let delayTimeout: ReturnType<typeof setTimeout> | undefined;

      const resetActive = () => {
        isFocused = false;
        isHovered = false;
      };

      const handleOpenClosed = (event: Event) => {
        const {type} = event;

        clearTimeout(delayTimeout);
        isFocused =
          type === 'focusout'
            ? false
            : isFocused || (type === 'focusin' && isFocusVisible(event));
        isHovered =
          type === 'mouseleave' ? false : isHovered || type === 'mouseenter';

        if ((isFocused || isHovered) && !controller._hasDisabledInvoker()) {
          delayTimeout = setTimeout(() => controller.show(), delayIn);
        } else {
          delayTimeout = setTimeout(() => controller.hide(), delayOut);
        }
      };

      return {
        init: () => {
          controller.addEventListener('hide', resetActive);
          controller.contentNode?.addEventListener(
            'mouseenter',
            handleOpenClosed
          );
          controller.contentNode?.addEventListener(
            'mouseleave',
            handleOpenClosed
          );
          controller.invokerNode?.addEventListener(
            'mouseenter',
            handleOpenClosed
          );
          controller.invokerNode?.addEventListener(
            'mouseleave',
            handleOpenClosed
          );
          controller.invokerNode?.addEventListener('focusin', handleOpenClosed);
          controller.invokerNode?.addEventListener(
            'focusout',
            handleOpenClosed
          );
        },
        teardown: () => {
          clearTimeout(delayTimeout);
          controller.removeEventListener('hide', resetActive);
          controller.contentNode?.removeEventListener(
            'mouseenter',
            handleOpenClosed
          );
          controller.contentNode?.removeEventListener(
            'mouseleave',
            handleOpenClosed
          );
          controller.invokerNode?.removeEventListener(
            'mouseenter',
            handleOpenClosed
          );
          controller.invokerNode?.removeEventListener(
            'mouseleave',
            handleOpenClosed
          );
          controller.invokerNode?.removeEventListener(
            'focusin',
            handleOpenClosed
          );
          controller.invokerNode?.removeEventListener(
            'focusout',
            handleOpenClosed
          );
        },
      };
    },
  };
}

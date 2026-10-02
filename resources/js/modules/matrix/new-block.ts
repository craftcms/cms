/**
 * The attribute that plays a block's one-shot highlight, and how long the
 * animation behind it runs for. Both halves live in `resources/css/matrix.css`;
 * the duration is repeated here because the Vue control drops the attribute on
 * a timer rather than waiting on the animation.
 */
export const NEW_BLOCK_ATTRIBUTE = 'data-matrix-block-new';

export const NEW_BLOCK_HIGHLIGHT_MS = 1200;

/**
 * Highlights a block that has just appeared, so it's obvious which one is new
 * when it lands among others.
 *
 * For the stacks that own their own DOM. The attribute is dropped once the
 * animation has had its say, so nothing replays it later; if the animation
 * never runs the attribute is inert anyway — what it animates is invisible at
 * rest.
 */
export function flashNewBlock(block: HTMLElement): void {
  block.setAttribute(NEW_BLOCK_ATTRIBUTE, '');
  block.addEventListener(
    'animationend',
    () => block.removeAttribute(NEW_BLOCK_ATTRIBUTE),
    {once: true}
  );
}

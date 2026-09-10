/**
 * The class that plays a block's one-shot highlight, and how long the
 * animation behind it runs for. Both halves live in `resources/css/matrix.css`;
 * the duration is repeated here because the Vue control drops the class on a
 * timer rather than waiting on the animation.
 */
export const NEW_BLOCK_CLASS = 'is-new';

export const NEW_BLOCK_HIGHLIGHT_MS = 1200;

/**
 * Highlights a block that has just appeared, so it's obvious which one is new
 * when it lands among others.
 *
 * For the stacks that own their own DOM. The class is dropped once the
 * animation has had its say, so nothing replays it later; if the animation
 * never runs the class is inert anyway — what it animates is invisible at rest.
 */
export function flashNewBlock(block: HTMLElement): void {
  block.classList.add(NEW_BLOCK_CLASS);
  block.addEventListener(
    'animationend',
    () => block.classList.remove(NEW_BLOCK_CLASS),
    {once: true}
  );
}

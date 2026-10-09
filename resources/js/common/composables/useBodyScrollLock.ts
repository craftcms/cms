import {onScopeDispose, toValue, watch, type MaybeRefOrGetter} from 'vue';

/**
 * How many callers currently want the body locked. Module scope, so every
 * caller on the page shares it.
 */
let holders = 0;

function apply(): void {
  document.body.classList.toggle('no-scroll', holders > 0);
}

/**
 * Stops the page behind an overlay from scrolling while `locked` is true,
 * using the CP's `body.no-scroll` convention.
 *
 * Ref-counted: overlays nest — the customize-sources modal opens a page
 * settings modal, which opens an icon picker — and each holds its own lock, so
 * the body only scrolls again once the last of them lets go. The lock is
 * released automatically when the owning scope is disposed, which covers a
 * caller unmounted while it was still open.
 */
export function useBodyScrollLock(locked: MaybeRefOrGetter<boolean>): void {
  let held = false;

  function hold(next: boolean): void {
    if (next === held) return;

    held = next;
    holders += next ? 1 : -1;
    apply();
  }

  watch(() => toValue(locked), hold, {immediate: true});
  onScopeDispose(() => hold(false));
}

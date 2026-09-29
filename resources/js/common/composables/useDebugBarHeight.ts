import {computed, shallowRef, type Ref} from 'vue';
import {useMutationObserver} from '@vueuse/core';
import {useVisibleHeight} from './useVisibleHeight';

const selector = '.phpdebugbar';

/**
 * How much of the viewport Laravel Debugbar is covering, or `null` when it
 * isn't on the page.
 *
 * The bar pins itself to the bottom of the viewport and changes height as it's
 * opened, closed and dragged, so the shell measures it rather than assuming its
 * collapsed height. Its script appends it to `<body>` after the page loads, so
 * this watches for it arriving too.
 */
export function useDebugBarHeight(): Readonly<Ref<number | null>> {
  const bar = shallowRef(document.querySelector<HTMLElement>(selector));

  useMutationObserver(
    document.body,
    () => {
      bar.value = document.querySelector<HTMLElement>(selector);
    },
    {childList: true}
  );

  const height = useVisibleHeight(bar);

  return computed(() => (bar.value ? height.value : null));
}

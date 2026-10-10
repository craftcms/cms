import {usePage} from '@inertiajs/vue3';
import {watch} from 'vue';
import type {CraftData} from './useCraftData';

/**
 * Mirrors the current user's "Underline links" preference onto `<body>` as
 * `underline-links`, which flips the link tokens (`--c-link-decoration`) and
 * the legacy stylesheet alike. Read from the shared page props, so saving the
 * preference takes effect without a full reload.
 */
export function useUnderlineLinks(): void {
  const page = usePage<{craft?: CraftData}>();

  watch(
    () => page.props?.craft?.currentUser?.underlineLinks ?? false,
    (underline) => {
      document.body.classList.toggle('underline-links', underline);
    },
    {immediate: true}
  );
}

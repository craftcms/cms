import {usePage} from '@inertiajs/vue3';
import {watch} from 'vue';
import type {CraftData} from './useCraftData';

/**
 * Mirrors the current user's accessibility preferences onto `<body>`, as the
 * legacy `base.twig` layout does: "Underline links" as `underline-links`,
 * which flips the link tokens (`--c-link-decoration`), and "Use shapes to
 * represent statuses" as `use-shapes`, which gives status dots their shapes.
 * Both reach the legacy stylesheet too. Read from the shared page props, so
 * saving a preference takes effect without a full reload.
 */
export function useAccessibilityClasses(): void {
  const page = usePage<{craft?: CraftData}>();

  watch(
    () => page.props?.craft?.currentUser?.underlineLinks ?? false,
    (underline) => {
      document.body.classList.toggle('underline-links', underline);
    },
    {immediate: true}
  );

  watch(
    () => page.props?.craft?.currentUser?.useShapes ?? false,
    (useShapes) => {
      document.body.classList.toggle('use-shapes', useShapes);
    },
    {immediate: true}
  );
}

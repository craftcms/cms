import {defineComponent, h, provide} from 'vue';
import DetailsPanels from '@/common/components/DetailsPanels.vue';
import {
  ScreenPanelsKey,
  createScreenPanels,
} from '@/common/composables/screenPanels';

/**
 * A stand-in for a screen in tests: its default slot can add panels with
 * `ScreenPanel`, which it shows in a details column, as a screen does.
 */
export const DetailsScreen = defineComponent({
  name: 'DetailsScreen',
  setup(_, {slots}) {
    const registry = createScreenPanels();
    provide(ScreenPanelsKey, registry);

    return () => [h(DetailsPanels, {registry}), slots.default?.()];
  },
});

import type {CpComponentRegistry} from '@/bootstrap/components';
import CpContainer from '@/common/components/CpContainer.vue';
import LayoutSlot from '@/common/components/LayoutSlot.vue';
import AppLayout from './AppLayout.vue';
import {ownedPageOnly} from './ownedPageOnly';

/**
 * The screen layout, for plugin pages that render it inline, `LayoutSlot`,
 * for filling its regions, and `CpContainer`, which pads page content to line
 * up with the shell's. Plugins resolve them by name
 * (`resolveComponent('craft:layout-slot')`).
 *
 * The first two change the screen's chrome, so they only render on a page
 * the plugin owns.
 */
export function registerLayoutComponents(
  components: Pick<CpComponentRegistry, 'register'>
): void {
  components.register(
    'craft:app-layout',
    ownedPageOnly('craft:app-layout', AppLayout)
  );
  components.register(
    'craft:layout-slot',
    ownedPageOnly('craft:layout-slot', LayoutSlot)
  );
  components.register('craft:cp-container', CpContainer);
}

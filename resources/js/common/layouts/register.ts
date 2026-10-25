import type {CpComponentRegistry} from '@/bootstrap/components';
import CpContainer from '@/common/components/CpContainer.vue';
import LayoutSlot from '@/common/components/LayoutSlot.vue';
import ScreenPanel from '@/common/components/ScreenPanel.vue';
import AppLayout from './AppLayout.vue';
import {ownedPageOnly} from './ownedPageOnly';

/**
 * The screen layout, for plugin pages that render it inline, `LayoutSlot`,
 * for filling its regions, `ScreenPanel`, for adding panels to its details
 * column, and `CpContainer`, which pads page content to line up with the
 * shell's. Plugins resolve them by name
 * (`resolveComponent('craft:layout-slot')`).
 *
 * All but the last change the screen's chrome, so they only render on a page
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
  components.register(
    'craft:screen-panel',
    ownedPageOnly('craft:screen-panel', ScreenPanel)
  );
  components.register('craft:cp-container', CpContainer);
}

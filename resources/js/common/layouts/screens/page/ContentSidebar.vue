<script setup lang="ts">
  /**
   * The column before the content. Defaults to the secondary nav built from the
   * page's `subnav` prop; the `content-sidebar` slot replaces it.
   */
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import SecondaryNav from '@/common/components/SecondaryNav.vue';
  import {navItemActions} from '@/common/composables/navActions';
  import type {ActionItem} from '@/common/types';
  import type {ScreenSlots} from '../types';

  defineProps<{
    /** Hidden rather than removed: the outlets must stay in the DOM. */
    visible: boolean;
    subnav: Array<CraftCms.Cms.Cp.Data.NavItem>;
    subnavActions?: Array<ActionItem>;
  }>();

  defineSlots<Pick<ScreenSlots, 'content-sidebar' | 'subnav-actions'>>();
</script>

<template>
  <div
    v-show="visible"
    id="content-sidebar"
    tabindex="-1"
    class="cp-content__sidebar"
  >
    <LayoutSlotOutlet name="content-sidebar">
      <slot name="content-sidebar">
        <!-- The subnav-actions outlet lives inside this fallback, so a page can
          teleport `content-sidebar` or `subnav-actions`, never both. -->
        <SecondaryNav :items="navItemActions(subnav)" :actions="subnavActions">
          <template #actions>
            <LayoutSlotOutlet name="subnav-actions">
              <slot name="subnav-actions"></slot>
            </LayoutSlotOutlet>
          </template>
        </SecondaryNav>
      </slot>
    </LayoutSlotOutlet>
  </div>
</template>

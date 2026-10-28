<script setup lang="ts">
  /**
   * The strip above the content header: the page's toolbar meta at the start,
   * its toolbar actions at the end. The shell hides it while it's empty
   * rather than removing it, so its outlets stay in the DOM for page content
   * to teleport into.
   */
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';

  defineProps<{
    /** Adds the ids the legacy JS looks the toolbar up by. */
    legacyIds?: boolean;
  }>();

  defineSlots<{
    meta(): unknown;
    actions(): unknown;
  }>();
</script>

<template>
  <CpContainer>
    <div
      class="border-b border-b-quiet py-1 divide flex justify-between items-center min-h-(--cp-header-height)"
    >
      <LayoutSlotOutlet name="content-toolbar">
        <div
          :id="legacyIds ? 'toolbar' : undefined"
          class="flex gap-2 items-center"
        >
          <LayoutSlotOutlet name="content-toolbar-meta">
            <slot name="meta"></slot>
          </LayoutSlotOutlet>
        </div>

        <div class="flex gap-2 items-center">
          <LayoutSlotOutlet name="content-toolbar-actions">
            <slot name="actions"></slot>
          </LayoutSlotOutlet>
        </div>
      </LayoutSlotOutlet>
    </div>
  </CpContainer>
</template>

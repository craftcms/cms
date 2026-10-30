<script setup lang="ts">
  /**
   * The content's heading row: the page title, then the page's own actions.
   * The shell hides it while it has nothing to show, rather than removing it,
   * so its outlets stay in the DOM for page content to teleport into.
   */
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';

  defineSlots<{
    /** The heading; the shell passes the page title by default. */
    title(): unknown;
    actions(): unknown;
  }>();
</script>

<template>
  <div id="cp-content-header" class="pt-lg pb-md">
    <CpContainer>
      <div class="flex flex-wrap gap-md items-center justify-between">
        <LayoutSlotOutlet name="title">
          <slot name="title"></slot>
        </LayoutSlotOutlet>

        <div class="flex gap-2 items-center">
          <LayoutSlotOutlet name="content-actions">
            <slot name="actions"></slot>
          </LayoutSlotOutlet>
        </div>
      </div>
    </CpContainer>
  </div>
</template>

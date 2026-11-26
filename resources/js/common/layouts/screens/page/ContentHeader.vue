<script setup lang="ts">
  /**
   * The content's heading row: the page title, then the page's own actions.
   * The shell hides it while it has nothing to show, rather than removing it,
   * so its outlets stay in the DOM for page content to teleport into.
   */
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';

  const {ruled = false} = defineProps<{
    /** Ends the row with a rule, with no padding below it. */
    ruled?: boolean;
  }>();

  defineSlots<{
    /** The heading; the shell passes the page title by default. */
    title(): unknown;
    actions(): unknown;
  }>();
</script>

<template>
  <div id="cp-content-header" class="pt-lg" :class="{'pb-md': !ruled}">
    <CpContainer>
      <div
        class="flex flex-wrap gap-md items-center justify-between"
        :class="{'pb-md border-b border-b-quiet': ruled}"
      >
        <LayoutSlotOutlet name="title">
          <slot name="title"></slot>
        </LayoutSlotOutlet>

        <!-- Kept at the end even when it wraps below the title. -->
        <div class="content-header__actions flex gap-md items-center ms-auto">
          <LayoutSlotOutlet name="content-actions">
            <slot name="actions"></slot>
          </LayoutSlotOutlet>
        </div>
      </div>
    </CpContainer>
  </div>
</template>

<style scoped>
  /*
    Gone while there's nothing in it, so the row doesn't leave a gap for it.
    Matches on data-empty rather than the outlet's inline style: a style
    attribute test inside :has() makes Chromium recheck it on every inline
    style change in the document.
  */
  .content-header__actions:has(> [data-layout-slot][data-empty]:only-child) {
    display: none;
  }
</style>

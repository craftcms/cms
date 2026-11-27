<script setup lang="ts">
  /**
   * Switches an `ImageComparison` between its views. It's separate so the
   * host can put it where it fits, such as a dialog's header. Show it only
   * when the comparison's `slidable` is true.
   */
  import '@craftcms/ui/components/button-group/button-group';
  import '@craftcms/ui/components/tooltip/tooltip';
  import {t} from '@craftcms/ui';
  import {useId} from 'vue';
  import type {ImageComparisonView} from './imageComparison';

  const view = defineModel<ImageComparisonView>({default: 'sideBySide'});

  const id = useId();

  function select(event: Event): void {
    view.value = (
      event as CustomEvent<{value: ImageComparisonView}>
    ).detail.value;
  }
</script>

<template>
  <div class="image-comparison-views">
    <craft-button-group
      :name="`${id}-view`"
      :aria-label="t('View')"
      .value="view"
      @change="select"
    >
      <craft-button
        :id="`${id}-sideBySide`"
        type="button"
        size="small"
        icon="table-columns"
        value="sideBySide"
      ></craft-button>
      <craft-button
        :id="`${id}-slider`"
        type="button"
        size="small"
        icon="arrows-left-right-to-line"
        value="slider"
      ></craft-button>
    </craft-button-group>
    <!-- Label the icon-only buttons. -->
    <craft-tooltip :for="`${id}-sideBySide`" invoker-relation="label">{{
      t('Side by side')
    }}</craft-tooltip>
    <craft-tooltip :for="`${id}-slider`" invoker-relation="label">{{
      t('Slider')
    }}</craft-tooltip>
  </div>
</template>

<style scoped>
  .image-comparison-views {
    display: flex;
    align-items: center;
  }
</style>

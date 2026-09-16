<script setup lang="ts">
  /**
   * The page header above the content: title, status badges, toolbar and
   * page-level actions (New …, Upload). The `content-header` slot replaces all
   * of it. The form's save controls live in `ContentFooter`.
   */
  import {computed} from 'vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import type {ScreenSlots} from '../types';
  import {useScreenRegions} from '../useScreenRegions';
  import CpContainer from '@/common/components/CpContainer.vue';

  defineProps<{
    title: string;
  }>();

  const slots =
    defineSlots<
      Pick<ScreenSlots, 'title' | 'title-badge' | 'toolbar' | 'actions'>
    >();

  const regions = useScreenRegions(slots);
  const hasToolbar = computed(() => regions.has('toolbar'));
</script>

<template>
  <div
    id="cp-content-header"
    class="flex gap-2 items-center justify-between pt-6 pb-3 border-b border-b-quiet"
  >
    <CpContainer>
      <div class="flex gap-2 items-center">
        <LayoutSlotOutlet name="title">
          <slot name="title">
            <h1 class="text-lg">{{ title }}</h1>
          </slot>
        </LayoutSlotOutlet>
        <LayoutSlotOutlet name="title-badge">
          <slot name="title-badge"></slot>
        </LayoutSlotOutlet>
      </div>
      <div v-show="hasToolbar" id="toolbar" class="flex items-center gap-2">
        <LayoutSlotOutlet name="toolbar">
          <slot name="toolbar"></slot>
        </LayoutSlotOutlet>
      </div>

      <div class="flex gap-2 items-center">
        <LayoutSlotOutlet name="actions">
          <slot name="actions"></slot>
        </LayoutSlotOutlet>
      </div>
    </CpContainer>
  </div>
</template>

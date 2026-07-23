<script setup lang="ts">
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {EntryType, ActionItem} from '@/common/types';
  import Tooltip from '@/common/components/Tooltip.vue';
  import {ref, watch} from 'vue';

  const emit = defineEmits<{
    (e: 'handle-ref', el: HTMLElement | null): void;
  }>();

  withDefaults(
    defineProps<
      Pick<
        EntryType,
        | 'name'
        | 'id'
        | 'handle'
        | 'color'
        | 'icon'
        | 'description'
        | 'indicators'
        | 'actions'
      > & {
        draggable?: boolean;
      }
    >(),
    {draggable: false}
  );

  const handleRef = ref<HTMLElement | null>(null);

  watch(
    handleRef,
    (el) => {
      emit('handle-ref', el);
    },
    {immediate: true}
  );
</script>

<template>
  <craft-chip
    :data-color="
      (color && typeof color !== 'string' ? color.value : color) ?? 'white'
    "
    :data-id="id"
  >
    <template v-if="icon">
      <craft-icon slot="icon" v-bind="icon" />
    </template>
    <div class="grid gap-1 justify-items-start">
      <div class="flex gap-1">
        <div class="font-bold">
          {{ name }}
        </div>
        <template v-if="description">
          <Tooltip>{{ description }}</Tooltip>
        </template>
      </div>
      <div class="cp-code">{{ handle }}</div>

      <div v-if="indicators">
        <craft-icon
          v-for="indicator in indicators"
          :key="indicator.icon"
          :name="indicator.icon"
          :label="indicator.label"
          :style="{
            color: indicator.iconColor,
          }"
        />
      </div>
    </div>

    <div slot="suffix" class="flex gap-0.5 items-center">
      <ActionMenu v-if="actions" :actions="actions as ActionItem[]" />
      <span v-if="draggable" ref="handleRef" class="drag-handle">
        <slot name="drag-handle">
          <craft-reorder-button variant="inherit"></craft-reorder-button>
        </slot>
      </span>
    </div>
  </craft-chip>
</template>

<style scoped lang="scss">
  // Some special styles for nice icon alignment. We might want to move this
  // into chips, but for right now this is the only spot
  craft-chip::part(prefix) {
    align-self: start;
    min-height: 1lh;
    justify-content: center;
  }

  .drag-handle {
    display: inline-flex;
    cursor: grab;
  }
</style>

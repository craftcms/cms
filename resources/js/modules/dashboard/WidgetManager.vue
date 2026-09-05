<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import type {DashboardWidget} from './types';

  const props = defineProps<{
    widgets: DashboardWidget[];
    columns: number;
    busy: boolean;
  }>();

  const opened = defineModel<boolean>('opened', {default: false});

  const emit = defineEmits<{
    remove: [widget: DashboardWidget];
    resize: [widget: DashboardWidget, colspan: number];
    reorder: [from: number, to: number];
  }>();

  const {setItemRef, setHandleRef, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => props.widgets.map((widget) => widget.id),
      enabled: () => !props.busy,
      onReorder: (from, to) => emit('reorder', from, to),
    });
</script>

<template>
  <craft-popover
    placement="bottom-end"
    :opened="opened"
    @opened-changed="opened = $event.detail.opened"
  >
    <craft-button
      slot="invoker"
      type="button"
      icon="gear"
      :aria-label="t('Settings')"
      :aria-expanded="opened"
    ></craft-button>
    <div slot="content-body" role="region" :aria-label="t('Widgets')">
      <h2 class="text-base mb-3">{{ t('Widgets') }}</h2>
      <craft-empty
        v-if="!widgets.length"
        :label="t('You don’t have any widgets yet.')"
      ></craft-empty>
      <ul v-else class="m-0 p-0 space-y-1 list-none">
        <li
          v-for="(widget, index) in widgets"
          :key="widget.id"
          :ref="(el) => setItemRef(el, widget.id)"
          class="flex flex-wrap items-center gap-3 rounded p-2"
          :class="{'bg-gray-100': getDropState(widget.id).type === 'is-over'}"
        >
          <craft-reorder-button
            :ref="
              (el: Parameters<typeof setHandleRef>[0]) =>
                setHandleRef(el, widget.id)
            "
            :disabled="busy || widgets.length < 2"
            :position="getRowPosition(index)"
            @reorder="
              emit(
                'reorder',
                index,
                index + ($event.detail.direction === 'up' ? -1 : 1)
              )
            "
          ></craft-reorder-button>
          <span :id="`widget-label-${widget.id}`" class="flex-1">{{
            widget.title || widget.name
          }}</span>
          <craft-slide-picker
            :min="1"
            :max="Math.min(columns, widget.maxColspan)"
            :step="1"
            :value="Math.min(widget.colspan, columns)"
            :disabled="busy"
            :label="t('Number of columns')"
            :described-by="`widget-label-${widget.id}`"
            @value-change="emit('resize', widget, $event.detail.value)"
          ></craft-slide-picker>
          <craft-button
            type="button"
            icon="trash"
            :aria-label="t('Delete')"
            :aria-describedby="`widget-label-${widget.id}`"
            :disabled="busy"
            @click="emit('remove', widget)"
          ></craft-button>
        </li>
      </ul>
    </div>
  </craft-popover>
</template>

<style scoped>
  craft-popover::part(popup) {
    width: min(26rem, calc(100vw - 2rem));
    max-width: none;
    max-height: min(36rem, calc(100dvh - 8rem));
  }
</style>

<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type CraftSlidePicker from '@craftcms/ui/components/slide-picker/slide-picker';
  import type CraftSelectColor from '@craftcms/ui/components/select-color/select-color';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import type {DashboardWidget} from './types';
  import {widgetName} from './widgetName';

  const props = defineProps<{
    widgets: DashboardWidget[];
    columns: number;
    busy: boolean;
    /** The colors a widget can have. */
    colors: CraftCms.Cms.Shared.Enums.Color[];
  }>();

  /** The color select's value for "No color". */
  const NO_COLOR = '__blank__';

  const opened = defineModel<boolean>('opened', {default: false});

  const emit = defineEmits<{
    remove: [widget: DashboardWidget];
    resize: [widget: DashboardWidget, colspan: number];
    recolor: [
      widget: DashboardWidget,
      color: CraftCms.Cms.Shared.Enums.Color | null,
    ];
    reorder: [from: number, to: number];
  }>();

  function recolor(widget: DashboardWidget, event: Event) {
    const value = String((event.target as CraftSelectColor).value ?? '');
    emit(
      'recolor',
      widget,
      value === NO_COLOR || value === ''
        ? null
        : (value as CraftCms.Cms.Shared.Enums.Color)
    );
  }

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
    .open="opened"
    @craft-show.self="opened = true"
    @craft-hide.self="opened = false"
  >
    <craft-button
      slot="invoker"
      type="button"
      icon="gear"
      :aria-label="t('Settings')"
      :aria-expanded="opened"
    ></craft-button>
    <div
      slot="content-body"
      role="region"
      aria-labelledby="widget-settings-heading"
    >
      <h2 id="widget-settings-heading" class="cp-h4 mb-md">
        {{ t('Widget Settings') }}
      </h2>
      <craft-empty
        v-if="!widgets.length"
        :label="t('You don’t have any widgets yet.')"
      ></craft-empty>
      <ul v-else class="m-0 p-0 grid gap-sm">
        <li
          v-for="(widget, index) in widgets"
          :key="widget.id"
          :ref="(el) => setItemRef(el, widget.id)"
          class="flex flex-wrap items-center gap-sm px-sm py-md rounded bg-(--c-color-fill-quiet)"
          :class="{'bg-gray-100': getDropState(widget.id).type === 'is-over'}"
        >
          <craft-reorder-button
            :ref="
              (el: Parameters<typeof setHandleRef>[0]) =>
                setHandleRef(el, widget.id)
            "
            :label="`${t('Reorder')} ${widgetName(widget)}`"
            :position="getRowPosition(index)"
            @craft-reorder="
              emit(
                'reorder',
                index,
                index + ($event.detail.direction === 'up' ? -1 : 1)
              )
            "
          ></craft-reorder-button>
          <span :id="`widget-label-${widget.id}`" class="flex-1">{{
            widgetName(widget)
          }}</span>
          <craft-select-color
            class="widget-color"
            .colors="colors"
            allow-transparent
            :blank-label="t('No color')"
            .value="widget.color ?? NO_COLOR"
            .disabled="busy"
            :label="`${t('Color')} ${widgetName(widget)}`"
            label-sr-only
            @change="recolor(widget, $event)"
          ></craft-select-color>
          <craft-slide-picker
            :min="1"
            :max="Math.min(columns, widget.maxColspan)"
            :step="1"
            .value="Math.min(widget.colspan, columns)"
            .disabled="busy"
            :label="`${t('Number of columns')} ${widgetName(widget)}`"
            label-sr-only
            @change="
              emit('resize', widget, ($event.target as CraftSlidePicker).value)
            "
          ></craft-slide-picker>
          <craft-button
            :id="`widget-delete-${widget.id}`"
            type="button"
            size="small"
            variant="danger-plain"
            icon
            :aria-labelledby="`widget-delete-${widget.id} widget-label-${widget.id}`"
            .disabled="busy"
            @click="emit('remove', widget)"
          >
            <craft-icon name="trash" :label="t('Delete')"></craft-icon>
          </craft-button>
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

  /* Wide enough for the longest color's name beside its swatch, leaving the
     row's other controls on one line. */
  .widget-color {
    flex: none;
    inline-size: 8.5rem;
  }
</style>

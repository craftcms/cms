<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import Widget from '@/modules/dashboard/Widget.vue';
  import WidgetManager from '@/modules/dashboard/WidgetManager.vue';
  import {useDashboard} from '@/modules/dashboard/useDashboard';
  import type {DashboardWidget, WidgetType} from '@/modules/dashboard/types';

  const props = defineProps<{
    widgets: DashboardWidget[];
    widgetTypes: Record<string, WidgetType>;
  }>();

  useAppLayout({title: t('Dashboard')});

  const {
    widgets,
    savedWidgets,
    container,
    ready,
    managing,
    busy,
    columns,
    deleted,
    actions,
    saved,
    cancel,
    remove,
    undo,
    reorder,
    resize,
    refreshGrid,
  } = useDashboard(props);
</script>

<template>
  <div>
    <LayoutSlot name="actions">
      <div class="flex gap-2">
        <ActionMenu :actions="actions" :label="t('New widget')"
          ><template #invoker
            ><craft-button type="button" icon="plus" :disabled="busy">{{
              t('New widget')
            }}</craft-button></template
          ></ActionMenu
        >
        <craft-button
          type="button"
          icon="gear"
          :aria-label="t('Settings')"
          :aria-expanded="managing"
          @click="managing = true"
        ></craft-button>
      </div>
    </LayoutSlot>
    <craft-callout v-if="deleted" class="mb-4" role="status">
      <span>{{
        t('“{name}” deleted.', {name: deleted.title || deleted.name})
      }}</span>
      <craft-button
        slot="action"
        type="button"
        :disabled="busy"
        @click="undo"
        >{{ t('Undo') }}</craft-button
      >
    </craft-callout>
    <craft-empty
      v-if="!widgets.length"
      :label="t('You don’t have any widgets yet.')"
    ></craft-empty>
    <div id="dashboard-grid" ref="container" class="dashboard-grid">
      <Widget
        v-for="widget in widgets"
        :key="
          JSON.stringify([
            widget.id,
            widget.settings,
            widget.data,
            widget.fragment,
          ])
        "
        class="item"
        :data-colspan="widget.colspan"
        :widget="widget"
        :ready="ready"
        @saved="saved(widget.id, $event)"
        @cancel="cancel(widget.id)"
        @resize="refreshGrid"
      />
    </div>
    <WidgetManager
      v-if="managing"
      :widgets="savedWidgets"
      :columns="columns"
      :busy="busy"
      @close="managing = false"
      @remove="remove"
      @reorder="reorder"
      @resize="resize"
    />
  </div>
</template>

<style scoped>
  .dashboard-grid {
    position: relative;
  }
  .item {
    margin-block-end: 14px;
  }
</style>

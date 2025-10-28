<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import {usePage} from '@inertiajs/vue3';
  import {computed, ref} from 'vue';
  import BaseWidget from '@/widgets/BaseWidget.vue';
  import {t} from '@craftcms/cp';

  interface Widget {
    id: number;
    type: string;
    colspan: number;
    title: string | null;
    subtitle: string | null;
    name: string | null;
    bodyHtml: string;
    settingsHtml: string;
    settingsJs: string;
    settings: {
      [key: string]: any;
    };
  }

  interface WidgetType {
    iconSvg: string;
    name: string;
    maxColspan: null | number;
    settingsHtml: string;
    settingsJs: string;
    selectable: boolean;
  }

  type CompleteWidget = Widget & {
    view?: 'settings' | 'default';
    new?: boolean;
    settingsNamespace?: string;
  };

  const {props} = usePage<{
    widgets: Widget[];
    widgetTypes: {
      [key: string]: WidgetType;
    };
  }>();

  const widgets = ref<CompleteWidget[]>([...props.widgets]);

  function createWidget(type: string) {
    const info = props.widgetTypes[type];
    if (!info) {
      throw new Error(`Unknown widget type: ${type}`);
    }

    const id = Math.floor(Math.random() * 1000000000);
    const settingsNamespace = `newwidget${id}-settings`;

    widgets.value.push({
      id,
      type,
      colspan: 1,
      title: t('app', `New "${info.name}" Widget`),
      subtitle: null,
      name: info.name,
      bodyHtml: '',
      settingsNamespace,
      settingsHtml: info.settingsHtml.replace(
        /__NAMESPACE__/g,
        settingsNamespace
      ),
      settingsJs: info.settingsJs,
      settings: info.settings || {},
      view: 'settings',
      new: true,
    });
  }

  function updateWidget(updates: Widget) {
    widgets.value = widgets.value.map((widget: Widget) => {
      if (widget.id === updates.id) {
        return updates;
      }

      return widget;
    });
  }

  function deleteWidget(id: number) {
    widgets.value = widgets.value.filter((widget: Widget) => widget.id !== id);
  }
</script>

<template>
  <AppLayout title="Dashboard">
    <template v-slot:actions>
      <craft-dropdown>
        <craft-button slot="trigger">
          <craft-icon name="plus"></craft-icon>
          New Widget
        </craft-button>

        <craft-dropdown-item
          v-for="(data, type) in props.widgetTypes"
          :key="type"
          @click="createWidget(type as string)"
        >
          <span class="icon" v-html="data.iconSvg" slot="icon"></span>
          {{ data.name }}
        </craft-dropdown-item>
      </craft-dropdown>
    </template>

    <div class="tw:px-4 tw:@container">
      <div class="widget-grid">
        <BaseWidget
          v-for="widget in widgets"
          :key="widget.id"
          v-bind="widget"
          @update="updateWidget"
          @delete="deleteWidget"
        />
      </div>
    </div>
  </AppLayout>
</template>

<style scoped lang="scss">
  .widget-grid {
    display: grid;
    gap: var(--c-spacing-md);
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
</style>

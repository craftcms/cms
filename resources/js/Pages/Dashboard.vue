<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import {usePage} from '@inertiajs/vue3';
  import {computed, ref} from 'vue';
  import BaseWidget from '@/widgets/BaseWidget.vue';
  import {t} from '@craftcms/cp';
  import type {CompleteWidget, Widget, WidgetType} from '@/types';

  const {props} = usePage<{
    widgets: Widget[];
    widgetTypes: {
      [key: string]: WidgetType;
    };
  }>();

  const widgets = ref<CompleteWidget[]>([...props.widgets]);
  const mode = ref<'view' | 'edit'>('view');
  const selectableTypes = computed(() =>
    Object.keys(props.widgetTypes)
      .filter((type) => props.widgetTypes[type]?.selectable)
      .map((key) => ({
        type: key,
        ...props.widgetTypes[key],
      }))
  );

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
      settings: {},
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

  function updateColspan(widget: Widget, event: Event) {
    updateWidget({
      ...widget,
      colspan: parseInt((event.target as HTMLInputElement).value, 10),
    });
  }

  function toggleMode() {
    if (mode.value === 'view') {
      mode.value = 'edit';
    } else {
      mode.value = 'view';
    }
  }

  function moveTo(fromIndex: number, toIndex: number) {
    if (toIndex < 0 || toIndex >= widgets.value.length) {
      return;
    }

    if (fromIndex !== toIndex && toIndex >= 0 && toIndex < widgets.value.length) {
      const widget = widgets.value.splice(fromIndex, 1)[0]
      widgets.value.splice(toIndex, 0, widget!)
    }
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
          v-for="data in selectableTypes"
          :key="data.type"
          @click="createWidget(data.type)"
        >
          <span class="icon" v-html="data.iconSvg" slot="icon"></span>
          {{ data.name }}
        </craft-dropdown-item>
      </craft-dropdown>

      <craft-button
        @click="toggleMode"
        icon
        :class="{
          'is-active': mode === 'edit',
        }"
      >
        <craft-icon name="gear"></craft-icon>
      </craft-button>
    </template>

    <div class="tw:px-4 tw:@container">
      <div
        :class="{
          'widget-grid': true,
          'widget-grid--edit': mode === 'edit',
        }"
      >
        <BaseWidget
          v-for="widget in widgets"
          :key="widget.id"
          v-bind="widget"
          :mode="mode"
          :class="{
            'tw:@md:col-span-1': widget.colspan === 1,
            'tw:@md:col-span-2': widget.colspan === 2,
            'tw:@md:col-span-3': widget.colspan === 3,
            'tw:@md:col-span-4': widget.colspan === 4,
          }"
          @update="updateWidget"
          @delete="deleteWidget"
        />
      </div>

      <template v-if="mode === 'edit'">
        <div class="edit-frame">
          <div class="tw:flex tw:mb-2 tw:items-center tw:justify-between">
            <h3>{{ t('app', 'Configure Widgets') }}</h3>
            <craft-button
              icon
              size="small"
              appearance="plain"
              class="tw:absolute tw:right-1 tw:top-1"
              @click="mode = 'view'"
            >
              <craft-icon name="x" style="font-size: 0.8em"></craft-icon>
            </craft-button>
          </div>
          <div v-for="(widget, idx) in widgets" :key="widget.id" class="widget-config">
            <craft-button icon type="button" size="small">
              <craft-icon name="arrows-up-down-left-right" style="font-size: 0.8em"></craft-icon>
            </craft-button>
            <div class="tw:grid tw:items-center tw:justify-center">
              <span
                class="icon"
                v-html="props.widgetTypes[widget.type]?.iconSvg"
                slot="icon"
              ></span>
            </div>
            {{ widget.name }}

            <input
              name="colspan"
              type="number"
              :value="widget.colspan"
              min="1"
              max="4"
              size="2"
              @change="updateColspan(widget, $event)"
            />

            <craft-button-group>
              <button type="button" size="small" @click="moveTo(idx, idx - 1)" :disabled="idx === 0">
                <craft-icon
                  name="chevron-up"
                  style="font-size: 0.8em"
                ></craft-icon>
              </button>
              <button type="button" size="small" @click="moveTo(idx, idx + 1)" :disabled="idx === widgets.length - 1">
                <craft-icon
                  name="chevron-down"
                  style="font-size: 0.8em"
                ></craft-icon>
              </button>
            </craft-button-group>

            <craft-button
              type="button"
              icon
              size="small"
              @click="deleteWidget(widget.id)"
            >
              <craft-icon name="x" style="font-size: 0.8em"></craft-icon>
            </craft-button>
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped lang="scss">
  .widget-grid {
    display: grid;
    gap: var(--c-spacing-md);
  }

  @container (width >= 768px) {
    .widget-grid {
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
  }

  .widget-grid--edit {
  }

  .widget-config {
    padding-block: calc(var(--tw-spacing) * 1.5);
    display: grid;
    gap: var(--c-spacing-md);
    align-items: center;
    grid-template-columns: auto 1rem minmax(0, 1fr) 4ch auto auto;
  }

  craft-button.is-active {
    background-color: var(--c-color-neutral-bg-emphasis);
    color: var(--c-color-neutral-on-emphasis);
  }

  .edit-frame {
    position: fixed;
    inset-block-end: var(--c-spacing-md);
    inset-inline-end: var(--c-spacing-md);
    width: calc(100% - var(--c-spacing-md) * 2);
    max-width: calc(400rem / 16);
    background-color: var(--c-bg-overlay);
    padding-block: var(--c-spacing-md);
    padding-inline: var(--c-spacing-lg);
    z-index: 1000;
    border: 1px solid var(--c-border-subtle);
    border-radius: var(--c-radius-md);
    box-shadow: var(--c-shadow-lg);
  }
</style>

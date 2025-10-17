<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import {usePage} from '@inertiajs/vue3';
  import VarDump from '@/components/VarDump.vue';
  import {computed, ref} from 'vue';

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

  const {props} = usePage<{
    widgets: Widget[];
    widgetTypes: {
      [key: string]: WidgetType;
    };
  }>();

  const widgets = ref(props.widgets)

  const openDrawer = (id: string) => {
    const drawer = document.querySelector(`#${id}`);
    drawer.open = true;
  };

  function createWidget(type: string) {
    const info = props.widgetTypes[type];
    if (!info) {
      throw new Error(`Unknown widget type: ${type}`);
    }

    widgets.value.push({
      id: Math.floor(Math.random() * 1000000000),
      type: type,
      colspan: 1,
      title: null,
      subtitle: null,
      name: info.name,
      bodyHtml: '',
      settingsHtml: info.settingsHtml,
      settingsJs: info.settingsJs,
      settings: {},
    })
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

    <div class="tw:px-4">
      <div class="widget-grid">
        <template v-for="widget in props.widgets" :key="widget.id">
          <craft-card :label="widget.title">
            <div slot="actions" v-if="widget.settingsHtml">
              <craft-button
                icon
                size="small"
                appearance="plain"
                @click="openDrawer(`drawer-${widget.id}`)"
              >
                <craft-icon name="gear"></craft-icon>
              </craft-button>
            </div>
            <div v-html="widget.bodyHtml"></div>

            <template v-if="widget.settingsHtml">
              <hr class="tw:my-4 tw:border-0 tw:border-b tw:border-b-subtle" />
              <form action="">
                <input type="hidden" name="action" value="">
                <div v-html="widget.settingsHtml"></div>

                <div class="tw:flex tw:gap-2 tw:mt-4">
                  <craft-button-submit>Save</craft-button-submit>
                  <craft-button-reset>Cancel</craft-button-reset>
                </div>
              </form>
            </template>
          </craft-card>
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped lang="scss">
  .widget-grid {
    display: grid;
  }

  @media screen and (min-width: 1024px) {
    .widget-grid {
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: var(--c-spacing-md);
    }
  }
</style>

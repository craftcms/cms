<script setup lang="ts">
  import {nextTick, onBeforeUnmount, onMounted, ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import {useResizeObserver} from '@vueuse/core';
  import WidgetSettings from './WidgetSettings.vue';
  import type {DashboardWidget} from './types';

  const props = defineProps<{widget: DashboardWidget; ready: boolean}>();
  const emit = defineEmits<{
    saved: [widget: DashboardWidget | false];
    cancel: [];
    resize: [];
  }>();

  const settings = ref(props.widget.id < 0);
  const container = ref<HTMLElement>();

  useResizeObserver(container, () => emit('resize'));
  const settingsButton = ref<HTMLElement>();
  let savedWidget: DashboardWidget | false | undefined;

  onMounted(() => {
    window.dispatchEvent(
      new CustomEvent('craft:widget-mounted', {
        detail: {
          element: container.value!,
          widget: props.widget,
          showSettings: () => {
            settings.value = true;
          },
          hideSettings: closeSettings,
        },
      })
    );
  });

  onBeforeUnmount(() => {
    window.dispatchEvent(
      new CustomEvent('craft:widget-unmounting', {
        detail: {element: container.value!},
      })
    );
  });

  async function closeSettings() {
    if (props.widget.id < 0) {
      emit('cancel');
      return;
    }

    settings.value = false;
    await nextTick();
    settingsButton.value?.focus();
    emit('resize');
  }

  watch(settings, async () => {
    await nextTick();
    emit('resize');

    if (settings.value) {
      container.value
        ?.querySelector<HTMLElement>(
          'form craft-input, form craft-select, form input, form textarea, form button'
        )
        ?.focus();
    }
  });

  function saved(widget: DashboardWidget | false) {
    savedWidget = widget;
    settings.value = false;
  }

  function finishSettingsLeave() {
    if (savedWidget === undefined) return;

    emit('saved', savedWidget);
    savedWidget = undefined;
  }
</script>

<template>
  <div
    :id="`widget${widget.id}`"
    ref="container"
    class="dashboard-widget widget"
    :class="widget.type.toLowerCase()"
    :data-id="widget.id"
    :data-type="widget.type"
    :data-title="widget.title"
  >
    <Transition name="widget-front">
      <div v-show="!settings" class="front" :inert="settings">
        <component
          v-if="ready && widget.id > 0 && widget.component"
          :is="widget.component"
          :id="widget.id"
          :data="widget.data"
          :widget="widget"
          @ready="emit('resize')"
        >
          <template #header>
            <div
              v-if="widget.title || widget.subtitle"
              slot="title"
              class="widget-heading"
            >
              <h2
                v-if="widget.title"
                :id="`widget-heading-${widget.id}`"
                class="text-base"
              >
                {{ widget.title }}
              </h2>
              <h5 v-if="widget.subtitle">{{ widget.subtitle }}</h5>
            </div>
            <craft-button
              v-if="widget.settingsForm"
              ref="settingsButton"
              slot="header-actions"
              class="widget-settings-button"
              type="button"
              icon="gear"
              :aria-label="t('Widget settings')"
              :aria-describedby="
                widget.title ? `widget-heading-${widget.id}` : undefined
              "
              @click="settings = true"
            ></craft-button>
          </template>
        </component>
      </div>
    </Transition>
    <Transition
      name="widget-back"
      @after-leave="finishSettingsLeave"
      @before-leave="(element) => element.setAttribute('inert', '')"
    >
      <div v-if="settings" class="settings-face">
        <craft-pane appearance="raised">
          <WidgetSettings
            :widget="widget"
            @saved="saved"
            @cancel="closeSettings"
          />
        </craft-pane>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
  .dashboard-widget {
    position: relative;
    perspective: 1000px;
    height: auto !important;
  }

  .dashboard-widget > .front,
  .dashboard-widget > .settings-face {
    backface-visibility: hidden;
    transform: rotateY(0deg);
    transition: transform 600ms ease;
  }

  .dashboard-widget > .widget-front-enter-from,
  .dashboard-widget > .widget-front-leave-to {
    transform: rotateY(180deg);
  }

  .dashboard-widget > .widget-back-enter-from,
  .dashboard-widget > .widget-back-leave-to {
    transform: rotateY(-180deg);
  }

  .widget-front-leave-active,
  .widget-back-leave-active {
    position: absolute;
    inset-block-start: 0;
    inset-inline-start: 0;
    width: 100%;
    pointer-events: none;
  }

  @media (prefers-reduced-motion: reduce) {
    .dashboard-widget > .front,
    .dashboard-widget > .settings-face {
      transition: none;
    }
  }
  craft-pane {
    display: block;
    position: relative;
  }
</style>

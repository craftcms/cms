<script setup lang="ts">
  import {
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    watch,
  } from 'vue';
  import {t} from '@craftcms/ui';
  import {useResizeObserver} from '@vueuse/core';
  import WidgetSettings from './WidgetSettings.vue';
  import type {DashboardWidget} from './types';
  import {provideWidgetColor} from './useWidgetColor';
  import {provideWidgetHeader} from './useWidgetHeader';

  const props = defineProps<{widget: DashboardWidget; ready: boolean}>();

  // The component rendering the widget, and its settings face below, color
  // their cards with it.
  provideWidgetColor(() => props.widget.color);
  // …and can add buttons to the header drawn above them, or hide its title.
  const header = provideWidgetHeader();
  const emit = defineEmits<{
    saved: [widget: DashboardWidget | false];
    cancel: [];
    resize: [];
    /** Its settings opened or closed. A reload waits for them to close. */
    settings: [open: boolean];
  }>();

  const settings = ref(props.widget.id < 0);
  const container = ref<HTMLElement>();

  useResizeObserver(container, () => emit('resize'));
  const settingsButton = ref<HTMLElement>();
  const settingsSending = shallowRef(false);
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

  watch(settings, (open) => emit('settings', open), {immediate: true});

  onBeforeUnmount(() => {
    emit('settings', false);
    window.dispatchEvent(
      new CustomEvent('craft:widget-unmounting', {
        detail: {element: container.value!},
      })
    );
  });

  async function closeSettings() {
    if (settingsSending.value) return;

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
            <!-- A title row at the top of the card's body, rather than a
                 header bar: the widget's title, and its settings button
                 beside the title's first line. -->
            <div
              v-if="widget.title || widget.subtitle || widget.settingsUi"
              class="widget-header"
              :class="{'widget-header--bare': header.titleHiders.value > 0}"
            >
              <div
                v-if="widget.title || widget.subtitle"
                class="widget-heading"
              >
                <h2
                  v-if="widget.title"
                  :id="`widget-heading-${widget.id}`"
                  class="widget-title cp-h3"
                >
                  {{ widget.title }}
                </h2>
                <p v-if="widget.subtitle" class="widget-subtitle">
                  {{ widget.subtitle }}
                </p>
              </div>
              <div class="widget-header-actions">
                <!-- Buttons the widget adds (see WidgetHeaderActions.vue). -->
                <span
                  :ref="
                    (element) =>
                      (header.actionsTarget.value =
                        (element as HTMLElement | null) ?? null)
                  "
                  class="widget-header-added-actions"
                ></span>
                <craft-button
                  v-if="widget.settingsUi"
                  ref="settingsButton"
                  class="widget-settings-button"
                  type="button"
                  icon="gear"
                  variant="plain"
                  flush="inline-end block"
                  :aria-label="t('Widget settings')"
                  :aria-describedby="
                    widget.title ? `widget-heading-${widget.id}` : undefined
                  "
                  @click="settings = true"
                ></craft-button>
              </div>
            </div>
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
        <craft-card :data-color="widget.color ?? undefined">
          <!-- The same header band as the widget's front. -->
          <div class="widget-header">
            <div class="widget-heading">
              <h2 class="widget-title cp-h3">
                {{ t('{type} Settings', {type: widget.name}) }}
              </h2>
            </div>
            <div v-if="widget.settingsUi" class="widget-header-actions">
              <craft-button
                type="button"
                icon="xmark-large"
                variant="plain"
                flush="inline-end block"
                :aria-label="t('Cancel')"
                .disabled="settingsSending"
                @click="closeSettings"
              ></craft-button>
            </div>
          </div>
          <WidgetSettings
            v-model:sending="settingsSending"
            :widget="widget"
            @saved="saved"
            @cancel="closeSettings"
          />
        </craft-card>
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
  craft-card {
    display: block;
    position: relative;
  }

  /* The title row the widget's component renders at the top of its card's
     body (see the header slot above). */
  /* A header band across the top of the card, with a hairline below. It
     reaches past the card body's padding (see card.styles.ts) to the card's
     edges, and is a header bar's height, 46px, unless the title wraps. */
  .widget-header {
    --_card-padding: clamp(
      var(--c-spacing-lg),
      2cqi + var(--c-spacing-md),
      calc(var(--c-spacing) * 6)
    );
    --_line-size: var(--widget-title-line-size, calc(var(--c-text-md) * 1.25));
    display: flex;
    align-items: flex-start;
    gap: var(--c-spacing-md);
    box-sizing: border-box;
    min-block-size: calc(46rem / 16 + 1px);
    margin-block: calc(var(--_card-padding) * -1) var(--_card-padding);
    margin-inline: calc(var(--_card-padding) * -1);
    padding-block: calc((46rem / 16 - var(--_line-size)) / 2);
    padding-inline: var(--_card-padding);
    border-block-end: 1px solid
      var(--c-color-border-quiet, var(--c-color-neutral-border-quiet));
  }

  .widget-heading {
    flex: 1;
    min-width: 0;
  }

  .widget-title {
    line-height: 1.25;
    overflow-wrap: anywhere;
  }

  .widget-subtitle {
    margin: 0;
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }

  /* A widget that shows a title of its own (see useWidgetHeader()) keeps
     this title only for screen readers. Its buttons sit in the card's top
     corner, inset like the card body's content, with no band. */
  .widget-header--bare {
    position: absolute;
    z-index: 1;
    inset-block-start: var(--_card-padding);
    inset-inline-end: var(--_card-padding);
    min-block-size: 0;
    margin: 0;
    padding: 0;
    border: 0;
  }

  .widget-header--bare > .widget-heading {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip-path: inset(50%);
    white-space: nowrap;
    border-width: 0;
  }

  .widget-header-added-actions {
    display: contents;
  }

  /* As tall as the title's first line, so the button centers on it however
     many lines the title wraps to. A widget that shows a title of its own
     can set --widget-title-line-size to that title's line height. */
  .widget-header-actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    flex: none;
    block-size: var(--_line-size);
    margin-inline-start: auto;
  }

  /* Widget settings are spaced like other compact forms, not like the
     full-page forms the dashboard's content pane is otherwise set up for. */
  .settings-face > craft-card {
    --c-field-group-gap: initial;
  }
</style>

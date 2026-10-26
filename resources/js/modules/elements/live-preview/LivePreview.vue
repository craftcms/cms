<script setup lang="ts">
  /**
   * Live Preview: the editor beside the element's front end, which reloads as
   * edits are autosaved.
   *
   * The editor isn't rendered here. The page's own editor content is moved into
   * `#{editorTargetId}` while this is open, so there's one form, one autosave
   * queue, and nothing to reconcile when it closes.
   */
  import {t} from '@craftcms/ui';
  import {
    addModalAttributes,
    ESC_KEY,
    releaseFocusWithin,
    setFocusWithin,
    trapFocusWithin,
  } from '@craftcms/garnish';
  import {
    useElementSize,
    useEventListener,
    usePreferredReducedMotion,
    useWindowSize,
  } from '@vueuse/core';
  import {
    computed,
    nextTick,
    onBeforeUnmount,
    ref,
    shallowRef,
    useId,
    useTemplateRef,
    watch,
  } from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import ResizeHandle from '@/common/components/ResizeHandle.vue';
  import {useResizable} from '@/common/composables/useResizable';
  import {uiLayerManager} from '@/common/slideouts/panel-stack';
  import {
    blendShapes,
    deviceShape,
    fitDevice,
    type DeviceShape,
    type DeviceType,
  } from './devicePreview';
  import type {LivePreviewController, LivePreviewFrame} from './useLivePreview';

  /** Room each pane keeps, however far the divider is dragged. */
  const MIN_PANE_WIDTH = 320;

  const props = defineProps<{
    controller: LivePreviewController;
    editorTargetId: string;
    title: string;
  }>();

  const emit = defineEmits<{
    (e: 'save'): void;
    (e: 'closed'): void;
  }>();

  const root = useTemplateRef<HTMLElement>('root');
  const stage = useTemplateRef<HTMLElement>('stage');
  const preview = props.controller;

  /*
   * Panes
   */
  const editor = useTemplateRef<HTMLElement>('editor');
  const editorId = `lp-editor-pane-${useId()}`;
  const newTabLinkId = `lp-new-tab-${useId()}`;
  const {width: windowWidth} = useWindowSize();
  const resizer = useResizable({
    target: editor,
    edge: 'inline-end',
    minWidth: () => Math.min(MIN_PANE_WIDTH, windowWidth.value / 2),
    maxWidth: () =>
      Math.max(MIN_PANE_WIDTH, windowWidth.value - MIN_PANE_WIDTH),
    cssVariable: '--lp-editor-width',
    storageKey: 'LivePreview.width',
  });

  // The drag only starts once the pointer has moved, and by then it could be
  // over the frame, which would take the events. So the frame lets go of the
  // pointer as soon as the handle is pressed.
  const handlePressed = ref(false);
  useEventListener(window, 'pointerup', () => (handlePressed.value = false));
  useEventListener(
    window,
    'pointercancel',
    () => (handlePressed.value = false)
  );

  /*
   * Progress bar
   */
  const progress = ref(0);
  const progressVisible = ref(false);
  let showTimer: ReturnType<typeof setTimeout> | undefined;
  let hideTimer: ReturnType<typeof setTimeout> | undefined;
  let trickleTimer: ReturnType<typeof setInterval> | undefined;

  function startProgress(): void {
    progressVisible.value = true;
    progress.value = 0.1;
    clearInterval(trickleTimer);
    // Creeps toward the end without reaching it, as there's no real progress to report.
    trickleTimer = setInterval(() => {
      progress.value += (0.9 - progress.value) * 0.1;
    }, 200);
  }

  function stopProgress(): void {
    clearTimeout(showTimer);
    clearTimeout(hideTimer);
    clearInterval(trickleTimer);
  }

  watch(
    () => preview.isLoading.value,
    (loading) => {
      stopProgress();

      if (loading) {
        if (progressVisible.value) {
          startProgress();
        } else {
          // Quick loads finish before it would show, rather than flashing it.
          showTimer = setTimeout(startProgress, 250);
        }
        return;
      }

      if (!progressVisible.value) {
        return;
      }

      progress.value = 1;
      hideTimer = setTimeout(() => {
        progressVisible.value = false;
        progress.value = 0;
      }, 400);
    }
  );

  onBeforeUnmount(stopProgress);

  /*
   * Devices
   */
  const {width: stageWidth, height: stageHeight} = useElementSize(stage);
  const reducedMotion = usePreferredReducedMotion();
  const targetShape = computed(() =>
    deviceShape(preview.device.value, preview.orientation.value)
  );
  const shape = shallowRef<DeviceShape | null>(targetShape.value);
  let shapeFrame = 0;

  // Animates the device's dimensions rather than its rendered size, so it's
  // fitted to the stage on every frame and never outgrows it on the way.
  watch(targetShape, (to) => {
    cancelAnimationFrame(shapeFrame);
    const from = shape.value;

    if (!to || !from || reducedMotion.value === 'reduce') {
      shape.value = to;
      return;
    }

    const start = performance.now();
    const step = (now: number) => {
      const t = Math.min(1, (now - start) / 300);
      const eased = t < 0.5 ? 4 * t ** 3 : 1 - (-2 * t + 2) ** 3 / 2;
      shape.value = t === 1 ? to : blendShapes(from, to, eased);

      if (t < 1) {
        shapeFrame = requestAnimationFrame(step);
      }
    };
    shapeFrame = requestAnimationFrame(step);
  });

  onBeforeUnmount(() => cancelAnimationFrame(shapeFrame));

  const layout = computed(() =>
    shape.value
      ? fitDevice(shape.value, {
          width: stageWidth.value,
          height: stageHeight.value,
        })
      : null
  );

  const deviceLabels: Record<DeviceType, string> = {
    desktop: t('Desktop'),
    tablet: t('Tablet'),
    phone: t('Mobile'),
  };

  const deviceIcons: Record<DeviceType, string> = {
    desktop: 'desktop',
    tablet: 'tablet-screen-button',
    phone: 'mobile-screen-button',
  };

  const announcement = ref('');

  function announceDevice(): void {
    const type = deviceLabels[preview.device.value];

    announcement.value =
      preview.device.value === 'desktop'
        ? t('Previewing {type} device', {type})
        : t('Previewing {type} device in {orientation}', {
            type,
            orientation:
              preview.orientation.value === 'landscape'
                ? t('Landscape')
                : t('Portrait'),
          });
  }

  function onDeviceChange(event: Event): void {
    const value = (event as CustomEvent<{value: DeviceType}>).detail?.value;

    if (value && value in deviceLabels) {
      preview.device.value = value;
      announceDevice();
    }
  }

  function rotate(): void {
    preview.orientation.value =
      preview.orientation.value === 'landscape' ? 'portrait' : 'landscape';
    announceDevice();
  }

  const deviceStyle = computed(() => {
    const value = layout.value;

    if (!value) {
      return undefined;
    }

    return {
      width: `${value.frame.width}px`,
      height: `${value.frame.height}px`,
      transform: `scale(${value.zoom})`,
    };
  });

  const viewportStyle = computed(() => {
    const value = layout.value;

    return value
      ? {
          width: `${value.viewport.width}px`,
          height: `${value.viewport.height}px`,
        }
      : undefined;
  });

  /*
   * Targets
   */
  const targetActions = computed(() =>
    preview.targets.value.map((target, index) => ({
      label: target.label,
      selected: index === preview.activeIndex.value,
      onClick: () => preview.switchTarget(index),
    }))
  );

  const currentUrl = computed(
    () =>
      preview.frames.value.find((frame) => frame.ready)?.url ??
      preview.frames.value.at(-1)?.url ??
      null
  );

  /*
   * Frames
   */
  const resizerOptions = computed(() => {
    const options = preview.iframeResizerOptions.value;

    return options !== false && window.iFrameResize ? options : null;
  });

  function onFrameMounted(
    frame: LivePreviewFrame,
    el: HTMLIFrameElement | null
  ): void {
    if (!el || el.dataset.lpFrame === String(frame.id)) {
      return;
    }

    el.dataset.lpFrame = String(frame.id);

    if (resizerOptions.value) {
      // Starts at the height of the frame it replaces, so the swap doesn't
      // jump the scroll position.
      const current = root.value?.querySelector<HTMLIFrameElement>(
        'iframe.lp-frame--ready'
      );
      if (current) {
        el.style.height = `${current.offsetHeight}px`;
      }

      window.iFrameResize!(
        {
          checkOrigin: false,
          ...resizerOptions.value,
        },
        el
      );
    }
  }

  /*
   * Opening and closing
   */
  let previouslyFocused: HTMLElement | null = null;

  watch(
    () => preview.isOpen.value,
    async (open) => {
      const el = root.value;

      if (!el) {
        return;
      }

      if (open) {
        previouslyFocused = document.activeElement as HTMLElement | null;
        document.documentElement.classList.add('lp-open');
        addModalAttributes(el);
        uiLayerManager().addLayer(el);
        uiLayerManager().registerShortcut(ESC_KEY, () => {
          void preview.close();
        });
        trapFocusWithin(el);
        await nextTick();
        setFocusWithin(el);
      } else {
        document.documentElement.classList.remove('lp-open');
        uiLayerManager().removeLayer(el);
        releaseFocusWithin(el);
        previouslyFocused?.focus({preventScroll: true});
        emit('closed');
      }
    }
  );
</script>

<template>
  <Teleport to="body">
    <div
      v-show="preview.isOpen.value"
      ref="root"
      class="live-preview"
      :class="{
        'live-preview--dragging': handlePressed || resizer.isResizing.value,
      }"
      :aria-label="t('Preview')"
      tabindex="-1"
      :style="resizer.style.value"
    >
      <section
        :id="editorId"
        ref="editor"
        class="lp-editor"
        :aria-label="title"
      >
        <header class="lp-editor__header">
          <craft-button
            type="button"
            size="small"
            icon="xmark"
            variant="plain"
            @click="preview.close()"
          >
            {{ t('Close Preview') }}
          </craft-button>
          <div class="lp-editor__status">
            <slot name="status"></slot>
          </div>
        </header>
        <form class="lp-editor__body" novalidate @submit.prevent="emit('save')">
          <div :id="editorTargetId"></div>
        </form>
        <footer class="lp-editor__footer">
          <slot name="footer"></slot>
        </footer>
      </section>

      <section class="lp-preview" :aria-label="t('Preview')">
        <ResizeHandle
          :resizer="resizer"
          @pointerdown="handlePressed = true"
          :label="t('Resize editor')"
          :controls="editorId"
        />
        <header class="lp-preview__toolbar">
          <craft-button-group
            name="lp-device"
            :value="preview.device.value"
            :aria-label="t('Device type')"
            @change="onDeviceChange"
          >
            <craft-button
              v-for="(label, type) in deviceLabels"
              :key="type"
              :value="type"
              icon
              :title="label"
            >
              <craft-icon :name="deviceIcons[type]" :label="label"></craft-icon>
            </craft-button>
          </craft-button-group>

          <craft-button
            type="button"
            icon="rotate"
            :disabled="preview.device.value === 'desktop'"
            :aria-label="t('Rotate')"
            :title="t('Rotate')"
            @click="rotate"
          ></craft-button>

          <div class="lp-preview__spacer"></div>

          <ActionMenu
            v-if="preview.targets.value.length > 1"
            :key="preview.activeIndex.value"
            :actions="targetActions"
            :label="preview.activeTarget.value?.label ?? null"
            :flush="false"
          >
            <template #invoker="{attributes}">
              <craft-button
                v-bind="attributes"
                type="button"
                size="small"
                icon="chevron-down"
                icon-position="suffix"
              >
                {{ preview.activeTarget.value?.label }}
              </craft-button>
            </template>
          </ActionMenu>

          <craft-button
            type="button"
            size="small"
            icon="arrows-rotate"
            :disabled="preview.isLoading.value"
            .loading="preview.isLoading.value"
            @click="preview.refresh({force: true})"
          >
            {{ t('Refresh') }}
          </craft-button>

          <template v-if="currentUrl">
            <craft-button
              :id="newTabLinkId"
              icon
              size="small"
              :href="currentUrl"
              target="_blank"
              rel="noopener"
            >
              <craft-icon
                name="arrow-up-right-from-square"
                :label="t('Open in a new tab')"
              ></craft-icon>
            </craft-button>
            <craft-tooltip :for="newTabLinkId">
              {{ t('Open in a new tab') }}
            </craft-tooltip>
          </template>
        </header>

        <div ref="stage" class="lp-preview__stage">
          <craft-callout
            v-if="preview.error.value"
            variant="danger"
            icon="triangle-exclamation"
            class="lp-preview__error"
          >
            {{ preview.error.value }}
          </craft-callout>
          <div
            class="lp-device"
            :class="{
              'lp-device--framed': layout,
              'lp-device--scrolls': resizerOptions,
            }"
            :style="deviceStyle"
          >
            <div class="lp-device__viewport" :style="viewportStyle">
              <div
                class="lp-progress"
                :class="{'lp-progress--visible': progressVisible}"
                aria-hidden="true"
              >
                <div
                  class="lp-progress__bar"
                  :style="{inlineSize: `${progress * 100}%`}"
                ></div>
              </div>
              <iframe
                v-for="frame in preview.frames.value"
                :key="frame.id"
                :ref="
                  (el) => onFrameMounted(frame, el as HTMLIFrameElement | null)
                "
                class="lp-frame"
                :class="{'lp-frame--ready': frame.ready}"
                :src="frame.url"
                :title="t('Preview')"
                :scrolling="resizerOptions ? 'no' : undefined"
                @load="
                  preview.frameLoaded(
                    frame.id,
                    $event.target as HTMLIFrameElement
                  )
                "
              ></iframe>
            </div>
          </div>
        </div>

        <div class="sr-only" role="status">{{ announcement }}</div>
      </section>
    </div>
  </Teleport>
</template>

<style scoped>
  .live-preview {
    position: fixed;
    inset: 0;
    z-index: var(--c-layer-overlay);
    display: grid;
    grid-template-columns: var(--lp-editor-width, 33%) minmax(0, 1fr);
    /* Without this the row grows to fit the editor, and the preview with it. */
    grid-template-rows: minmax(0, 1fr);
    background-color: var(--c-surface-sunken);
  }

  .lp-editor {
    display: grid;
    border-inline-end: 1px solid var(--c-color-border-quiet);
    grid-template-rows: auto minmax(0, 1fr) auto;
    min-width: 0;
    background-color: var(--c-surface-default);
  }

  .lp-editor__header,
  .lp-editor__footer,
  .lp-preview__toolbar {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    min-height: var(--cp-header-height, 3rem);
    padding-inline: var(--c-spacing-md);
  }

  .lp-editor__header {
    border-block-end: 1px solid var(--c-color-border-quiet);
  }

  .lp-editor__status {
    margin-inline-start: auto;
  }

  .lp-editor__body {
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  .lp-editor__footer {
    justify-content: flex-end;
    border-block-start: 1px solid var(--c-color-border-quiet);
  }

  .lp-preview {
    position: relative;
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    min-width: 0;
  }

  .lp-preview__toolbar {
    background-color: var(--c-surface-default);
    border-block-end: 1px solid var(--c-color-border-quiet);
  }

  .lp-preview__spacer {
    flex: 1;
  }

  .lp-progress {
    position: absolute;
    z-index: 2;
    inset-inline: 0;
    inset-block-start: 0;
    block-size: 3px;
    pointer-events: none;
    opacity: 0;
    transition: opacity 200ms ease 200ms;
  }

  .lp-progress--visible {
    opacity: 1;
    transition: none;
  }

  .lp-progress__bar {
    block-size: 100%;
    background-color: var(--c-color-accent-fill-loud);
    box-shadow: 0 0 8px var(--c-color-accent-fill-loud);
  }

  .lp-progress--visible .lp-progress__bar {
    transition: inline-size 200ms ease;
  }

  @media (prefers-reduced-motion: reduce) {
    .lp-progress,
    .lp-progress--visible .lp-progress__bar {
      transition: none;
    }
  }

  .lp-preview__stage {
    position: relative;
    overflow: hidden;
  }

  .lp-preview__error {
    position: absolute;
    inset-block-start: var(--c-spacing-md);
    inset-inline: var(--c-spacing-md);
    z-index: 1;
  }

  .lp-device {
    position: absolute;
    inset: 0;
  }

  .lp-device__viewport {
    position: relative;
    width: 100%;
    height: 100%;
    background-color: white;
  }

  .lp-device--framed {
    margin: auto;
    display: grid;
    place-items: center;
    transform-origin: center;
    border-radius: 2.5rem;
    background-color: var(--c-color-neutral-fill-loud, #1f2937);
    box-shadow: var(--c-shadow-lg);

    .lp-device__viewport {
      border-radius: 0.25rem;
      overflow: hidden;
    }
  }

  .lp-device--scrolls .lp-device__viewport {
    overflow-y: auto;
  }

  .lp-frame {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
    visibility: hidden;
  }

  .lp-frame--ready {
    visibility: visible;
  }

  .lp-device--scrolls .lp-frame {
    position: static;
    display: block;
  }

  .lp-device--scrolls .lp-frame:not(.lp-frame--ready) {
    position: absolute;
    inset-block-start: 0;
  }

  /* An iframe under the pointer takes its events, and the drag with them. */
  .live-preview--dragging .lp-frame {
    pointer-events: none;
  }
</style>

<style>
  html.lp-open {
    overflow: hidden;
  }
</style>

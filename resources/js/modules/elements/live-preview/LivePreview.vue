<script setup lang="ts">
  /**
   * Live Preview: the editor beside the element's front end, which reloads as
   * edits are autosaved.
   *
   * The editor isn't rendered here. The page's own editor content is moved into
   * the exposed `editorTarget` while this is open, so there's one form, one
   * autosave queue, and nothing to reconcile when it closes.
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
    title: string;
    /** Set when the screen is only there for the preview, which then can't close. */
    standalone?: {editUrl: string | null} | null;
  }>();

  const emit = defineEmits<{
    (e: 'save'): void;
    (e: 'closed'): void;
  }>();

  const root = useTemplateRef<HTMLElement>('root');
  const stage = useTemplateRef<HTMLElement>('stage');
  const editorTarget = useTemplateRef<HTMLElement>('editorTarget');

  /** Whether the pane is on screen, which outlasts `isOpen` while it slides out. */
  const shown = ref(false);

  // An element rather than an id, so the content follows it if it's replaced.
  defineExpose({editorTarget, shown});
  const preview = props.controller;

  /*
   * Panes
   */
  const editor = useTemplateRef<HTMLElement>('editor');
  const editorId = `lp-editor-pane-${useId()}`;
  const previewPane = useTemplateRef<HTMLElement>('previewPane');
  const previewPaneId = `lp-preview-pane-${useId()}`;

  /** Focuses the preview without the hash change following the link would make. */
  function skipToPreview(): void {
    previewPane.value?.focus();
  }
  const controlId = `lp-control-${useId()}`;
  const newTabLinkId = `${controlId}-new-tab`;
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

  watch(editor, (el) => (preview.editor.value = el), {immediate: true});
  watch(resizer.style, () => {
    if (resizer.isResizing.value) {
      preview.emit('drag');
    }
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
  // The page is resized once, at the midpoint, faded out so it doesn't jump.
  const frameShape = shallowRef<DeviceShape | null>(targetShape.value);
  const frameOpacity = ref(1);
  // How far the device is from desktop, which has no bezel, gutter or corners.
  const framing = ref(targetShape.value ? 1 : 0);
  let shapeFrame = 0;

  /** Desktop as a device: a bezel-less screen the size of the stage. */
  function stageShape(): DeviceShape {
    return {
      viewport: {width: stageWidth.value, height: stageHeight.value},
      bezel: {inline: 0, block: 0},
    };
  }

  // Animates the device's dimensions rather than its rendered size, so it's
  // fitted to the stage on every frame and never outgrows it on the way.
  watch(targetShape, (to) => {
    cancelAnimationFrame(shapeFrame);

    if (reducedMotion.value === 'reduce' || !stageWidth.value) {
      shape.value = frameShape.value = to;
      frameOpacity.value = 1;
      framing.value = to ? 1 : 0;
      return;
    }

    const from = shape.value ?? stageShape();
    const end = to ?? stageShape();
    const startOpacity = frameOpacity.value;
    const startFraming = framing.value;
    const endFraming = to ? 1 : 0;
    frameShape.value ??= from;

    const start = performance.now();
    const step = (now: number) => {
      const t = Math.min(1, (now - start) / 300);
      const eased = t < 0.5 ? 4 * t ** 3 : 1 - (-2 * t + 2) ** 3 / 2;
      shape.value = t === 1 ? to : blendShapes(from, end, eased);
      framing.value = startFraming + (endFraming - startFraming) * eased;

      if (t === 1) {
        frameShape.value = to;
      } else if (t >= 0.5) {
        frameShape.value = end;
      }
      frameOpacity.value = t < 0.5 ? startOpacity * (1 - 2 * t) : 2 * t - 1;

      if (t < 1) {
        shapeFrame = requestAnimationFrame(step);
      }
    };
    shapeFrame = requestAnimationFrame(step);
  });

  onBeforeUnmount(() => cancelAnimationFrame(shapeFrame));

  const layout = computed(() =>
    shape.value
      ? fitDevice(
          shape.value,
          {width: stageWidth.value, height: stageHeight.value},
          24 * framing.value
        )
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
      '--lp-framing': framing.value,
    };
  });

  // The page is sized for one device at a time rather than reflowing on every
  // frame of the animation; the screen clips it on the way.
  const viewportStyle = computed(() => {
    const value = layout.value;
    const target = frameShape.value?.viewport;

    return value && target
      ? {
          width: `${value.viewport.width}px`,
          height: `${value.viewport.height}px`,
          '--lp-frame-width': `${target.width}px`,
          '--lp-frame-height': `${target.height}px`,
          '--lp-frame-opacity': frameOpacity.value,
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

    if (preview.activeTarget.value) {
      preview.emit('afterUpdateIframe', {
        target: preview.activeTarget.value,
        iframe: el,
      });
    }

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
        shown.value = true;
        previouslyFocused = document.activeElement as HTMLElement | null;
        document.documentElement.classList.add('lp-open');
        addModalAttributes(el);
        uiLayerManager().addLayer(el);
        if (!props.standalone) {
          uiLayerManager().registerShortcut(ESC_KEY, () => {
            void preview.close();
          });
        }
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
    <Transition name="live-preview" @after-leave="shown = false">
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
            <a
              :href="`#${previewPaneId}`"
              class="skip-link lp-skip-link"
              @click.prevent="skipToPreview"
              >{{ t('Skip to top of preview') }}</a
            >
            <craft-button
              v-if="!standalone"
              type="button"
              size="small"
              icon="xmark"
              variant="plain"
              :aria-label="t('Close Preview')"
              @click="preview.close()"
            >
            </craft-button>
            <craft-button
              v-else-if="standalone.editUrl"
              size="small"
              icon="arrow-up-right-from-square"
              icon-position="suffix"
              variant="plain"
              :href="standalone.editUrl"
              target="_blank"
            >
              {{ t('Edit') }}
              <span class="sr-only">{{ t('Opens in a new window') }}</span>
            </craft-button>
            <div class="lp-editor__status">
              <slot name="status"></slot>
            </div>
          </header>
          <form
            class="lp-editor__body"
            novalidate
            @submit.prevent="emit('save')"
          >
            <div ref="editorTarget"></div>
          </form>
          <footer class="lp-editor__footer">
            <slot name="footer"></slot>
          </footer>
        </section>

        <section
          :id="previewPaneId"
          ref="previewPane"
          class="lp-preview"
          :aria-label="t('Preview')"
          tabindex="-1"
        >
          <ResizeHandle
            :resizer="resizer"
            @pointerdown="handlePressed = true"
            :label="t('Resize editor')"
            :controls="editorId"
          />
          <header class="lp-preview__toolbar">
            <div>
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
                    icon="chevron-down"
                    icon-position="suffix"
                  >
                    {{ preview.activeTarget.value?.label }}
                  </craft-button>
                </template>
              </ActionMenu>
            </div>

            <div class="flex gap-md items-center">
              <craft-button-group
                name="lp-device"
                :value="preview.device.value"
                :aria-label="t('Device type')"
                @change="onDeviceChange"
              >
                <craft-button
                  v-for="(label, type) in deviceLabels"
                  :id="`${controlId}-${type}`"
                  :key="type"
                  :value="type"
                  icon
                >
                  <craft-icon
                    :name="deviceIcons[type]"
                    :label="label"
                  ></craft-icon>
                </craft-button>
              </craft-button-group>
              <craft-tooltip
                v-for="(label, type) in deviceLabels"
                :key="type"
                :for="`${controlId}-${type}`"
              >
                {{ label }}
              </craft-tooltip>

              <craft-button
                :id="`${controlId}-rotate`"
                type="button"
                icon="rotate"
                :disabled="preview.device.value === 'desktop'"
                :aria-label="t('Rotate')"
                @click="rotate"
              ></craft-button>
              <craft-tooltip :for="`${controlId}-rotate`">
                {{ t('Rotate') }}
              </craft-tooltip>
            </div>

            <div class="justify-self-end">
              <craft-button
                :id="`${controlId}-refresh`"
                type="button"
                icon="arrows-rotate"
                :disabled="preview.isLoading.value"
                .loading="preview.isLoading.value"
                @click="preview.refresh({reload: true})"
              >
                {{ t('Refresh') }}
              </craft-button>
              <craft-tooltip :for="`${controlId}-refresh`">
                {{ t('Reload the preview') }}
              </craft-tooltip>

              <template v-if="currentUrl">
                <craft-button
                  :id="newTabLinkId"
                  icon
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
            </div>
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
                    (el) =>
                      onFrameMounted(frame, el as HTMLIFrameElement | null)
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

          <!-- Something to tab to after the frame. Without it, tabbing out of
          the frame's last link leaves the focus trap nowhere to go. -->
          <a
            :href="`#${previewPaneId}`"
            class="skip-link lp-skip-link"
            @click.prevent="skipToPreview"
            >{{ t('Skip to top of preview') }}</a
          >

          <div class="sr-only" role="status">{{ announcement }}</div>
        </section>
      </div>
    </Transition>
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

  /* The editor slides in from one side and the preview from the other, over
     the page as the backdrop fades in. */
  @media screen and (prefers-reduced-motion: no-preference) {
    .live-preview-enter-active,
    .live-preview-leave-active {
      transition: background-color 400ms ease;

      & > .lp-editor,
      & > .lp-preview {
        transition: translate 400ms ease;
      }
    }

    .live-preview-enter-from,
    .live-preview-leave-to {
      background-color: transparent;

      & > .lp-editor {
        translate: -100% 0;
      }

      & > .lp-preview {
        translate: 100% 0;
      }
    }

    [dir='rtl'] {
      .live-preview-enter-from,
      .live-preview-leave-to {
        & > .lp-editor {
          translate: 100% 0;
        }

        & > .lp-preview {
          translate: -100% 0;
        }
      }
    }
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

  .lp-preview:focus {
    outline: none;
  }

  .lp-skip-link {
    --x-position: var(--c-spacing-xs);
    --y-position: var(--c-spacing-xs);

    z-index: 3;
  }

  .lp-preview__toolbar {
    /* Equal sides keep the device controls centred whatever the target's label. */
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    background-color: var(--c-surface-default);
    border-block-end: 1px solid var(--c-color-border-quiet);
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
    /* Keeps the device's animation from laying out anything outside it. */
    contain: strict;
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
    display: grid;
    /* Centred even while a larger page overflows it, on the way to a bigger device. */
    grid-template: minmax(0, 1fr) / minmax(0, 1fr);
    place-items: unsafe center;
    width: 100%;
    height: 100%;
    background-color: white;
  }

  .lp-device--framed {
    margin: auto;
    display: grid;
    place-items: center;
    transform-origin: center;
    border-radius: calc(2.5rem * var(--lp-framing, 1));
    background-color: var(--c-color-neutral-fill-loud, #1f2937);
    box-shadow: var(--c-shadow-lg);

    .lp-device__viewport {
      border-radius: calc(0.25rem * var(--lp-framing, 1));
      overflow: hidden;
    }
  }

  .lp-device--scrolls .lp-device__viewport {
    align-items: start;
    overflow-y: auto;
  }

  .lp-frame {
    grid-area: 1 / 1;
    width: var(--lp-frame-width, 100%);
    height: var(--lp-frame-height, 100%);
    border: 0;
    opacity: var(--lp-frame-opacity, 1);
    visibility: hidden;
  }

  .lp-frame--ready {
    visibility: visible;
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

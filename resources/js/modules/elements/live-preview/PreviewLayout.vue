<script setup lang="ts">
  /**
   * The full-screen preview layout: a side pane beside a front-end preview with
   * its target, device and refresh controls.
   *
   * The side pane's body and footer are slotted, so Live Preview can hold the
   * element editor in it and a release preview its list of drafts. A second,
   * resizable pane can follow the preview, through the `end` slot.
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
    onMounted,
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
    isRotation,
    rotatesClockwise,
    type DeviceShape,
    type DeviceType,
  } from './devicePreview';
  import type {LivePreviewController, LivePreviewFrame} from './useLivePreview';

  /** Room each pane keeps, however far the divider is dragged. */
  const MIN_PANE_WIDTH = 320;

  const props = withDefaults(
    defineProps<{
      controller: LivePreviewController;
      /** The side pane's heading, and its accessible name. */
      title: string;
      /** Whether the preview can be closed, with its button or Escape. */
      closable?: boolean;
      /** A fixed width for the side pane, which then can't be resized. */
      sidebarWidth?: string | null;
      /**
       * Leaves out the side pane and the device controls, for screens with no
       * room for either. Whatever the side pane offered belongs in the
       * toolbar's `toolbar-start` slot instead.
       */
      compact?: boolean;
    }>(),
    {
      closable: true,
      sidebarWidth: null,
      compact: false,
    }
  );

  const emit = defineEmits<{
    (e: 'after-enter'): void;
    (e: 'after-leave'): void;
    (e: 'closed'): void;
  }>();

  defineSlots<{
    meta?(): unknown;
    actions?(): unknown;
    default(): unknown;
    footer?(): unknown;
    'toolbar-start'?(): unknown;
    'toolbar-end'?(): unknown;
    /** A pane after the preview, which it shares the width with. */
    end?(): unknown;
  }>();

  const root = useTemplateRef<HTMLElement>('root');
  const stage = useTemplateRef<HTMLElement>('stage');
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

  const end = useTemplateRef<HTMLElement>('end');
  const endId = `lp-end-pane-${useId()}`;
  /** Sized as Live Preview's editor is, so an editor here starts out the same. */
  const endResizer = useResizable({
    target: end,
    edge: 'inline-start',
    minWidth: () => Math.min(MIN_PANE_WIDTH, windowWidth.value / 2),
    maxWidth: () =>
      Math.max(MIN_PANE_WIDTH, windowWidth.value - MIN_PANE_WIDTH),
    cssVariable: '--lp-end-width',
    storageKey: 'LivePreview.width',
  });
  const {width: endWidth} = useElementSize(end, undefined, {
    box: 'border-box',
  });

  defineExpose({
    // Live Preview registers this as its editor's pane, which slideouts then
    // open over. A release preview's pane holds no editor, so it doesn't.
    pane: editor,
    /** How much of the window the end pane takes, or 0 without one. */
    endWidth: computed(() => (end.value ? endWidth.value : 0)),
  });

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
  // Compact, there are no device controls, so the page fills the stage.
  const targetShape = computed(() =>
    props.compact
      ? null
      : deviceShape(preview.device.value, preview.orientation.value)
  );
  const shape = shallowRef<DeviceShape | null>(targetShape.value);
  // The page is resized once, at the midpoint, faded out so it doesn't jump.
  const frameShape = shallowRef<DeviceShape | null>(targetShape.value);
  const frameOpacity = ref(1);
  // How far the device is from desktop, which has no gutter around it.
  const framing = ref(targetShape.value ? 1 : 0);
  // Degrees the device is turned, on its way to the other orientation.
  const rotation = ref(0);
  const rtl = ref(false);
  let shapeFrame = 0;

  onMounted(() => {
    rtl.value =
      !!stage.value && getComputedStyle(stage.value).direction === 'rtl';
  });

  /** Desktop as a device: a bezel-less screen the size of the stage. */
  function stageShape(): DeviceShape {
    return {
      viewport: {width: stageWidth.value, height: stageHeight.value},
      bezel: 0,
      radius: 0,
    };
  }

  // Animates the device's dimensions rather than its rendered size, so it's
  // fitted to the stage on every frame and never outgrows it on the way.
  watch(targetShape, (to, old) => {
    cancelAnimationFrame(shapeFrame);
    rotation.value = 0;

    if (reducedMotion.value === 'reduce' || !stageWidth.value) {
      shape.value = frameShape.value = to;
      frameOpacity.value = 1;
      framing.value = to ? 1 : 0;
      return;
    }

    // Rotating the device at rest turns it, page and all, then swaps in the
    // other orientation, which takes up the same box, and fades the page in.
    if (to && old && shape.value === old && isRotation(old, to)) {
      const fromOrientation =
        old.viewport.width < old.viewport.height ? 'portrait' : 'landscape';
      const turn = rotatesClockwise(fromOrientation, rtl.value) ? 90 : -90;
      const startOpacity = frameOpacity.value;
      const start = performance.now();
      const fadeIn = (from: number) => (now: number) => {
        const t = Math.min(1, (now - from) / 150);
        frameOpacity.value = t;

        if (t < 1) {
          shapeFrame = requestAnimationFrame(fadeIn(from));
        }
      };
      const step = (now: number) => {
        const t = Math.min(1, (now - start) / 450);
        const eased = t < 0.5 ? 4 * t ** 3 : 1 - (-2 * t + 2) ** 3 / 2;
        rotation.value = turn * eased;
        frameOpacity.value =
          t < 0.6 ? startOpacity : startOpacity * (1 - (t - 0.6) / 0.4);

        if (t < 1) {
          shapeFrame = requestAnimationFrame(step);
          return;
        }

        rotation.value = 0;
        shape.value = frameShape.value = to;
        shapeFrame = requestAnimationFrame(fadeIn(now));
      };
      shapeFrame = requestAnimationFrame(step);
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
          24 * framing.value,
          rotation.value
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
    tablet: 'custom-icons/tablet-even',
    phone: 'custom-icons/mobile-even',
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
    const current = shape.value;

    if (!value || !current) {
      return undefined;
    }

    return {
      width: `${value.frame.width}px`,
      height: `${value.frame.height}px`,
      transform: `rotate(${rotation.value}deg) scale(${value.zoom})`,
      '--lp-bezel': `${current.bezel}px`,
      '--lp-radius': `${current.radius}px`,
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

  /** Whether there's anything to preview; a selection can have no targets. */
  const hasTarget = computed(() => preview.activeTarget.value !== null);

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

  // The page inside doesn't always report a new height when the frame's width
  // changes (a device switch, the divider, the window), so it's asked to.
  const resizedFrames = new Map<number, HTMLIFrameElement>();
  const frameWidths = new WeakMap<Element, number>();
  const frameWidthObserver = new ResizeObserver((entries) => {
    for (const {target, contentRect} of entries) {
      const width = Math.round(contentRect.width);
      const previous = frameWidths.get(target);
      frameWidths.set(target, width);

      if (previous !== undefined && previous !== width) {
        (
          target as HTMLIFrameElement & {iFrameResizer?: {resize(): void}}
        ).iFrameResizer?.resize();
      }
    }
  });

  onBeforeUnmount(() => frameWidthObserver.disconnect());

  function onFrameMounted(
    frame: LivePreviewFrame,
    el: HTMLIFrameElement | null
  ): void {
    if (!el) {
      const resized = resizedFrames.get(frame.id);
      if (resized) {
        frameWidthObserver.unobserve(resized);
        resizedFrames.delete(frame.id);
      }
      return;
    }

    if (el.dataset.lpFrame === String(frame.id)) {
      return;
    }

    el.dataset.lpFrame = String(frame.id);

    if (preview.activeTarget.value) {
      preview.emit('after-update-iframe', {
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
      frameWidthObserver.observe(el);
      resizedFrames.set(frame.id, el);
    }
  }

  /*
   * Opening and closing
   */
  let previouslyFocused: HTMLElement | null = null;
  /** The element holding the page while open, until it lets go. */
  let modalLayer: HTMLElement | null = null;

  function releasePage(): void {
    if (!modalLayer) {
      return;
    }

    document.documentElement.classList.remove('lp-open');
    uiLayerManager().removeLayer(modalLayer);
    releaseFocusWithin(modalLayer);
    modalLayer = null;
  }

  watch(
    () => preview.isOpen.value,
    async (open) => {
      const el = root.value;

      if (!el) {
        return;
      }

      if (open) {
        previouslyFocused = document.activeElement as HTMLElement | null;
        modalLayer = el;
        document.documentElement.classList.add('lp-open');
        addModalAttributes(el);
        uiLayerManager().addLayer(el);
        if (props.closable) {
          uiLayerManager().registerShortcut(ESC_KEY, () => {
            void preview.close();
          });
        }
        trapFocusWithin(el);
        await nextTick();
        setFocusWithin(el);
      } else {
        releasePage();
        previouslyFocused?.focus({preventScroll: true});
        emit('closed');
      }
    }
  );

  // Leaving the page while it's open (saving redirects away, say) never closes it.
  onBeforeUnmount(releasePage);
</script>

<template>
  <Teleport to="body">
    <Transition
      name="live-preview"
      @after-enter="emit('after-enter')"
      @after-leave="emit('after-leave')"
    >
      <div
        v-show="preview.isOpen.value"
        ref="root"
        class="live-preview"
        :class="{
          'live-preview--dragging':
            handlePressed ||
            resizer.isResizing.value ||
            endResizer.isResizing.value,
          'live-preview--compact': compact,
          'live-preview--with-end': $slots.end,
        }"
        :aria-label="t('Preview')"
        tabindex="-1"
        :style="[
          sidebarWidth
            ? {'--lp-editor-width': sidebarWidth}
            : resizer.style.value,
          endResizer.style.value,
        ]"
      >
        <section
          v-if="!compact"
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
            <div class="lp-editor__heading">
              <h2 class="lp-editor__title">{{ title }}</h2>
              <slot name="meta"></slot>
            </div>
            <slot name="actions"></slot>
            <craft-button
              v-if="closable"
              type="button"
              size="small"
              icon="xmark-large"
              variant="plain"
              flush="inline-end"
              :aria-label="t('Close Preview')"
              @click="preview.close()"
            >
            </craft-button>
          </header>
          <slot></slot>
          <footer v-if="$slots.footer" class="lp-editor__footer">
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
            v-if="!compact && !sidebarWidth"
            :resizer="resizer"
            @pointerdown="handlePressed = true"
            :label="t('Resize editor')"
            :controls="editorId"
          />
          <header class="lp-preview__toolbar">
            <div class="lp-preview__toolbar-start">
              <slot name="toolbar-start">
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
                      variant="plain"
                      icon="chevron-down"
                      icon-position="suffix"
                    >
                      {{ preview.activeTarget.value?.label }}
                    </craft-button>
                  </template>
                </ActionMenu>
              </slot>
            </div>

            <div v-if="compact"></div>
            <div v-else class="flex gap-md items-center">
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
                  class="lp-device-button"
                  size="small"
                  icon
                  .disabled="!hasTarget"
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
                class="lp-rotate"
                :class="{invisible: preview.device.value === 'desktop'}"
                type="button"
                size="small"
                variant="plain"
                icon="custom-icons/device-rotate"
                :disabled="preview.device.value === 'desktop' || !hasTarget"
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
                size="small"
                variant="plain"
                icon="arrows-rotate"
                :disabled="!hasTarget || preview.isLoading.value"
                .loading="preview.isLoading.value"
                @click="preview.refresh({reload: true})"
              >
                {{ t('Refresh') }}
              </craft-button>
              <craft-tooltip :for="`${controlId}-refresh`">
                {{ t('Reload the preview') }}
              </craft-tooltip>

              <craft-button
                v-if="hasTarget && currentUrl"
                :id="newTabLinkId"
                size="small"
                variant="plain"
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
              <craft-button
                v-else
                :id="newTabLinkId"
                type="button"
                size="small"
                variant="plain"
                icon
                disabled
              >
                <craft-icon
                  name="arrow-up-right-from-square"
                  :label="t('Open in a new tab')"
                ></craft-icon>
              </craft-button>
              <craft-tooltip :for="newTabLinkId">
                {{ t('Open in a new tab') }}
              </craft-tooltip>

              <slot name="toolbar-end"></slot>

              <craft-button
                v-if="compact && closable"
                type="button"
                size="small"
                icon="xmark-large"
                variant="plain"
                flush="inline-end"
                :aria-label="t('Close Preview')"
                @click="preview.close()"
              >
              </craft-button>
            </div>
          </header>

          <div
            ref="stage"
            class="lp-preview__stage"
            :class="{'lp-preview__stage--empty': !hasTarget}"
          >
            <craft-empty
              v-if="!hasTarget"
              class="lp-preview__empty"
              :label="t('No preview available.')"
            ></craft-empty>
            <craft-callout
              v-if="preview.error.value"
              variant="danger"
              icon="triangle-exclamation"
              class="lp-preview__error"
            >
              {{ preview.error.value }}
            </craft-callout>
            <div
              v-show="hasTarget"
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

        <section
          v-if="$slots.end"
          :id="endId"
          ref="end"
          class="lp-end"
          :aria-label="t('Editor')"
        >
          <ResizeHandle
            :resizer="endResizer"
            @pointerdown="handlePressed = true"
            :label="t('Resize editor')"
            :controls="endId"
          />
          <slot name="end"></slot>
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
    grid-template-columns: minmax(0, 1fr);
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
    padding-inline: var(--cp-container-padding);
    border-block-end: 1px solid var(--c-color-border-quiet);
  }

  .lp-editor__heading {
    flex: 1;
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    min-width: 0;
  }

  .lp-editor__title {
    margin: 0;
    overflow: hidden;
    font-size: 1.125rem;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  :slotted(.lp-editor__body) {
    display: flex;
    flex-direction: column;
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
    display: grid;
    grid-template: minmax(0, 1fr) / minmax(0, 1fr);
    overflow: hidden;
    /* Keeps the device's animation from laying out anything outside it. */
    contain: strict;
  }

  .live-preview--compact {
    grid-template-columns: minmax(0, 1fr);
  }

  .live-preview--with-end {
    grid-template-columns:
      var(--lp-editor-width, 33%) minmax(0, 1fr)
      var(--lp-end-width, 33%);
  }

  .live-preview--with-end.live-preview--compact {
    grid-template-columns: minmax(0, 1fr) var(--lp-end-width, 33%);
  }

  .lp-end {
    position: relative;
    display: grid;
    grid-template: minmax(0, 1fr) / minmax(0, 1fr);
    min-width: 0;
    border-inline-start: 1px solid var(--c-color-border-quiet);
    background-color: var(--c-surface-default);
  }

  .live-preview--compact .lp-preview__toolbar {
    grid-template-columns: minmax(0, 1fr) 0 auto;
  }

  .lp-preview__toolbar-start {
    min-width: 0;
  }

  .lp-preview__stage--empty {
    background-color: var(--c-surface-sunken);
  }

  .lp-preview__empty {
    place-self: center;
  }

  .lp-preview__error {
    position: absolute;
    inset-block-start: var(--c-spacing-md);
    inset-inline: var(--c-spacing-md);
    z-index: 1;
  }

  .lp-rotate:dir(rtl)::part(prefix) {
    scale: -1 1;
  }

  .lp-device-button {
    inline-size: calc(var(--c-size-control-sm) * 1.25);
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
    /* Unlike auto margins, this keeps a device that's wider than the stage centered. */
    position: relative;
    inset: auto;
    grid-area: 1 / 1;
    place-self: unsafe center;
    display: grid;
    place-items: center;
    transform-origin: center;
    border-radius: var(--lp-radius);
    background-color: var(--c-color-neutral-fill-loud, #1f2937);
    box-shadow: var(--c-shadow-lg);

    .lp-device__viewport {
      border-radius: calc(var(--lp-radius) - var(--lp-bezel));
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

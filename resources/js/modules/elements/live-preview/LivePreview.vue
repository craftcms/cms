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
  import {useElementSize, useWindowSize} from '@vueuse/core';
  import {computed, nextTick, ref, useTemplateRef, watch} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {uiLayerManager} from '@/common/slideouts/panel-stack';
  import {deviceLayout, type DeviceType} from './devicePreview';
  import {
    MIN_PANE_WIDTH,
    type LivePreviewController,
    type LivePreviewFrame,
  } from './useLivePreview';

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
  const {width: windowWidth} = useWindowSize();
  const editorWidthPx = computed(() => {
    const stored = preview.editorWidth.value;
    const px = stored <= 1 ? stored * windowWidth.value : stored;
    const max = Math.max(MIN_PANE_WIDTH, windowWidth.value - MIN_PANE_WIDTH);

    return Math.round(Math.min(max, Math.max(MIN_PANE_WIDTH, px)));
  });

  const dragging = ref(false);

  function isRtl(): boolean {
    return document.documentElement.dir === 'rtl';
  }

  function onHandlePointerDown(event: PointerEvent): void {
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
    dragging.value = true;
  }

  function onHandlePointerMove(event: PointerEvent): void {
    if (!dragging.value) {
      return;
    }

    const px = isRtl() ? windowWidth.value - event.clientX : event.clientX;
    // Kept as a share of the window, so it scales with it.
    preview.editorWidth.value = px / windowWidth.value;
  }

  function onHandlePointerUp(event: PointerEvent): void {
    (event.currentTarget as HTMLElement).releasePointerCapture(event.pointerId);
    dragging.value = false;
  }

  function onHandleKeydown(event: KeyboardEvent): void {
    const step = event.shiftKey ? 64 : 16;
    const grow = isRtl() ? 'ArrowLeft' : 'ArrowRight';
    const shrink = isRtl() ? 'ArrowRight' : 'ArrowLeft';

    if (event.key !== grow && event.key !== shrink) {
      return;
    }

    event.preventDefault();
    const px = editorWidthPx.value + (event.key === grow ? step : -step);
    preview.editorWidth.value = px / windowWidth.value;
  }

  /*
   * Devices
   */
  const {width: stageWidth, height: stageHeight} = useElementSize(stage);
  const layout = computed(() =>
    deviceLayout(preview.device.value, preview.orientation.value, {
      width: stageWidth.value,
      height: stageHeight.value,
    })
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
      :class="{'live-preview--dragging': dragging}"
      :aria-label="t('Preview')"
      tabindex="-1"
      :style="{'--lp-editor-width': `${editorWidthPx}px`}"
    >
      <section class="lp-editor" :aria-label="title">
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

      <div
        class="lp-handle"
        role="separator"
        aria-orientation="vertical"
        :aria-label="t('Resize editor')"
        :aria-valuenow="editorWidthPx"
        :aria-valuemin="MIN_PANE_WIDTH"
        :aria-valuemax="Math.max(MIN_PANE_WIDTH, windowWidth - MIN_PANE_WIDTH)"
        tabindex="0"
        @pointerdown="onHandlePointerDown"
        @pointermove="onHandlePointerMove"
        @pointerup="onHandlePointerUp"
        @pointercancel="onHandlePointerUp"
        @keydown="onHandleKeydown"
      ></div>

      <section class="lp-preview" :aria-label="t('Preview')">
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
              size="small"
              :aria-label="label"
              :title="label"
            >
              <craft-icon slot="prefix" :name="deviceIcons[type]"></craft-icon>
            </craft-button>
          </craft-button-group>

          <craft-button
            type="button"
            size="small"
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
            @click="preview.refresh({force: true})"
          >
            {{ t('Refresh') }}
          </craft-button>

          <craft-button
            v-if="currentUrl"
            :href="currentUrl"
            target="_blank"
            size="small"
            icon="arrow-up-right-from-square"
            :title="t('Open in a new tab')"
          >
            <span class="sr-only">{{ t('Open in a new tab') }}</span>
          </craft-button>
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
    grid-template-columns: var(--lp-editor-width) auto minmax(0, 1fr);
    background-color: var(--c-surface-sunken);
  }

  .lp-editor {
    display: grid;
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

  .lp-handle {
    width: 6px;
    cursor: col-resize;
    touch-action: none;
    background-color: var(--c-color-border-quiet);

    &:hover,
    &:focus-visible {
      background-color: var(--c-color-accent-fill-loud, currentColor);
    }
  }

  .lp-preview {
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
    transition:
      width 300ms,
      height 300ms;

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
  .live-preview--dragging {
    cursor: col-resize;
    user-select: none;

    .lp-frame {
      pointer-events: none;
    }
  }
</style>

<style>
  html.lp-open {
    overflow: hidden;
  }
</style>

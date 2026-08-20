<script setup lang="ts">
  import {onKeyStroke, useElementSize, useEventListener} from '@vueuse/core';
  import {computed, onScopeDispose, ref, shallowRef, watch} from 'vue';
  import {BaseDrag, ResizeHandle} from '@craftcms/garnish';
  import {t} from '@craftcms/ui';
  import {useBodyScrollLock} from '@/common/composables/useBodyScrollLock';

  export interface ModalProps {
    isActive?: boolean;
    overlay?: boolean;
    width?: string;
    height?: string;
    maxHeight?: string;
    /** Adds a corner handle for dragging the modal to a new size. */
    resizable?: boolean;
  }

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'opened', el: Element): void;
  }>();

  const props = withDefaults(defineProps<ModalProps>(), {
    isActive: false,
    overlay: true,
    width: 'md',
    resizable: false,
  });

  onKeyStroke('Escape', () => {
    emit('close');
  });

  // The page behind the overlay shouldn't scroll out from under it.
  useBodyScrollLock(() => props.isActive);

  const widthClass = computed(() => {
    return `w-${props.width}`;
  });

  const contentStyle = computed(() => {
    const viewportCap = 'calc(100vh - (var(--c-spacing-lg) * 2))';
    const style: Record<string, string> = {};
    if (props.height) {
      style.height = `min(${props.height}, ${viewportCap})`;
    }
    if (props.maxHeight) {
      style.maxHeight = `min(${props.maxHeight}, ${viewportCap})`;
    }
    // A dragged size wins over the width class and the height prop.
    if (resizedWidth.value !== null) {
      style.width = `${resizedWidth.value}px`;
    }
    if (resizedHeight.value !== null) {
      style.height = `${resizedHeight.value}px`;
    }
    // Only meaningful while the height is content-driven — an explicit one
    // already fixes the box.
    if (floorHeight.value !== null && !style.height) {
      style.minHeight = `${floorHeight.value}px`;
    }
    return Object.keys(style).length ? style : undefined;
  });

  // --- Resizing -------------------------------------------------------------
  //
  // Mirrors Garnish's resizable Modal: a BaseDrag on a corner handle, growing
  // the modal by twice the pointer delta because it stays centered, so both
  // edges move. The upper bound comes from the CSS max-width/max-height rather
  // than a hard-coded gutter — clamping against it keeps a drag back from the
  // edge responsive instead of unwinding invisible overshoot first.

  /** Smallest size a drag can leave the modal at, in px. Garnish parity. */
  const MIN_SIZE = 200;
  /** Arrow-key increment, in px. */
  const STEP = 16;
  const LARGE_STEP = 64;

  const content = shallowRef<HTMLElement | null>(null);
  const handle = shallowRef<HTMLElement | null>(null);
  const resizedWidth = ref<number | null>(null);
  const resizedHeight = ref<number | null>(null);

  function bounds(el: HTMLElement): {width: number; height: number} {
    const styles = getComputedStyle(el);

    // max-width/max-height compute to px even when authored as calc(), which
    // the custom properties they're built from do not.
    return {
      width: parseFloat(styles.maxWidth) || window.innerWidth,
      height: parseFloat(styles.maxHeight) || window.innerHeight,
    };
  }

  // --- Height floor ---------------------------------------------------------
  //
  // A modal whose content shrinks — swapping a source's settings for a new
  // heading's single field — would otherwise collapse under whoever is reading
  // it. Remember the tallest it has been while open and refuse to go below it.
  // It only ever grows, and resets when the modal closes, so reopening starts
  // from the new content again.

  const floorHeight = ref<number | null>(null);
  // Border-box, so the floor matches the rendered box the cap is measured
  // against — a content-box floor sits a border short and lets it creep down.
  const {height: contentHeight} = useElementSize(content, undefined, {
    box: 'border-box',
  });

  watch(contentHeight, (height) => {
    if (!props.isActive || !height) return;

    raiseFloor(Math.round(height));
  });

  watch(
    () => props.isActive,
    (active) => {
      if (!active) floorHeight.value = null;
    }
  );

  function raiseFloor(height: number): void {
    const cap = content.value ? bounds(content.value).height : Infinity;
    // min-height beats max-height, so the floor has to respect the cap itself
    // or a shrinking viewport would leave the modal taller than the screen.
    const next = Math.min(height, cap);

    if (floorHeight.value === null || next > floorHeight.value) {
      floorHeight.value = next;
    }
  }

  function resize(width: number, height: number): void {
    const el = content.value;
    if (!el) return;

    const max = bounds(el);
    resizedWidth.value = Math.round(
      Math.min(Math.max(width, MIN_SIZE), max.width)
    );
    resizedHeight.value = Math.round(
      Math.min(Math.max(height, MIN_SIZE), max.height)
    );
  }

  function measured(): {width: number; height: number} {
    const rect = content.value?.getBoundingClientRect();

    return {
      width: Math.round(rect?.width ?? 0),
      height: Math.round(rect?.height ?? 0),
    };
  }

  function nudge(deltaX: number, deltaY: number): void {
    // Build on the last size we asked for, not the rendered one: key repeats
    // outrun Vue's DOM updates, so measuring every time would have them all read
    // the same stale box and overwrite each other.
    const size = measured();
    const width = resizedWidth.value ?? size.width;
    const height = resizedHeight.value ?? size.height;
    const rtl = content.value
      ? getComputedStyle(content.value).direction === 'rtl'
      : false;

    resize(width + (rtl ? -deltaX : deltaX), height + deltaY);
  }

  function onHandleKeydown(ev: KeyboardEvent): void {
    const step = ev.shiftKey ? LARGE_STEP : STEP;

    switch (ev.key) {
      case 'ArrowLeft':
        nudge(-step, 0);
        break;
      case 'ArrowRight':
        nudge(step, 0);
        break;
      case 'ArrowUp':
        nudge(0, -step);
        break;
      case 'ArrowDown':
        nudge(0, step);
        break;
      case 'Enter':
        reset();
        break;
      default:
        return;
    }

    ev.preventDefault();
  }

  /** Hands the size back to CSS. Wired to double-click and Enter. */
  function reset(): void {
    resizedWidth.value = null;
    resizedHeight.value = null;
  }

  let dragger: BaseDrag | null = null;
  let startWidth = 0;
  let startHeight = 0;
  let startDistX = 0;
  let startDistY = 0;
  let sign = 1;

  watch(handle, (el) => {
    dragger?.destroy();
    dragger = null;

    if (!el) return;

    dragger = new BaseDrag(el, {
      // The default selector list would swallow pointer-downs on the handle's
      // own SVG; the handle *is* the control here.
      ignoreHandleSelector: null,
      onBeforeDragStart: () => {
        // Sync, unlike onDragStart, so we measure what was on screen when the
        // drag threshold was crossed, and discount the distance already
        // travelled so the modal doesn't jump by it.
        const size = measured();
        startWidth = size.width;
        startHeight = size.height;
        startDistX = dragger?.mouseDistX ?? 0;
        startDistY = dragger?.mouseDistY ?? 0;
        sign =
          content.value && getComputedStyle(content.value).direction === 'rtl'
            ? -1
            : 1;
      },
      onDrag: () => {
        const dx = ((dragger?.mouseDistX ?? 0) - startDistX) * sign;
        const dy = (dragger?.mouseDistY ?? 0) - startDistY;

        // Centered, so each edge moves by the pointer delta.
        resize(startWidth + dx * 2, startHeight + dy * 2);
      },
    });
  });

  // A smaller viewport lowers the cap; re-clamp so dragging back responds at
  // once rather than after the overshoot unwinds.
  useEventListener(window, 'resize', () => {
    if (resizedWidth.value !== null && resizedHeight.value !== null) {
      resize(resizedWidth.value, resizedHeight.value);
    }

    if (floorHeight.value !== null && content.value) {
      floorHeight.value = Math.min(
        floorHeight.value,
        bounds(content.value).height
      );
    }
  });

  onScopeDispose(() => {
    dragger?.destroy();
    dragger = null;
  });
</script>

<template>
  <Transition name="body" @after-enter="(el) => emit('opened', el as Element)">
    <div class="cp-modal" v-if="isActive">
      <div
        ref="content"
        :class="{
          content: true,
          [widthClass]: true,
        }"
        :style="contentStyle"
      >
        <slot></slot>
      </div>
      <button
        v-if="resizable"
        ref="handle"
        type="button"
        class="resizehandle"
        :aria-label="t('Resize')"
        @keydown="onHandleKeydown"
        @dblclick="reset"
        v-html="ResizeHandle"
      ></button>
    </div>
  </Transition>

  <Transition name="fade" v-if="overlay">
    <div class="cp-overlay" v-if="isActive" @click="emit('close')"></div>
  </Transition>
</template>

<style scoped>
  .content {
    max-width: calc(100vw - (var(--c-spacing-lg) * 2));
    max-height: calc(100vh - (var(--c-spacing-lg) * 2));
    box-shadow: var(--c-modal-shadow);
    -webkit-overflow-scrolling: touch;
    border-radius: var(--c-modal-radius);
    border-width: var(--c-modal-border-width);
    border-style: var(--c-modal-border-style);
    border-color: var(--c-modal-border-color);
    position: relative;
    overflow-y: scroll;
    pointer-events: auto;
    /* Keeps the slotted content's own z-indexes — a sticky pane footer, say —
     from painting over the modal's chrome, such as the resize handle. Nothing
     can escape this box visually anyway; it scrolls. */
    isolation: isolate;
  }

  .cp-modal,
  .cp-overlay {
    position: fixed;
    width: 100vw;
    height: 100vh;
    inset: 0;
  }

  .cp-modal {
    z-index: 10002;
    display: grid;
    justify-content: center;
    /* Content-sized in both axes, matching what justify-content does for the
     column, so the resize handle's `align-self: end` lands on the content's
     edge rather than the viewport's. */
    align-content: center;
    align-items: center;
    pointer-events: none;
  }

  /* Overlaid on the content's own grid cell so the handle can sit in its corner
   without joining the scrolling box. */
  .cp-modal > * {
    grid-area: 1 / 1;
  }

  .resizehandle {
    align-self: end;
    justify-self: end;
    /* Positioned so the z-index reliably lifts it above `.content`, which is
     itself positioned and would otherwise paint over the corner. */
    position: relative;
    z-index: 1;
    width: 24px;
    height: 24px;
    padding: var(--c-spacing-xs);
    border: none;
    background: none;
    cursor: nwse-resize;
    touch-action: none;
    pointer-events: auto;
  }

  .resizehandle :deep(path) {
    fill: var(--c-text-quiet);
  }

  .resizehandle :deep(svg.rtl) {
    display: none;
  }

  [dir='rtl'] .resizehandle :deep(svg.ltr) {
    display: none;
  }

  [dir='rtl'] .resizehandle :deep(svg.rtl) {
    display: block;
  }

  .cp-overlay {
    /**
    Action menu items are z-index 10000, so we want to be above that
    @TODO make this less fragile/weird
     */
    z-index: 10001;
    background-color: rgba(0, 0, 0, 0.5);
  }

  /* Only animate when the user is cool with it */
  @media (prefers-reduced-motion: no-preference) {
    .body-enter-active {
      animation: body-in 250ms;
    }
    .body-leave-active {
      animation: body-in 250ms reverse;
    }

    @keyframes body-in {
      0% {
        opacity: 0;
        transform: scale(0.9) translateY(2rem);
      }
      100% {
        opacity: 1;
        transform: scale(1) translateY(0);
      }
    }

    .fade-enter-active,
    .fade-leave-active {
      transition: opacity 0.1s ease;
    }

    .fade-enter-from,
    .fade-leave-to {
      opacity: 0;
    }
  }
</style>

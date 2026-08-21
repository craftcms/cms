<script setup lang="ts">
  import {onKeyStroke} from '@vueuse/core';
  import {computed, shallowRef} from 'vue';
  import {ResizeHandle} from '@craftcms/garnish';
  import {t} from '@craftcms/ui';
  import {useBodyScrollLock} from '@/common/composables/useBodyScrollLock';
  import {useResizableBox} from '@/common/composables/useResizableBox';

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

  const content = shallowRef<HTMLElement | null>(null);
  const {
    width: resizedWidth,
    height: resizedHeight,
    floor: floorHeight,
    setHandle,
    onKeydown: onHandleKeydown,
    reset,
  } = useResizableBox({target: content, active: () => props.isActive});

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
        :ref="(el) => setHandle(el as HTMLElement | null)"
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
  /* A column flex box so slotted content can grow into a height the floor is
   holding open, instead of sitting at its natural height with a gap below. */
  .content {
    display: flex;
    flex-direction: column;
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

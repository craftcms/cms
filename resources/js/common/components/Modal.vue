<script lang="ts">
  import {shallowReactive} from 'vue';

  /**
   * The open modals, innermost last. A modal opened from inside another — an
   * icon picker in a page's settings, say — stacks on top of it.
   */
  const openModals = shallowReactive<symbol[]>([]);
</script>

<script setup lang="ts">
  import {usePreferredReducedMotion} from '@vueuse/core';
  import {computed, onBeforeUnmount, shallowRef, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import CraftDialog from '@craftcms/ui/components/dialog/dialog';
  import CornerResizeHandle from '@/common/components/CornerResizeHandle.vue';
  import {useResizableBox} from '@/common/composables/useResizableBox';

  export interface ModalProps {
    isActive?: boolean;
    /** Accessible name; ModalForm supplies its visible title. */
    label?: string;
    overlay?: boolean;
    width?: string;
    height?: string;
    maxHeight?: string;
    /** Adds a corner handle for dragging the modal to a new size. */
    resizable?: boolean;
    /**
     * Whether Escape and a click on the overlay close it. Off for a modal
     * holding work that closing would throw away — only its own buttons do.
     */
    dismissible?: boolean;
  }

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'opened', el: Element): void;
  }>();

  const props = withDefaults(defineProps<ModalProps>(), {
    isActive: false,
    overlay: true,
    width: 'md',
    label: () => t('Dialog'),
    resizable: false,
    dismissible: true,
  });

  const stackId = Symbol('modal');
  const dialog = shallowRef<CraftDialog | null>(null);
  const reducedMotion = usePreferredReducedMotion();
  const transitionDuration = computed(() =>
    reducedMotion.value === 'reduce' ? 0 : 250
  );
  let invoker: HTMLElement | null = null;

  function closeDialog(element = dialog.value): void {
    if (!element) return;

    const returnTo = invoker;
    invoker = null;
    element.opened = false;
    void element.updateComplete.then(() => {
      if ((!props.isActive || !element.isConnected) && returnTo?.isConnected) {
        returnTo.focus();
      }
    });
  }

  function leaveStack(): void {
    const index = openModals.indexOf(stackId);
    if (index !== -1) openModals.splice(index, 1);
  }

  watch(
    () => props.isActive,
    (active) => {
      if (active) {
        leaveStack();
        openModals.push(stackId);
        if (invoker) return;

        const focused = document.activeElement;
        invoker =
          focused instanceof HTMLElement
            ? (focused
                .closest('craft-action-menu')
                ?.querySelector<HTMLElement>('[slot="invoker"]') ?? focused)
            : null;
      }
    },
    {immediate: true, flush: 'sync'}
  );
  onBeforeUnmount(() => {
    leaveStack();
    closeDialog();
  });

  /** Whether this is the innermost open modal — the one Escape is for. */
  const isTop = computed(() => openModals.at(-1) === stackId);

  /**
   * Whether this modal opened over another. The one beneath already dims the
   * page, so a second shade would only darken it further; this one's overlay
   * still catches clicks, just without the color.
   */
  const isNested = computed(() => openModals.indexOf(stackId) > 0);

  function requestClose(event: Event): void {
    if (
      event.composedPath().find((node) => node instanceof CraftDialog) !==
      dialog.value
    )
      return;

    event.preventDefault();
    event.stopPropagation();
    if (props.isActive && props.dismissible && isTop.value) emit('close');
  }

  function opened(): void {
    if (content.value) {
      emit('opened', content.value);
    }
  }

  function closed(element: Element): void {
    leaveStack();
    if (element instanceof CraftDialog) closeDialog(element);
  }

  const content = shallowRef<HTMLElement | null>(null);
  const resizer = useResizableBox({
    target: content,
    active: () => props.isActive,
    fixedHeight: () => Boolean(props.height),
  });

  const widthClass = computed(() => {
    return `w-${props.width}`;
  });

  /**
   * This modal's own contribution to the box. The resizer's styles are bound
   * after it, and later entries in a `:style` array win — that is what lets a
   * dragged size beat the width class and the height prop.
   */
  const contentStyle = computed(() => {
    const viewportCap = 'calc(100vh - (var(--c-spacing-lg) * 2))';
    const style: Record<string, string> = {};
    if (props.height) {
      style.height = `min(${props.height}, ${viewportCap})`;
    }
    if (props.maxHeight) {
      style.maxHeight = `min(${props.maxHeight}, ${viewportCap})`;
    }

    return Object.keys(style).length ? style : undefined;
  });
</script>

<template>
  <Transition
    name="body"
    :duration="transitionDuration"
    @after-enter="opened"
    @after-leave="closed"
  >
    <craft-dialog
      v-if="isActive"
      ref="dialog"
      :label="label"
      .opened="true"
      no-close
      .closeOnOutsideClick="dismissible"
      :class="{'without-overlay': !overlay || isNested}"
      @craft-before-hide="requestClose"
      @keydown.esc="requestClose"
    >
      <div class="cp-modal">
        <div
          ref="content"
          :class="{content: true, [widthClass]: true}"
          :style="[contentStyle, resizer.style.value]"
        >
          <slot></slot>
        </div>
        <CornerResizeHandle v-if="resizable" :resizer="resizer" />
      </div>
    </craft-dialog>
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
    background-color: var(--c-modal-fill);
    box-shadow: var(--c-modal-shadow);
    -webkit-overflow-scrolling: touch;
    border-radius: var(--c-modal-radius);
    border-width: var(--c-modal-border-width);
    border-style: var(--c-modal-border-style);
    border-color: var(--c-modal-border-color);
    position: relative;
    overflow-y: scroll;
    /* Keeps the slotted content's own z-indexes — a sticky pane footer, say —
     from painting over the modal's chrome, such as the resize handle. Nothing
     can escape this box visually anyway; it scrolls. */
    isolation: isolate;
  }

  craft-dialog::part(header) {
    display: none;
  }

  craft-dialog::part(surface) {
    display: block;
    min-inline-size: 0;
    max-inline-size: none;
    max-block-size: none;
    background: none;
    box-shadow: none;
    overflow: visible;
  }

  craft-dialog::part(body) {
    padding: 0;
    overflow: visible;
  }

  craft-dialog.without-overlay::part(dialog)::backdrop {
    background: transparent;
  }

  .cp-modal {
    display: grid;
    justify-content: center;
    align-content: center;
    align-items: center;
  }

  /* Overlaid on the content's own grid cell so the handle can sit in its corner
   without joining the scrolling box. */
  .cp-modal > * {
    grid-area: 1 / 1;
  }

  /* Where the handle sits is this modal's business; how it looks is its own. */
  .corner-resize-handle {
    align-self: end;
    justify-self: end;
    /* Positioned so the z-index reliably lifts it above `.content`, which is
     itself positioned and would otherwise paint over the corner. */
    position: relative;
    z-index: 1;
  }

  @media (prefers-reduced-motion: no-preference) {
    craft-dialog.body-enter-active::part(dialog),
    craft-dialog.body-leave-active::part(dialog) {
      transition:
        opacity 250ms,
        transform 250ms;
    }

    craft-dialog.body-enter-from::part(dialog),
    craft-dialog.body-leave-to::part(dialog) {
      opacity: 0;
      transform: scale(0.9) translateY(2rem);
    }

    craft-dialog.body-enter-active::part(dialog)::backdrop,
    craft-dialog.body-leave-active::part(dialog)::backdrop {
      transition: opacity 100ms;
    }

    craft-dialog.body-enter-from::part(dialog)::backdrop,
    craft-dialog.body-leave-to::part(dialog)::backdrop {
      opacity: 0;
    }
  }
</style>

<script setup lang="ts">
  /**
   * The corner grip for a {@link useResizableBox} box — a modal.
   *
   * The sibling {@link ResizeHandle} is the other shape: an edge divider for a
   * layout column, reporting a width to assistive tech as a window splitter. A
   * corner grip resizes both axes at once, so there is no single value to
   * report and no `separator` role that fits. It stays a plain button, named
   * for what it does, driven by arrow keys with Enter to reset.
   *
   * Where it sits is the caller's job — it styles itself and nothing else.
   */
  import {ResizeHandle as resizeHandleSvg} from '@craftcms/garnish';
  import {t} from '@craftcms/ui';
  import type {ResizeHandleControls} from '@/common/composables/resizeHandle';

  const props = withDefaults(
    defineProps<{
      /** The resizer this handle drives. Must be a stable object. */
      resizer: ResizeHandleControls;
      /** Accessible name, e.g. "Resize dialog". */
      label?: string;
    }>(),
    {label: undefined}
  );

  const {setHandle, onKeydown, reset} = props.resizer;
</script>

<template>
  <button
    :ref="(el) => setHandle(el as HTMLElement | null)"
    type="button"
    class="corner-resize-handle"
    :aria-label="label ?? t('Resize')"
    @keydown="onKeydown"
    @dblclick="reset"
    v-html="resizeHandleSvg"
  ></button>
</template>

<style scoped lang="css">
  .corner-resize-handle {
    width: 24px;
    height: 24px;
    padding: var(--c-spacing-xs);
    border: none;
    background: none;
    cursor: nwse-resize;
    /* Let the pointer, not the browser's scroll gesture, drive touch drags. */
    touch-action: none;
  }

  .corner-resize-handle:focus-visible {
    outline: 2px solid var(--c-color-accent-border-loud);
    outline-offset: -2px;
    border-radius: var(--c-radius-sm);
  }

  /* The icon ships both directions in one string; CSS picks between them. */
  .corner-resize-handle :deep(path) {
    fill: var(--c-text-quiet);
  }

  .corner-resize-handle :deep(svg.rtl) {
    display: none;
  }

  [dir='rtl'] .corner-resize-handle :deep(svg.ltr) {
    display: none;
  }

  [dir='rtl'] .corner-resize-handle :deep(svg.rtl) {
    display: block;
  }
</style>

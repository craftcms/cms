<script setup lang="ts">
    /**
     * The draggable divider for a {@link useResizable} column.
     *
     * Renders the WAI-ARIA window splitter: a focusable `separator` reporting the
     * column's width through `aria-valuenow`/`valuemin`/`valuemax`, driven by
     * arrow keys (Shift for a bigger step), Home/End for the bounds, and Enter to
     * return to the default width — the keyboard counterpart to double-clicking.
     *
     * Positioning is the caller's job: the handle is absolutely positioned and
     * stretches the block axis, so give it an inline offset and make sure it has
     * a positioned ancestor.
     */
    import type {UseResizableReturn} from '@/common/composables/useResizable';

    const props = defineProps<{
        /** The resizer this handle drives. Must be a stable object. */
        resizer: UseResizableReturn;
        /** Accessible name, e.g. "Resize details sidebar". */
        label: string;
        /** `id` of the element being resized. */
        controls?: string;
    }>();

    // Pulled out so the template gets automatic ref unwrapping.
    const {
        isResizing,
        valueNow,
        minWidth,
        maxWidth,
        setHandle,
        onKeydown,
        reset,
    } = props.resizer;
</script>

<template>
    <div
        :ref="(el) => setHandle(el as HTMLElement | null)"
        class="resize-handle"
        :class="{'resize-handle--active': isResizing}"
        role="separator"
        tabindex="0"
        aria-orientation="vertical"
        :aria-label="label"
        :aria-controls="controls"
        :aria-valuenow="valueNow"
        :aria-valuemin="minWidth"
        :aria-valuemax="maxWidth"
        @keydown="onKeydown"
        @dblclick="reset"
    >
        <span class="resize-handle__grip" aria-hidden="true"></span>
    </div>
</template>

<style scoped lang="css">
    .resize-handle {
        position: absolute;
        inset-block: 0;
        inline-size: calc(12rem / 16);
        /* Set --resize-handle-display: none from the caller to drop the handle in
       layouts that have no track to resize, without a specificity fight
       between this scoped style and the caller's. */
        display: var(--resize-handle-display, flex);
        align-items: stretch;
        justify-content: center;
        cursor: col-resize;
        /* Let the pointer, not the browser's scroll gesture, drive touch drags. */
        touch-action: none;
        border-radius: var(--c-radius-sm);
    }

    .resize-handle:focus-visible {
        outline: 2px solid var(--c-color-accent-border-loud);
        outline-offset: -2px;
    }

    .resize-handle__grip {
        inline-size: 2px;
        border-radius: 1px;
        background-color: transparent;
        transition: background-color 100ms ease-in-out;
    }

    .resize-handle:hover .resize-handle__grip,
    .resize-handle:focus-visible .resize-handle__grip {
        background-color: var(--c-color-neutral-border-normal);
    }

    .resize-handle--active .resize-handle__grip {
        background-color: var(--c-color-accent-border-loud);
    }

    @media (prefers-reduced-motion: reduce) {
        .resize-handle__grip {
            transition: none;
        }
    }
</style>

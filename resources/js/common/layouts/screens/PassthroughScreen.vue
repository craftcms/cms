<script setup lang="ts">
    /**
     * A shell that renders content without chrome.
     *
     * Both real shells provide this as the shell for anything they render, so a
     * page that renders `<AppLayout>` inline while already inside a shell
     * contributes its content instead of stacking a second set of CP chrome.
     * That happens whenever such a page is opened in a slideout.
     *
     * Its props aren't dropped: they're forwarded to the enclosing shell's props
     * store, so `<AppLayout :title="…" :form="…">` still configures the real
     * shell around it. Save events bubble the same way.
     */
    import {onScopeDispose, watchEffect} from 'vue';
    import {useScreenPropsStore} from '@/common/composables/screen';
    import type {FormSaveOptions} from '@/common/types';
    import type {ScreenProps, ScreenSlots} from './types';

    const emit = defineEmits<{
        (e: 'save', options?: FormSaveOptions): void;
    }>();

    const props = defineProps<ScreenProps>();

    defineSlots<ScreenSlots>();

    const store = useScreenPropsStore();

    watchEffect(() => {
        if (!store) {
            return;
        }

        store.set(
            Object.fromEntries(
                Object.entries(props).filter(([, value]) => value !== undefined)
            )
        );
    });

    // Re-emit the real shell's save so it reaches the page's `@save` listener on
    // the inline `<AppLayout>` between us.
    if (store) {
        onScopeDispose(
            store.onSave((options) => emit('save', options as FormSaveOptions))
        );
    }
</script>

<template>
    <slot name="main">
        <slot name="error-summary"></slot>
        <slot name="content-notice"></slot>
        <slot name="tabs"></slot>
        <slot></slot>
        <slot name="content-footer"></slot>
    </slot>
</template>

<script setup lang="ts">
    import ElementEditor from '@/modules/elements/components/ElementEditor.vue';
    import ElementEditScreen from '@/modules/elements/components/ElementEditScreen.vue';
    import {useIsSlideout} from '@/common/composables/screen';

    // Full pages render `ElementEditScreen`, which fills the shell's `main` slot
    // and so owns the whole main region. A slideout panel brings its own header,
    // form and footer, so this stays on the layout-slot editor there.
    //
    // Inline `<AppLayout>` (inside `ElementEditScreen`), so no ambient layout.
    defineOptions({layout: []});

    // The shared edit payload comes from the ElementEditor pipeline; only the
    // User-specific keys (UserEditViewModel) remain props.
    const props = defineProps<{
        userId: number | null;
        redirectUrl: string | null;
    }>();

    const editor = useIsSlideout() ? ElementEditor : ElementEditScreen;

    // What `users/save-user` resolves the account from. Everything else — the
    // native fields (username, email, full name, photo) and any custom fields —
    // comes from the compiled field layout.
    const saveData = () => ({
        userId: props.userId,
    });
</script>

<template>
    <component :is="editor" :save-data="saveData" />
</template>

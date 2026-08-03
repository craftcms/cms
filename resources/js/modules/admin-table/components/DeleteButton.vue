<script setup lang="ts">
    import {t} from '@craftcms/ui/utilities/translate';

    const emit = defineEmits<{
        (e: 'click'): void;
    }>();
    withDefaults(
        defineProps<{
            label?: string;
            icon?: string;
        }>(),
        {label: t('Delete item'), icon: 'x'}
    );
</script>

<template>
    <craft-button
        type="button"
        @click="emit('click')"
        size="small"
        variant="danger-plain"
        v-bind="$attrs"
    >
        <craft-icon :name="icon" :label="label"></craft-icon>
    </craft-button>
</template>

<style scoped lang="scss"></style>

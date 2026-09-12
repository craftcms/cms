<script setup lang="ts">
    import type {UrlMethodPair} from '@inertiajs/core';
    import FormPage from '@/pages/Form.vue';
    import type {FormPayload} from '@/modules/forms/types';

    defineProps<{
        form: FormPayload;
        submit: UrlMethodPair;
    }>();
</script>

<template>
    <FormPage :form="form" :submit="submit" />
</template>

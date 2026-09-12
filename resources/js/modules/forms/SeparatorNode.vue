<script setup lang="ts">
    import type {FormNodePayload} from './types';

    defineProps<{node: FormNodePayload}>();
</script>

<template>
    <hr :data-form-node="node.uid" />
</template>

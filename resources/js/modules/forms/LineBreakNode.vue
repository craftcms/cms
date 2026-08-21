<script setup lang="ts">
    import type {FormNodePayload} from './types';

    defineProps<{node: FormNodePayload}>();
</script>

<template>
    <div class="line-break" :data-form-node="node.uid"></div>
</template>

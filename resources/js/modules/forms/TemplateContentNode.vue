<script setup lang="ts">
    import type {FormNodePayload} from './types';

    defineProps<{
        node: FormNodePayload<{
            html: string;
            width: number;
        }>;
    }>();
</script>

<template>
    <div
        inert
        :class="`width-${node.props.width}`"
        :data-form-node="node.uid"
        v-html="node.props.html"
    ></div>
</template>

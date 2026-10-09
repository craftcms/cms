<script setup lang="ts">
  import {useAsyncIcon} from '@/common/composables/useAsyncIcon';

  const props = defineProps<{icon: string | null}>();
  const {html} = useAsyncIcon(() => props.icon);
</script>

<template>
  <span v-if="html" class="cs-item__icon" v-html="html" />
</template>

<style scoped lang="scss">
  .cs-item__icon {
    display: inline-flex;
    align-items: center;
    width: var(--c-size-control-sm);
  }
</style>

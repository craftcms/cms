<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';

  defineProps<{
    html: string | null | undefined;
  }>();
  defineSlots<{
    default?(): unknown;
  }>();
</script>

<template>
  <div class="p-lg">
    <slot />
    <hr v-if="html && $slots.default" class="my-lg" />
    <DynamicHtmlRenderer v-if="html" :html="html" />
  </div>
</template>

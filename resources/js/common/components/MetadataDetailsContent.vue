<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';

  defineProps<{
    html: string | null | undefined;
  }>();
  defineSlots<{
    default?(): unknown;
    /** Buttons below the metadata, at the end of the panel. */
    actions?(): unknown;
  }>();
</script>

<template>
  <div class="py-lg px-(--cp-container-padding)">
    <slot />
    <hr v-if="html && $slots.default" class="my-lg" />
    <DynamicHtmlRenderer v-if="html" :html="html" />
    <div v-if="$slots.actions" :class="{'mt-lg': html || $slots.default}">
      <slot name="actions" />
    </div>
  </div>
</template>

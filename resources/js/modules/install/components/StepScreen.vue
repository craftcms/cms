<script setup lang="ts">
  import {nextTick, onMounted, useTemplateRef} from 'vue';

  withDefaults(
    defineProps<{
      illustrationSrc?: string;
      heading?: string;
    }>(),
    {illustrationSrc: '', heading: ''}
  );

  const headingEl = useTemplateRef<HTMLElement>('headingEl');
  onMounted(async () => {
    // The heading is slotted into `<craft-pane>`, a Lit element — focusing it
    // before the pane's own render/slot assignment settles is silently a
    // no-op, so wait a tick first (matches `useFocusField`'s approach).
    await nextTick();
    headingEl.value?.focus();
  });
</script>

<template>
  <div class="grid md:grid-cols-2 gap-4 items-center">
    <div class="aspect-[352/455] w-1/2 md:w-3/4 mx-auto">
      <img loading="lazy" :src="illustrationSrc" alt="" width="368" />
    </div>
    <div>
      <h1 ref="headingEl" tabindex="-1" class="mb-4">{{ heading }}</h1>
      <div class="grid gap-3 md:pr-6">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>

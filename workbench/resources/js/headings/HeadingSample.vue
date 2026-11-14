<script setup lang="ts">
  import {inject, onBeforeUnmount, onMounted, ref, type Ref} from 'vue';

  const props = defineProps<{
    /** The `.cp-h*` class to apply. */
    level: 1 | 2 | 3 | 4 | 5 | 6;
    /** The element to render, when it differs from the class's level. */
    tag?: string;
  }>();

  const showLabels = inject<Ref<boolean>>('showHeadingLabels', ref(false));
  const el = ref<HTMLElement>();
  const size = ref('');

  // Read back what the browser actually rendered, so the label can't drift
  // from the CSS. Mounting can happen before the page is in the document, so
  // wait for layout.
  let observer: ResizeObserver | undefined;

  onMounted(() => {
    observer = new ResizeObserver(([entry]) => {
      const style = getComputedStyle(entry!.target);
      size.value = `${parseFloat(style.fontSize)}px / ${parseFloat(style.lineHeight)}px`;
    });

    if (el.value) {
      observer.observe(el.value);
    }
  });

  onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
  <component :is="tag ?? `h${level}`" ref="el" :class="`cp-h${level}`">
    <slot></slot>
    <code v-if="showLabels" class="heading-sample-label"
      >.cp-h{{ level }} · {{ size }}</code
    >
  </component>
</template>

<style scoped>
  .heading-sample-label {
    display: inline-block;
    margin-inline-start: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-xs, 4px);
    border-radius: var(--c-radius-sm);
    background-color: var(--c-surface-sunken);
    color: var(--c-text-quiet);
    font-family: var(--c-font-mono);
    font-size: var(--c-text-sm);
    font-weight: normal;
    letter-spacing: normal;
    text-transform: none;
    line-height: 1.5;
    vertical-align: middle;
  }
</style>

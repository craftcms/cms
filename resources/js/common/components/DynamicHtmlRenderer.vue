<script setup lang="ts">
  import {computed, defineComponent} from 'vue';

  const props = defineProps<{
    html: string;
  }>();

  /**
   * Matches an XML processing instruction, e.g. the `<?xml version="1.0"?>`
   * declaration that SVG editors write at the top of a file.
   */
  const PROCESSING_INSTRUCTION_RE = /<\?[\s\S]*?\?>/g;

  /**
   * Server-rendered HTML, made safe for Vue's runtime template compiler.
   *
   * The compiler parses in HTML mode, where a processing instruction is a hard
   * error rather than something it can skip. Under `vite dev` that only warns —
   * `'<?' is allowed only in XML context` — and the markup still renders, so
   * the failure is invisible in development. In a production build the compiler
   * throws instead, and because the error escapes during render it takes the
   * whole surrounding subtree down with it: an XML declaration on the system
   * icon is enough to leave the CP with no sidebar at all.
   *
   * These declarations carry no meaning once the markup is inlined into an HTML
   * document, so dropping them costs nothing.
   */
  const template = computed(() =>
    props.html.replace(PROCESSING_INSTRUCTION_RE, '')
  );

  const dynamicComponent = computed(() =>
    defineComponent({template: template.value})
  );
</script>
<template>
  <component :is="dynamicComponent" v-if="html" />
</template>

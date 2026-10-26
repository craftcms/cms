<script setup lang="ts">
  /**
   * Color-coded code. Give `value` for JSON, which it formats, or `code` in
   * any language `highlightTokens()` knows.
   */
  import {computed} from 'vue';
  import {highlightTokens} from '@/common/utils/highlight-code';

  const props = defineProps<
    | {language: 'json'; value: unknown; code?: never}
    | {language: string; code: string; value?: never}
  >();

  const tokens = computed(() =>
    highlightTokens(
      props.code ?? JSON.stringify(props.value, null, 2) ?? '',
      props.language
    )
  );
</script>

<template>
  <pre
    class="th-code"
  ><code><template v-for="(token, index) in tokens" :key="index"><span v-if="token.className" :class="`th-${token.className}`">{{ token.value }}</span><template v-else>{{ token.value }}</template></template></code></pre>
</template>

<style scoped>
  .th-code {
    margin: 0;
  }
</style>

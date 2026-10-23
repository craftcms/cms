<script setup lang="ts">
  /**
   * A callout about a single input. Place it in the input's `after` slot so
   * it's added to the input's accessible description.
   */
  import {t} from '@craftcms/ui';

  defineProps<{
    /** Translated message. */
    message: string;
    /** Whether `message` contains trusted markup, such as `<kbd>`. */
    html?: boolean;
    href?: string;
  }>();
</script>

<template>
  <craft-callout
    data-description
    variant="info"
    appearance="plain"
    class="p-0"
    icon="lightbulb"
  >
    <span v-if="html" v-html="message"></span>
    <template v-else>{{ message }}</template>
    <template v-if="href"
      >{{ ' ' }}<a :href="href">{{ t('Learn more') }}</a></template
    >
  </craft-callout>
</template>

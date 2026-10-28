<script setup lang="ts">
  /**
   * A field tip's text, with an optional "Learn more" link. Give it
   * `slot="tip"` inside a field or labeled control, which renders it as the
   * field's tip notice and adds it to the control's accessible description.
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
  <span>
    <span v-if="html" v-html="message"></span>
    <template v-else>{{ message }}</template>
    <template v-if="href"
      >{{ ' ' }}<a :href="href">{{ t('Learn more') }}</a></template
    >
  </span>
</template>

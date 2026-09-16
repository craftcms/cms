<script setup lang="ts">
  import {t} from '@craftcms/ui';

  withDefaults(
    defineProps<{
      errors: Record<string, string>;
      /**
       * What the screen was trying to do. Settings screens are the default
       * because they were the first caller; an element editor says its own.
       */
      title?: string;
    }>(),
    {title: () => t('Could not save settings')}
  );
</script>

<template>
  <craft-callout variant="danger" icon="triangle-exclamation" class="mb-3">
    <div slot="title" class="font-bold">
      {{ title }}
    </div>
    <ul>
      <!-- Deliberately text, not markup. A message can carry HTML the server
           composed — the link to another site's validation errors is one — but
           it can also carry content, and Craft encodes before it lets markup
           through (`Html::encodeInvalidTags()`, then Markdown). Rendering these
           raw would put a field's own value into the DOM as HTML.
           `ElementResponse::errorSummary()` already does that work server-side;
           this component should be fed its output rather than re-deriving it. -->
      <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
    </ul>
  </craft-callout>
</template>

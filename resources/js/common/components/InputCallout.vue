<script setup lang="ts">
  /**
   * A callout about a single input. Place it in the input's `after` slot so
   * it's added to the input's accessible description.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';

  const props = withDefaults(
    defineProps<{
      /** Translated message; may contain markup such as `<kbd>`. */
      message: string;
      variant?: string;
      /** Defaults to a lightbulb for `info`, otherwise the variant's own icon. */
      icon?: string;
      href?: string;
      linkText?: string;
    }>(),
    {variant: 'info'}
  );

  const resolvedIcon = computed(
    () => props.icon ?? (props.variant === 'info' ? 'lightbulb' : undefined)
  );
</script>

<template>
  <craft-callout
    data-description
    :variant="variant"
    appearance="plain"
    class="p-0"
    :icon="resolvedIcon"
  >
    <span v-html="message"></span>
    <template v-if="href"
      >{{ ' ' }}<a :href="href">{{ linkText ?? t('Learn more') }}</a></template
    >
  </craft-callout>
</template>

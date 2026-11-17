<script setup lang="ts">
  import {type Component, computed} from 'vue';
  import {t} from '@craftcms/ui';
  import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';

  const props = withDefaults(
    defineProps<{
      as?: string | Component;
      template: string;
      params?: Record<string, string | number>;
    }>(),
    {as: 'div'}
  );

  const text = computed(() =>
    t(
      props.template,
      props.params &&
        Object.fromEntries(
          Object.entries(props.params).map(([key, value]) => [
            key,
            typeof value === 'string' ? escapeHtml(value) : value,
          ])
        )
    )
  );
</script>

<template>
  <component :is="as" v-html="text" />
</template>

<style scoped lang="scss"></style>

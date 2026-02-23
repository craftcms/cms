<script setup lang="ts">
  import {type Component, computed} from 'vue';
  import {t} from '@craftcms/cp/utilities/translate.ts';

  const props = withDefaults(
    defineProps<{
      as?: string | Component;
      template: string;
      params?: Record<string, string | number>;
    }>(),
    {as: 'div'}
  );

  const text = computed(() => t(props.template, props.params));
</script>

<template>
  <component :is="as" v-bind="$attrs">{{ text }}</component>
</template>

<style scoped lang="scss"></style>

<script setup lang="ts">
  import {t} from '@craftcms/ui';

  const {icon = 'custom-icons/gear-slash'} = defineProps<{icon?: string}>();
</script>

<template>
  <craft-callout
    appearance="fill"
    rounded="none"
    class="border border-b-neutral-border-quiet"
  >
    <craft-icon slot="icon" :name="icon"></craft-icon>
    <slot>{{
      t('Changes to these settings arenʼt permitted in this environment.')
    }}</slot>
  </craft-callout>
</template>

<style scoped lang="scss">
  craft-callout {
    --c-callout-padding-inline: calc(var(--cp-container-padding) - 1px);
    --c-callout-icon-padding-inline: 0 var(--c-spacing-sm);
  }
</style>

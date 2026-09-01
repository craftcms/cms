<script setup lang="ts">
  import {t} from '@craftcms/ui';

  defineProps<{
    errors: Record<string, string>;
  }>();
</script>

<template>
  <craft-callout variant="danger" icon="triangle-exclamation" class="mb-3">
    <div slot="title" class="font-bold">
      {{ t('Could not save settings') }}
    </div>
    <ul>
      <li v-for="(error, key) in errors" :key="key">
        {{ error }}
      </li>
    </ul>
  </craft-callout>
</template>

<style scoped lang="scss"></style>

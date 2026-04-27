<script setup lang="ts">
  import {t} from '@craftcms/cp';
</script>

<template>
  <craft-callout
    appearance="fill"
    rounded="start"
    class="border border-b-neutral-border-quiet"
  >
    <craft-icon slot="icon" name="custom-icons/gear-slash"></craft-icon>
    <slot>{{
      t('Changes to these settings arenʼt permitted in this environment.')
    }}</slot>
  </craft-callout>
</template>

<style scoped lang="scss"></style>

<script setup lang="ts">
  /** One fact in a `MetadataList`: its label, and its value in the default slot. */
  defineProps<{
    label: string;
  }>();
</script>

<template>
  <div>
    <dt>{{ label }}</dt>
    <dd><slot></slot></dd>
  </div>
</template>

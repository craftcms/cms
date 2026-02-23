<script setup lang="ts">
  import {computed} from 'vue';

  const props = defineProps<{
    value: string;
  }>();

  const formattedValue = computed(() => new Date(props.value).toLocaleString());
</script>

<template>
  {{ formattedValue }}
</template>

<style scoped lang="scss"></style>

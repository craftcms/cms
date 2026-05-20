<script setup lang="ts">
  import {computed} from 'vue';
  import VarDump from '@/components/VarDump.vue';

  const props = defineProps<{
    filesystem?: string;
  }>();

  const fs = computed(() => props.filesystem ? JSON.parse(props.filesystem) : null);
</script>

<template>
  <h2>Roundabout LocalFsSettings</h2>

  <VarDump :data="{props}" />
</template>

<style scoped lang="scss"></style>

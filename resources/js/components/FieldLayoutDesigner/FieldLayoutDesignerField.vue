<script setup lang="ts">
  import VarDump from '@/components/VarDump.vue';

  const model = defineModel();
</script>

<template>
  hey
  <VarDump :data="model"></VarDump>
</template>

<style scoped lang="scss"></style>

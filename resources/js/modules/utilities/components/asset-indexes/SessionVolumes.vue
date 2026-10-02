<script setup lang="ts">
  function getVolumes(raw: string): Array<string> {
    const parsed = JSON.parse(raw);
    return Object.values(parsed);
  }

  defineProps<{
    value: string;
  }>();
</script>

<template>
  <ul class="">
    <li v-for="volume in getVolumes(value)" :key="volume">
      {{ volume }}
    </li>
  </ul>
</template>

<style scoped lang="scss"></style>

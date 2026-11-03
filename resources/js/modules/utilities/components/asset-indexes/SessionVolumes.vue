<script setup lang="ts">
  import {computed} from 'vue';

  const props = defineProps<{
    value: string;
  }>();

  const volumes = computed<Array<string>>(() =>
    Object.values(JSON.parse(props.value))
  );
</script>

<template>
  <ul class="">
    <li v-for="volume in volumes" :key="volume">
      {{ volume }}
    </li>
  </ul>
</template>

<style scoped lang="scss"></style>

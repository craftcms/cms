<script setup lang="ts">
  import {defineComponent, computed} from 'vue';

  const props = defineProps<{
    html: string;
  }>();

  const dynamicComponent = computed(() =>
    defineComponent({template: props.html})
  );
</script>
<template>
  <component :is="dynamicComponent" />
</template>

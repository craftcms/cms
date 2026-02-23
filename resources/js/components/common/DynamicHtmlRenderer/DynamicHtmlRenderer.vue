<script setup lang="ts">
  import {computed, defineComponent} from 'vue';

  const props = defineProps<{
    html: string;
  }>();

  const dynamicComponent = computed(() =>
    defineComponent({template: props.html})
  );
</script>
<template>
  <component :is="dynamicComponent" v-if="html" />
</template>

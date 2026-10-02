<script setup lang="ts">
  import {resolveDynamicComponent} from 'vue';
  import ElementEditor from '@/modules/elements/components/ElementEditor.vue';

  const props = defineProps<{
    editorComponent?: string;
    saveParams: Record<string, number | string | null>;
  }>();

  const saveData = () => ({...props.saveParams});
</script>

<template>
  <component
    :is="
      editorComponent ? resolveDynamicComponent(editorComponent) : ElementEditor
    "
    :save-data="saveData"
  />
</template>

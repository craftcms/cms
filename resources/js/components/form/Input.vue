<script setup lang="ts">
  import {defineModel} from 'vue';

  const model = defineModel();
  defineProps<{
    label?: string;
    name?: string;
    error?: string;
    disabled?: boolean;
  }>();
</script>

<template>
  <craft-input
    :label="label"
    :name="name"
    :disabled="disabled"
    v-model="model"
    v-bind="$attrs"
  >
    <ul class="error-list" v-if="error" slot="feedback">
      <li>{{ error }}</li>
    </ul>
  </craft-input>
</template>

<style scoped lang="scss"></style>

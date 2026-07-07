<script setup lang="ts">
  import {useTemplateRef} from 'vue';

  defineProps<{
    url: string;
  }>();

  const emit = defineEmits<{
    (e: 'success'): void;
  }>();

  const invoker = useTemplateRef('invoker');

  function openSlideout(url: string) {
    const slideout = new Craft.CpScreenSlideout(url);

    slideout.on('submit', () => {
      emit('success');
    });

    slideout.on('close', () => {
      invoker.value?.focus();
    });
  }
</script>

<template>
  <craft-button
    type="button"
    appearance="filled"
    @click="openSlideout(url)"
    ref="invoker"
  >
    <slot></slot>
  </craft-button>
</template>

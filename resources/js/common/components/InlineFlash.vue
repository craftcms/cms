<script setup lang="ts">
  import TransitionFade from '@/common/components/TransitionFade.vue';
  import {useFlash} from '@/common/composables/useFlash';

  withDefaults(
    defineProps<{
      isActive?: boolean;
    }>(),
    {isActive: false}
  );

  const {successFlash, errorFlash} = useFlash();
</script>

<template>
  <TransitionFade>
    <template v-if="isActive && successFlash">
      <craft-callout
        variant="success"
        appearance="plain"
        icon="circle-check"
        inline
        class="p-0"
        >{{ successFlash }}</craft-callout
      >
    </template>
    <template v-if="isActive && errorFlash">
      <craft-callout
        variant="danger"
        appearance="plain"
        icon="triangle-exclamation"
        inline
        class="p-0"
      >
        {{ errorFlash }}
      </craft-callout>
    </template>
  </TransitionFade>
</template>

<style scoped lang="scss"></style>

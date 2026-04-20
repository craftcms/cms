<script setup lang="ts">
  import TransitionFade from '@/components/TransitionFade.vue';
  import {useFlash} from '@/composables/useFlash';

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
      <div class="flex gap-1 items-center text-sm">
        <craft-icon
          name="circle-check"
          style="color: var(--c-color-success-fill-loud)"
        ></craft-icon>
        {{ successFlash }}
      </div>
    </template>
    <template v-if="isActive && errorFlash">
      <div class="tw:flex tw:gap-1 tw:items-center tw:text-sm">
        <craft-icon
          name="triangle-exclamation"
          style="color: var(--c-color-danger-fill-loud)"
        ></craft-icon>
        {{ errorFlash }}
      </div>
    </template>
  </TransitionFade>
</template>

<style scoped lang="scss"></style>

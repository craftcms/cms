<script setup lang="ts">
  import useCraftData from '@/common/composables/useCraftData';
  import {computed} from 'vue';

  const {currentUser} = useCraftData();

  const sizeMap = {
    sm: 'size-7',
    md: 'size-10',
  };

  const props = withDefaults(
    defineProps<{
      size?: keyof typeof sizeMap;
    }>(),
    {
      size: 'sm',
    }
  );

  const sizeClass = computed(() => {
    return sizeMap[props.size];
  });
</script>

<template>
  <div
    v-if="currentUser?.thumbHtml"
    v-html="currentUser?.thumbHtml"
    data-color="white"
    :class="{
      'user-thumbnail': true,
      'rounded-full': true,
      [sizeClass]: true,
    }"
  />
</template>

<style scoped lang="scss">
  .user-thumbnail {
    background-color: var(--c-color-fill-loud);
    width: 100%;
    height: 100%;
  }

  :deep(svg) {
    width: 100%;
    height: 100%;
  }
</style>

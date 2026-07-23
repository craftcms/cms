<script setup lang="ts">
  import {computed} from 'vue';
  import {capitalize} from '@craftcms/ui';

  type Color = string | {value: string};

  const props = withDefaults(
    defineProps<{
      label?: string;
      value: string | number;
      mode?: 'badge' | 'inline';
      color?: Color;
    }>(),
    {mode: 'inline'}
  );

  const variant = computed(() => {
    if (props.color) {
      return 'custom';
    }

    switch (props.value) {
      case 'live':
      case 'on':
      case 'success':
      case 'enabled':
        return 'success';

      case 'off':
      case 'suspended':
      case 'expired':
      case 'danger':
      case 'red':
        return 'danger';

      case 'pending':
      case 'warning':
        return 'warning';

      case 'info':
        return 'info';

      case 'disabled':
        return 'empty';

      default:
        return 'custom';
    }
  });

  const indicatorStyles = computed(() => {
    // Empty string === all
    if (!props.value) {
      return {
        '--background':
          'transparent linear-gradient(60deg, #184cef, #e5422b) border-box',
      };
    }

    return {};
  });

  const computedLabel = computed(
    () => props.label ?? capitalize(props.value.toString())
  );
</script>

<template>
  <div
    :class="{
      'inline-flex border': true,
      'gap-2 border-transparent': mode === 'inline',
      [`bg-${variant}-fill-quiet border-${variant}-border-quiet`]:
        mode === 'badge',
      'gap-1 px-1.5 py-0.5 rounded-full text-xs': mode === 'badge',
    }"
  >
    <craft-indicator
      :variant="variant"
      :style="indicatorStyles"
    ></craft-indicator>
    {{ computedLabel }}
  </div>
</template>

<style scoped lang="scss"></style>

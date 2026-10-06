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

  // Empty string === all. `fill` takes any CSS value, so the gradient goes
  // through it; only set when there is one, or the indicator's own fill would
  // be overridden with nothing.
  const indicatorBindings = computed(() =>
    props.value
      ? {}
      : {fill: 'linear-gradient(60deg, #184cef, #e5422b) border-box'}
  );

  const computedLabel = computed(
    () => props.label ?? capitalize(props.value.toString())
  );
</script>

<template>
  <div
    :class="{
      'inline-flex border items-center': true,
      'gap-2 border-transparent': mode === 'inline',
      [`bg-${variant}-fill-quiet border-${variant}-border-quiet`]:
        mode === 'badge',
      'gap-1 px-1.5 py-0.5 rounded-full text-xs': mode === 'badge',
    }"
  >
    <craft-indicator
      :variant="variant"
      v-bind="indicatorBindings"
    ></craft-indicator>
    {{ computedLabel }}
  </div>
</template>

<style scoped lang="scss"></style>

<script setup lang="ts">
  import {computed} from 'vue';
  import {capitalize, colors} from '@craftcms/ui';

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

  /**
   * `craft-indicator` colors itself from `fill` alone: a status variant or
   * palette color name, or any CSS value. Empty string === all, which gets the
   * gradient. Nothing is bound for a value it can't color, or the indicator's
   * own fill would be overridden with nothing.
   */
  const indicatorBindings = computed(() => {
    if (!props.value) {
      return {fill: 'linear-gradient(60deg, #184cef, #e5422b) border-box'};
    }

    if (props.color) {
      return {
        fill: typeof props.color === 'string' ? props.color : props.color.value,
      };
    }

    if (variant.value === 'empty') {
      return {appearance: 'outline'};
    }

    if (variant.value !== 'custom') {
      return {fill: variant.value};
    }

    const value = props.value.toString();

    return (colors as string[]).includes(value) ? {fill: value} : {};
  });

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
    <craft-indicator v-bind="indicatorBindings"></craft-indicator>
    {{ computedLabel }}
  </div>
</template>

<style scoped lang="scss"></style>

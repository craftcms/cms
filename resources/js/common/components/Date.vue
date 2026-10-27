<script setup lang="ts">
  import {computed} from 'vue';

  const props = defineProps<{
    value: string;
    locale?: string;
  }>();

  const displayLocale = computed<Intl.LocalesArgument>(
    () => props.locale ?? window.Craft?.formattingLocale
  );
  const options = computed<Intl.DateTimeFormatOptions>(() => {
    return {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    };
  });

  const formattedValue = computed(() =>
    new Date(props.value).toLocaleString(displayLocale.value, options.value)
  );
</script>

<template>
  {{ formattedValue }}
</template>

<style scoped lang="scss"></style>

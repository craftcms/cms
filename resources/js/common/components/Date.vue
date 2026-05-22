<script setup lang="ts">
  import {computed} from 'vue';

  const props = defineProps<{
    value: string;
    locale?: string;
  }>();

  // @TODO this needs to respect a user's locale
  const locale = computed<Intl.LocalesArgument>(() => 'en-US');
  const options = computed<Intl.DateTimeFormatOptions>(() => {
    return {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    };
  });

  const formattedValue = computed(() =>
    new Date(props.value).toLocaleString(locale.value, options.value)
  );
</script>

<template>
  {{ formattedValue }}
</template>

<style scoped lang="scss"></style>

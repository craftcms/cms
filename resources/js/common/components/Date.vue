<script setup lang="ts">
  import {formatDate} from '@craftcms/ui';
  import {computed} from 'vue';

  const props = defineProps<{
    value: string;
  }>();

  const formattedValue = computed(() => formatDate(props.value));
</script>

<template>
  {{ formattedValue }}
</template>

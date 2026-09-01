<script setup lang="ts">
  import {onMounted, ref} from 'vue';
  import {useHttp} from '@inertiajs/vue3';
  import {show} from '@actions/Utilities/DeprecationErrorsController';

  const props = defineProps<{
    logId: number;
  }>();

  const http = useHttp<Record<string, never>, {html: string}>({});
  const data = ref<{html: string} | null>(null);

  onMounted(() => {
    http.get(show({logId: props.logId}).url, {
      onSuccess: ({html}) => {
        data.value = {html};
      },
    });
  });
</script>

<template>
  <craft-pane class="max-w-4xl">
    <template v-if="http.processing">
      <craft-spinner></craft-spinner>
    </template>
    <template v-if="http.wasSuccessful">
      <div v-html="data?.html"></div>
    </template>
  </craft-pane>
</template>

<style scoped lang="scss"></style>

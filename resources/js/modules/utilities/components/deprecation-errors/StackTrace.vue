<script setup lang="ts">
  import {onMounted, ref} from 'vue';
  import Pane from '@/common/components/Pane.vue';
  import {useHttp} from '@inertiajs/vue3';
  import {getDeprecationErrorTracesModal} from '@actions/Utilities/DeprecationErrorsController';

  const props = defineProps<{
    logId: number;
  }>();

  const http = useHttp<{logId: any}, {html: string}>({logId: props.logId});
  const data = ref<{html: string} | null>(null);

  onMounted(() => {
    http.post(getDeprecationErrorTracesModal().url, {
      onSuccess: ({html}) => {
        data.value = {html};
      },
    });
  });
</script>

<template>
  <Pane class="max-w-4xl">
    <template v-if="http.processing">
      <craft-spinner></craft-spinner>
    </template>
    <template v-if="http.wasSuccessful">
      <div v-html="data.html"></div>
    </template>
  </Pane>
</template>

<style scoped lang="scss"></style>

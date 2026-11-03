<script setup lang="ts">
  import {onMounted, ref} from 'vue';
  import {useHttp} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {show} from '@actions/Utilities/DeprecationErrorsController';

  const props = defineProps<{
    logId: number;
  }>();

  const emit = defineEmits<{
    (e: 'close'): void;
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
  <craft-pane :label="t('Stack Trace')">
    <craft-button
      slot="header-actions"
      type="button"
      icon="xmark-large"
      :aria-label="t('Close')"
      variant="plain"
      size="small"
      flush="inline-end"
      @click="emit('close')"
    ></craft-button>
    <div v-if="http.processing" class="flex h-80 items-center justify-center">
      <craft-spinner></craft-spinner>
    </div>
    <div v-else-if="http.wasSuccessful" v-html="data?.html"></div>
  </craft-pane>
</template>

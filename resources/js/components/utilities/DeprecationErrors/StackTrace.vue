<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {onMounted, ref} from 'vue';
  import Pane from '@/components/Pane.vue';
  import {useForm, useHttp} from '@inertiajs/vue3';
  import {show} from '@actions/Utilities/DeprecationErrorsController';
  import Empty from '@/components/Empty.vue';

  const props = defineProps<{
    logId: number;
  }>();

  const form = useForm({
    logId: props.logId,
  });

  const data = ref<Record<'html', string> | null>({html: ''});
  const showError = ref(false);
  const http = useHttp<any, {html: string}>();

  onMounted(() => {
    http.get(show(props.logId).url, {
      onHttpException: () => {
        showError.value = true;
      },
      onSuccess: (responseData: {html: string}) => {
        data.value = responseData;
      },
    });
  });
</script>

<template>
  <Pane class="max-w-4xl">
    <template v-if="data?.html">
      <div v-html="data?.html"></div>
    </template>
    <template v-else-if="showError">
      <Empty
        :label="t('Failed to load stack trace.')"
        icon="triangle-exclamation"
      ></Empty>
    </template>
    <template v-else>
      <craft-spinner></craft-spinner>
    </template>
  </Pane>
</template>

<style scoped lang="scss"></style>

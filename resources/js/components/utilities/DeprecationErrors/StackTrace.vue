<script setup lang="ts">
  import {useActionClient} from '@/composables/useFetch';
  import {onMounted} from 'vue';
  import Pane from '@/components/Pane/Pane.vue';

  const props = defineProps<{
    logId: number;
  }>();

  const {data, execute, isSuccess, isLoading} = useActionClient(
    'utilities/get-deprecation-error-traces-modal'
  );

  onMounted(() => {
    execute({logId: props.logId});
  });
</script>

<template>
  <Pane class="max-w-4xl">
    <template v-if="isLoading">
      <craft-spinner></craft-spinner>
    </template>
    <template v-if="isSuccess">
      <div v-html="data.html"></div>
    </template>
  </Pane>
</template>

<style scoped lang="scss"></style>

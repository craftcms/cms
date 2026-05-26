<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {Form} from '@inertiajs/vue3';
  import type {LogData} from '@/modules/utilities/components/deprecation-errors/DeprecationErrors.vue';
  import {deleteAllDeprecationErrors} from '@actions/Utilities/DeprecationErrorsController';

  defineProps<{
    logs: Array<LogData>;
    disabled?: boolean;
  }>();
</script>

<template>
  <div>
    <Form
      v-if="logs.length"
      #default="{processing}"
      :action="deleteAllDeprecationErrors()"
      method="post"
    >
      <craft-button :disabled="disabled" :loading="processing" type="submit">{{
        t('Clear All')
      }}</craft-button>
    </Form>
  </div>
</template>

<style scoped lang="scss"></style>

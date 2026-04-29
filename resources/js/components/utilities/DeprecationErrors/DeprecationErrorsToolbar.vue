<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {Form} from '@inertiajs/vue3';
  import {destroyAll} from '@actions/Utilities/DeprecationErrorsController';

  defineProps<{
    disabled?: boolean;
  }>();
</script>

<template>
  <Form :action="destroyAll()" method="delete" #default="{processing}">
    <craft-button :disabled="disabled" type="submit" :loading="processing">{{
      t('Clear All')
    }}</craft-button>
  </Form>
</template>

<style scoped lang="scss"></style>

<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {Form} from '@inertiajs/vue3';
  import {destroyAll} from '@actions/Utilities/DeprecationErrorsController';

  defineProps<{
    disabled?: boolean;
  }>();
</script>

<template>
  <div>
    <Form
      v-if="!disabled"
      v-slot="{processing}"
      :action="destroyAll()"
      method="delete"
    >
      <craft-button :loading="processing" type="submit">
        {{ t('Clear All') }}
      </craft-button>
    </Form>
  </div>
</template>

<style scoped lang="scss"></style>

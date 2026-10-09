<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import type {InertiaForm} from '@inertiajs/vue3';
  import {
    PRIMARY_SUBMITTER,
    useFormSubmitter,
  } from '@/common/composables/useFormSubmitter';

  const props = defineProps<{
    form: InertiaForm<any>;
    label?: string;
  }>();

  const submitter = useFormSubmitter(() => props.form);
</script>

<template>
  <craft-button
    type="submit"
    :variant="ButtonVariant.Primary"
    .loading="submitter.isSubmitting(PRIMARY_SUBMITTER)"
    .disabled="form.processing"
  >
    {{ label ?? t('Save') }}
  </craft-button>
</template>

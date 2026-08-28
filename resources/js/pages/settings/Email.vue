<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {useForm} from '@inertiajs/vue3';
  import FormPage from '@/pages/Form.vue';
  import type {FormPayload} from '@/modules/forms/types';
  import InlineFlash from '@/common/components/InlineFlash.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import {test} from '@routes/cp/settings/email';

  const props = defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    defaultToEmail: string;
  }>();

  const testForm = useForm({
    to: props.defaultToEmail,
  });

  function sendTest(): void {
    testForm.clearErrors().submit(test(), {
      onSuccess: () => testForm.reset(),
    });
  }
</script>

<template>
  <div class="cp:grid cp:gap-3">
    <FormPage :form="form" :submit="submit" />

    <craft-pane appearance="raised">
      <h2 class="cp:mb-3">{{ t('Send a test email') }}</h2>

      <div class="cp:grid cp:gap-3">
        <CraftInput
          :label="t('To')"
          v-model="testForm.to"
          name="to"
          :error="testForm.errors.to"
        />

        <div class="cp:flex cp:gap-2 cp:items-center">
          <craft-button
            type="button"
            :variant="ButtonVariant.Solid"
            :loading="testForm.processing"
            @click="sendTest"
          >
            {{ t('Test') }}
          </craft-button>
          <InlineFlash
            :is-active="testForm.recentlySuccessful || testForm.hasErrors"
          />
        </div>
      </div>
    </craft-pane>
  </div>
</template>

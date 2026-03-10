<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts';
  import {useForm} from '@inertiajs/vue3';
  import mailSettingsController from '@actions/Utilities/MailSettingsController';
  import Input from '@/components/form/Input.vue';

  const props = defineProps<{
    settings: Record<string, string>;
    defaultToEmail: string;
  }>();

  const form = useForm({
    to: props.defaultToEmail,
  });

  function submit() {
    form.clearErrors();
    form.submit(mailSettingsController(), {
      onSuccess: () => {
        form.reset();
      },
    });
  }
</script>

<template>
  <div class="p-4 grid gap-8">
    <div>
      <h2 class="mb-3">{{ t('Email Settings') }}</h2>

      <table class="cp-table cp-table--borderless" dir="ltr">
        <tbody>
          <tr v-for="(value, label) in settings" :key="label">
            <th class="light">{{ label }}</th>
            <td>{{ value || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div id="mail-settings-test">
      <h2 class="mb-3">{{ t('Send a test email') }}</h2>

      <form @submit.prevent="submit" method="post">
        <div class="grid gap-3">
          <Input
            :label="t('To')"
            v-model="form.to"
            name="to"
            :error="form.errors.to"
          />

          <div class="buttons">
            <craft-button
              type="submit"
              variant="primary"
              :loading="form.processing"
            >
              {{ t('Test') }}
            </craft-button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
  import {nextTick, ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import CraftTextarea from '@craftcms/ui/vue/CraftTextarea.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftCheckbox from '@craftcms/ui/vue/CraftCheckbox.vue';
  import {useForm} from '@inertiajs/vue3';
  import CraftSupportController from '@actions/Dashboard/Widgets/CraftSupportController';
  import type {SupportData} from './types';

  const props = defineProps<{
    id: number;
    active: boolean;
    screen: 'home' | 'help' | 'feedback';
    data: SupportData;
  }>();

  const message = defineModel<string>({required: true});
  const textarea = ref<InstanceType<typeof CraftTextarea>>();
  const form = useForm({
    fromEmail: props.data.email,
    attachLogs: true,
    attachDbBackup: props.data.showBackupOption,
    attachTemplates: true,
    attachAdditionalFile: null as File | null,
  });

  const fileInput = ref<HTMLInputElement>();
  const sending = defineModel<boolean>('sending', {default: false});

  watch(
    () => form.processing,
    (processing) => {
      sending.value = processing;
    }
  );

  watch(
    () => props.active,
    async (active) => {
      if (active) {
        await nextTick();
        textarea.value?.$el.focus();
      }
    }
  );

  function submit() {
    if (form.processing || !message.value) return;

    form.attachAdditionalFile = fileInput.value?.files?.[0] ?? null;

    form
      .transform((values) => ({
        ...values,
        widgetId: props.id,
        message: message.value,
      }))
      .submit(CraftSupportController(), {
        preserveScroll: true,
        only: ['errors', 'flash'],
        onSuccess: () => {
          message.value = '';
          form.reset('attachAdditionalFile');

          if (fileInput.value) {
            fileInput.value.value = '';
          }
        },
      });
  }
</script>

<template>
  <div class="space-y-4">
    <CraftTextarea
      ref="textarea"
      v-model="message"
      :label="
        screen === 'help'
          ? t('Briefly describe your question.')
          : t('Briefly describe your issue or idea.')
      "
      :rows="5"
      :disabled="sending"
      @keydown.enter.ctrl.prevent="submit"
      @keydown.enter.meta.prevent="submit"
    />
    <form @submit.prevent="submit" class="space-y-3">
      <CraftInput
        name="fromEmail"
        :label="t('Your Email')"
        v-model="form.fromEmail"
        :error="form.errors.fromEmail"
        type="email"
        required
        :disabled="sending"
      ></CraftInput>
      <craft-disclosure :label="t('More')">
        <div slot="content" class="flex flex-col gap-2 py-3">
          <CraftCheckbox
            name="attachLogs"
            v-model="form.attachLogs"
            :disabled="sending"
            :label="t('Attach error logs')"
          ></CraftCheckbox>
          <CraftCheckbox
            v-if="data.showBackupOption"
            name="attachDbBackup"
            v-model="form.attachDbBackup"
            :disabled="sending"
            :label="t('Attach a database backup')"
          ></CraftCheckbox>
          <CraftCheckbox
            name="attachTemplates"
            v-model="form.attachTemplates"
            :disabled="sending"
            :label="t('Include your template files')"
          ></CraftCheckbox>
          <craft-field
            :label="t('Attach an additional file')"
            :has-errors="Boolean(form.errors.attachAdditionalFile)"
          >
            <input
              ref="fileInput"
              slot="input"
              type="file"
              name="attachAdditionalFile"
              :disabled="sending"
              :aria-invalid="
                form.errors.attachAdditionalFile ? 'true' : undefined
              "
            />
            <ul
              v-if="form.errors.attachAdditionalFile"
              slot="feedback"
              class="error-list"
            >
              <li>{{ form.errors.attachAdditionalFile }}</li>
            </ul>
          </craft-field>
        </div>
      </craft-disclosure>
      <craft-callout
        v-if="Object.keys(form.errors).length"
        variant="danger"
        role="alert"
        ><ul>
          <li v-for="(error, field) in form.errors" :key="field">
            {{ error }}
          </li>
        </ul></craft-callout
      >
      <craft-button
        type="submit"
        variant="primary"
        .loading="sending"
        .disabled="sending || !message"
        >{{ t('Send') }}</craft-button
      >
    </form>
  </div>
</template>

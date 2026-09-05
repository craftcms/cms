<script setup lang="ts">
  import {computed, nextTick, ref, shallowRef, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import CraftTextarea from '@craftcms/ui/vue/CraftTextarea.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftCheckbox from '@craftcms/ui/vue/CraftCheckbox.vue';
  import CraftInputFile from '@craftcms/ui/vue/CraftInputFile.vue';
  import type InputFileElement from '@craftcms/ui/components/input-file/input-file';
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

  const attachments = shallowRef<InputFileElement['modelValue']>([]);
  const sending = computed(() => form.processing);

  defineExpose({sending});

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

    const file = attachments.value[0];
    form.attachAdditionalFile = file instanceof File ? file : null;

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
          attachments.value = [];
        },
      });
  }
</script>

<template>
  <div class="space-y-4">
    <h2 class="text-lg">{{ t('Contact Developer Support') }}</h2>
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
          <CraftInputFile
            v-model="attachments"
            :error="form.errors.attachAdditionalFile"
            :label="t('Attach an additional file')"
            :disabled="sending"
          ></CraftInputFile>
        </div>
      </craft-disclosure>
      <craft-callout
        v-if="Object.keys(form.errors).length"
        variant="danger"
        role="alert"
        ><ul>
          <li v-for="error in form.errors" :key="error">{{ error }}</li>
        </ul></craft-callout
      >
      <craft-button
        type="submit"
        variant="primary"
        :loading="sending"
        :disabled="sending || !message"
        >{{ t('Send') }}</craft-button
      >
    </form>
  </div>
</template>

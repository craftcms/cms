<script setup lang="ts">
  /**
   * Edits a system message, as a slideout panel.
   *
   * Opened with `openSlideoutWith()` rather than `openSlideout()`: the message
   * list already has everything the form needs, so there's no screen to fetch.
   */
  import {computed, useId} from 'vue';
  import {t} from '@craftcms/ui';
  import {useForm, useHttp} from '@inertiajs/vue3';
  import SystemMessagesController, {
    store,
  } from '@actions/Utilities/SystemMessagesController';
  import type {BaseOption} from '@/common/types';
  import type {SystemMessageData} from '@/modules/utilities/types/utilities';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import Select from '@/common/form/Select.vue';
  import type CraftInput from '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/input/input';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {commentToolbarButtons} from '@/modules/markdown-field/toolbarPresets';
  import '@/modules/markdown-field/markdown-field';
  import '@craftcms/ui/components/text-expander/text-expander';

  const props = defineProps<{
    message: SystemMessageData;
    locales: Array<BaseOption>;
  }>();

  const subjectId = useId();
  const bodyId = useId();

  const form = useForm({
    key: props.message.key,
    language: props.message.language,
    subject: props.message.subject,
    body: props.message.body,
  });

  const loader = useHttp<{language: string}, {message: SystemMessageData}>({
    language: props.message.language,
  });

  const {save} = useSettingsSave(form, store);

  useAppLayout(() => ({
    title: t('Edit Message'),
    form,
    onSave: save,
  }));

  const language = computed({
    get: () => loader.language,
    set: (value: string | number) => {
      if (
        String(value) === loader.language ||
        (form.isDirty &&
          !window.confirm(t('Are you sure you want to discard your changes?')))
      ) {
        return;
      }

      void loadLanguage(String(value));
    },
  });

  function onSubjectChanged(event: CustomEvent<{initialize?: boolean}>): void {
    if (event.detail?.initialize) {
      return;
    }

    form.subject = String((event.target as CraftInput).modelValue ?? '');
  }

  async function loadLanguage(value: string): Promise<void> {
    loader.language = value;

    try {
      const {message} = await loader.get(
        SystemMessagesController.show({key: props.message.key}).url
      );

      form.defaults({
        key: message.key,
        language: message.language,
        subject: message.subject,
        body: message.body,
      });
      form.reset();
      form.clearErrors();
    } catch (error) {
      loader.language = form.language;
      throw error;
    }
  }
</script>

<template>
  <CpContainer class="py-lg">
    <craft-field-group>
      <p>{{ message.heading }}</p>

      <div v-if="locales.length > 1" class="flex items-end gap-2">
        <Select
          v-model="language"
          :label="t('Language')"
          :options="locales"
          :disabled="loader.processing"
        />
        <craft-spinner .visible="loader.processing" style="--size: 1rem" />
      </div>

      <craft-field
        :label="t('Subject')"
        :help-text="t('Evaluated as a Twig template.')"
        .hasErrors="Boolean(form.errors.subject)"
        width="full"
        required
      >
        <craft-input
          slot="input"
          .modelValue="form.subject"
          maxlength="1000"
          required
          .disabled="loader.processing"
          :aria-invalid="form.errors.subject ? 'true' : undefined"
          @model-value-changed="onSubjectChanged"
        >
          <input :id="subjectId" slot="input" />
        </craft-input>
        <craft-text-expander
          slot="input"
          :for="subjectId"
          .triggers="message.textExpanderTriggers"
        />
        <span
          slot="tip"
          v-html="t('Type <kbd>{{</kbd> to insert a message variable.')"
        ></span>
        <ul v-if="form.errors.subject" slot="feedback" class="error-list">
          <li>{{ form.errors.subject }}</li>
        </ul>
      </craft-field>

      <craft-field
        :label="t('Body')"
        :help-text="t('Evaluated as a twig template, then parsed as markdown.')"
        .hasErrors="Boolean(form.errors.body)"
        required
      >
        <craft-markdown-field
          :id="bodyId"
          slot="input"
          :label="t('Body')"
          :rows="16"
          show-toolbar
          .toolbarButtons="commentToolbarButtons"
          sanitize-html
          required
          .disabled="loader.processing"
          :aria-invalid="form.errors.body ? 'true' : undefined"
          .value="form.body"
          @input="form.body = ($event.target as HTMLTextAreaElement).value"
        />
        <craft-text-expander
          slot="input"
          :for="bodyId"
          .triggers="message.textExpanderTriggers"
        />
        <span
          slot="tip"
          v-html="t('Type <kbd>{{</kbd> to insert a message variable.')"
        ></span>
        <ul v-if="form.errors.body" slot="feedback" class="error-list">
          <li>{{ form.errors.body }}</li>
        </ul>
      </craft-field>
    </craft-field-group>
  </CpContainer>
</template>

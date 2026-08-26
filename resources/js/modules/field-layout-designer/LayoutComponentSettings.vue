<script setup lang="ts">
  /**
   * The field layout designer's component settings, as a slideout panel.
   *
   * Opened with `openSlideoutWith()` rather than `openSlideout()`: the form is
   * built by POSTing the layout currently being edited, which is unsaved client
   * state with no URL to fetch.
   */
  import {ref, shallowRef} from 'vue';
  import {useForm} from '@inertiajs/vue3';
  import {appendBodyHtml, appendHeadHtml} from '@craftcms/ui';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSlideout} from '@/common/slideouts';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {FormPayload, FormValues} from '@/modules/forms/types';
  import {takeLayoutSettingsContext} from './settings-slideout';

  type FormErrors = Record<string, string | string[]>;

  const props = defineProps<{
    contextId: string;
    title: string;
  }>();
  const context = takeLayoutSettingsContext(props.contextId);

  const slideout = useSlideout();
  const payload = shallowRef<FormPayload>(context.payload);
  const errors = shallowRef<FormPayload['errors']>(
    context.payload.errors ?? []
  );
  const renderer = ref<{
    currentValues(): FormPayload['values'];
  } | null>(null);

  /**
   * Backs the shell's Save button, and gives it an accurate dirty check for the
   * unsaved-changes prompt — kept in sync with the renderer's values below.
   */
  const form = useForm({
    settings: JSON.stringify(settingsValues(context.payload.values)),
  });

  useAppLayout(() => ({
    title: props.title,
    form,
    onSave: save,
  }));

  function settingsValues(values: FormPayload['values']): FormValues {
    const settings = values.settings;
    return settings instanceof Object &&
      !Array.isArray(settings) &&
      !(settings instanceof File)
      ? {...settings}
      : {};
  }

  function currentValues(): FormValues {
    return settingsValues(renderer.value?.currentValues() ?? {});
  }

  function onChange(): void {
    form.settings = JSON.stringify(currentValues());
  }

  function setErrors(next: FormErrors): void {
    const scope = payload.value.scope ?? [];

    errors.value = Object.entries(next).map(([path, messages]) => ({
      path: [...scope, ...path.split('.')],
      messages: Array.isArray(messages) ? messages : [messages],
    }));
  }

  async function save(): Promise<void> {
    errors.value = [];

    try {
      await context.apply(currentValues());
    } catch (error: any) {
      const responseErrors = error?.response?.data?.errors;

      if (responseErrors) {
        setErrors(responseErrors);
      }

      return;
    }

    // Before close(): closing drops the panel from the store, and its handler
    // with it.
    slideout?.saved();
    slideout?.close({force: true});
  }

  async function refresh(
    values: FormPayload['values'],
    scope: string[] = payload.value.scope ?? []
  ): Promise<FormPayload> {
    const {data} = await Craft.sendActionRequest(
      'POST',
      'fields/refresh-layout-component-settings',
      {
        // `values` is already relative to `scope`, unlike currentValues().
        data: {...context.requestData(), settings: values, scope},
      }
    );

    if (!data.form) {
      throw new Error('The layout component did not return a Form payload.');
    }

    // Server-rendered controls (condition builders, field selects) register
    // their own assets on every render.
    await appendHeadHtml(data.headHtml);
    await appendBodyHtml(data.bodyHtml);

    return data.form;
  }
</script>

<template>
  <FormRenderer
    ref="renderer"
    :payload="payload"
    :errors="errors"
    :refresh="payload.refreshable ? refresh : undefined"
    @change="onChange"
  />
</template>

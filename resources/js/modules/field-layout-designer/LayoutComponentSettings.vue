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
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import type {UiPayload, UiValues} from '@/modules/ui/types';
  import {takeLayoutSettingsContext} from './settings-slideout';

  type UiErrors = Record<string, string | string[]>;

  const props = defineProps<{
    contextId: string;
    title: string;
  }>();
  const context = takeLayoutSettingsContext(props.contextId);

  const slideout = useSlideout();
  const payload = shallowRef<UiPayload>(context.payload);
  const errors = shallowRef<UiPayload['errors']>(context.payload.errors ?? []);
  const renderer = ref<{
    currentValues(): UiPayload['values'];
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

  function settingsValues(values: UiPayload['values']): UiValues {
    const settings = values.settings;
    return settings instanceof Object &&
      !Array.isArray(settings) &&
      !(settings instanceof File)
      ? {...settings}
      : {};
  }

  function currentValues(): UiValues {
    return settingsValues(renderer.value?.currentValues() ?? {});
  }

  function onChange(): void {
    form.settings = JSON.stringify(currentValues());
  }

  function setErrors(next: UiErrors): void {
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
    values: UiPayload['values'],
    scope: string[] = payload.value.scope ?? []
  ): Promise<UiPayload> {
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
  <UiRenderer
    ref="renderer"
    :payload="payload"
    :errors="errors"
    :refresh="payload.refreshable ? refresh : undefined"
    @change="onChange"
  />
</template>

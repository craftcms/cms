<script setup lang="ts">
  import {useMessages} from '@/modules/messages/useMessages';
  import {computed, ref} from 'vue';
  import {useHttp} from '@inertiajs/vue3';
  import '@craftcms/ui/components/field-group/field-group';
  import {actionClient, t} from '@craftcms/ui';
  import {
    store,
    update,
    refreshSettings,
  } from '@actions/Dashboard/WidgetsController';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import type {UiPayload} from '@/modules/ui/types';
  import type {DashboardWidget} from './types';

  const messages = useMessages();

  const props = defineProps<{widget: DashboardWidget}>();
  const emit = defineEmits<{
    saved: [widget: DashboardWidget | false];
    cancel: [];
  }>();

  const renderer = ref<InstanceType<typeof UiRenderer>>();
  const form = useHttp<Record<string, never>, {info: DashboardWidget | false}>(
    {}
  ).withAllErrors();
  defineExpose({sending: computed(() => form.processing)});
  const errors = computed(() =>
    Object.fromEntries(
      Object.entries(form.errors).map(([path, messages]) => [
        path,
        Array.isArray(messages) ? messages : [String(messages)],
      ])
    )
  );
  const uiErrors = computed(() =>
    Object.entries(errors.value).map(([path, messages]) => ({
      path: [...(props.widget.settingsUi?.scope ?? []), ...path.split('.')],
      messages,
    }))
  );

  async function refresh(
    values: UiPayload['values'],
    scope: string[] = []
  ): Promise<UiPayload> {
    const {data} = await actionClient.post(refreshSettings.url(), {
      type: props.widget.type,
      settings: values,
      namespace: scope.join('.'),
    });
    if (!data.ui) {
      throw new Error('The widget did not return a UI payload.');
    }

    return data.ui;
  }

  async function save() {
    if (form.processing) return;

    const values = renderer.value?.currentValues() ?? {};
    const namespace = props.widget.settingsUi?.scope[0];

    try {
      const data = await form
        .transform(() => ({
          type: props.widget.type,
          widgetId: props.widget.id,
          settings: namespace ? values[namespace] : props.widget.settings,
        }))
        .submit(props.widget.id < 0 ? store() : update());

      emit('saved', data.info);
      messages.success(t('Widget saved.'));
    } catch {
      if (!form.hasErrors) form.setError('widget', t('Couldn’t save widget.'));
    }
  }
</script>

<template>
  <form @submit.prevent="save">
    <craft-field-group>
      <UiRenderer
        v-if="widget.settingsUi"
        ref="renderer"
        :payload="widget.settingsUi"
        :refresh="refresh"
        :errors="uiErrors"
      />
    </craft-field-group>
    <craft-callout
      v-if="Object.keys(errors).length"
      variant="danger"
      role="alert"
      ><ul>
        <template v-for="(messages, field) in errors" :key="field"
          ><li v-for="message in messages" :key="message">
            {{ message }}
          </li></template
        >
      </ul></craft-callout
    >
    <div class="flex gap-2 mt-4">
      <craft-button
        type="submit"
        variant="primary"
        .loading="form.processing"
        .disabled="form.processing"
        >{{ t('Save') }}</craft-button
      >
      <craft-button
        type="button"
        .disabled="form.processing"
        @click="emit('cancel')"
        >{{ t('Cancel') }}</craft-button
      >
    </div>
  </form>
</template>

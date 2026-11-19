<script setup lang="ts">
  import {useMessages} from '@/modules/messages/useMessages';
  import {computed, ref, useId, watch} from 'vue';
  import {useHttp} from '@inertiajs/vue3';
  import '@craftcms/ui/components/field-group/field-group';
  import {t} from '@craftcms/ui';
  import {fetchUiPayload} from '@/modules/ui/fetchUiPayload';
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

  const formId = useId();

  const renderer = ref<InstanceType<typeof UiRenderer>>();
  const form = useHttp<Record<string, never>, {info: DashboardWidget | false}>(
    {}
  ).withAllErrors();
  const sending = defineModel<boolean>('sending', {default: false});
  watch(
    () => form.processing,
    (processing) => {
      sending.value = processing;
    }
  );
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
    const {ui} = await fetchUiPayload(
      refreshSettings.url(),
      {
        type: props.widget.type,
        settings: values,
        namespace: scope.join('.'),
      },
      'The widget did not return a UI payload.'
    );

    return ui;
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
  <form :id="formId" @submit.prevent="save">
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
          ><li v-for="(message, index) in messages" :key="index">
            {{ message }}
          </li></template
        >
      </ul></craft-callout
    >
  </form>
  <div class="widget-settings-footer">
    <craft-button
      type="submit"
      :form="formId"
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
</template>

<style scoped>
  /* A footer band across the bottom of the card, with a hairline above,
     like the header band at its top (see Widget.vue). It reaches past the
     card body's padding (see card.styles.ts) to the card's edges, and is a
     footer bar's height, 46px. */
  .widget-settings-footer {
    --_card-padding: clamp(
      var(--c-spacing-lg),
      2cqi + var(--c-spacing-md),
      calc(var(--c-spacing) * 6)
    );
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-md);
    box-sizing: border-box;
    min-block-size: calc(46rem / 16 + 1px);
    margin-block: var(--_card-padding) calc(var(--_card-padding) * -1);
    margin-inline: calc(var(--_card-padding) * -1);
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--_card-padding);
    border-block-start: 1px solid
      var(--c-color-border-quiet, var(--c-color-neutral-border-quiet));
  }
</style>

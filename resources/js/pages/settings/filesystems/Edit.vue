<script setup lang="ts">
  import {actionClient, t, toHandle} from '@craftcms/ui';
  import Pane from '@/common/components/Pane.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/ui/vue/CraftInputHandle.vue';
  import Select from '@/common/form/Select.vue';
  import {useForm, usePage} from '@inertiajs/vue3';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave.js';
  import {store} from '@actions/Settings/FilesystemsController';
  import {computed, ref, watch} from 'vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {FormPayload} from '@/modules/forms/types';
  import {renderSettings} from '@/actions/CraftCms/Cms/Http/Controllers/Settings/FilesystemsController';

  defineOptions({
    inheritAttrs: false,
  });

  const props =
    usePage<CraftCms.Cms.Http.ViewModels.FilesystemsEditViewModel>().props;

  const form = useForm({
    name: props.filesystem.name ?? '',
    handle: props.filesystem.handle ?? '',
    oldHandle: props.oldHandle,
    type: props.filesystem.type ?? '',
    settings: {} as Record<string, any>,
  });

  const settingsRenderer = ref<{
    advanceBaseline: () => void;
  } | null>(null);
  function setSettingsRenderer(renderer: unknown): void {
    settingsRenderer.value = renderer as typeof settingsRenderer.value;
  }
  const settingsPayload = computed<FormPayload | null>(
    () =>
      (props.fsInstances[form.type]?.settingsForm as FormPayload | null) ?? null
  );

  watch(
    () => form.type,
    () => (form.settings = {})
  );

  useInputGenerator(
    () => form.name,
    (v) => (form.handle = toHandle(v))
  );

  const {save} = useSettingsSave(form, store, {
    onSuccess: () => settingsRenderer.value?.advanceBaseline(),
  });

  const settingsErrors = computed(() =>
    Object.entries(form.errors)
      .filter(([path]) => !['name', 'handle', 'type'].includes(path))
      .map(([path, message]) => ({
        path: ['settings', ...path.split('.')],
        messages: [String(message)],
      }))
  );

  async function refreshSettings(
    values: FormPayload['values']
  ): Promise<FormPayload> {
    const {data} = await actionClient.post(renderSettings().url, {
      type: form.type,
      settings: {
        ...(props.filesystem.url === null ? {} : {url: props.filesystem.url}),
        ...values,
      },
    });

    if (!data.form) {
      throw new Error('The filesystem type did not return a Form payload.');
    }

    return data.form;
  }

  useAppLayout({
    form,
    onSave: save,
  });
</script>

<template>
  <Pane appearance="raised">
    <craft-field-group>
      <CraftInput
        v-model="form.name"
        :label="t('Name')"
        id="name"
        name="name"
        autocomplete="off"
        :autofocus="true"
        :required="true"
        :error="form.errors?.name"
        data-error-key="name"
        :disabled="props.readOnly"
      />

      <CraftInputHandle
        :label="t('Handle')"
        id="handle"
        name="handle"
        v-model="form.handle"
        :required="true"
        :error="form.errors?.handle"
        data-error-key="handle"
        :disabled="props.readOnly"
      />

      <hr />

      <template v-if="props.fsOptions.length">
        <Select
          id="type"
          name="type"
          :label="t('Filesystem Type')"
          :help-text="t('What type of filesystem is this?')"
          :options="props.fsOptions"
          v-model="form.type"
          :disabled="props.readOnly"
        />
      </template>

      <div>
        <template v-for="(instance, fsType) in props.fsInstances" :key="fsType">
          <craft-field-group v-if="form.type === fsType">
            <FormRenderer
              v-if="settingsPayload"
              :ref="setSettingsRenderer"
              :payload="settingsPayload"
              :errors="settingsErrors"
              :refresh="refreshSettings"
              @update:mutation="form.settings = $event.settings ?? {}"
            />
          </craft-field-group>
        </template>
      </div>
    </craft-field-group>
  </Pane>
</template>

<style scoped lang="scss"></style>

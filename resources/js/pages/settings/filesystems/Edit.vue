<script setup lang="ts">
  import {actionClient, serializeFormInputs, t, toHandle} from '@craftcms/ui';
  import Pane from '@/common/components/Pane.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/ui/vue/CraftInputHandle.vue';
  import Select from '@/common/form/Select.vue';
  import {useForm, usePage} from '@inertiajs/vue3';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave.js';
  import {renderSettings, store} from '@actions/Settings/FilesystemsController';
  import {computed, ref, watch} from 'vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import FormDefinitionRenderer from '@/form-definitions/FormDefinitionRenderer.vue';
  import type {
    FormDefinitionData,
    FormErrors,
    FormValues,
  } from '@/form-definitions/types';

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
    ...props.settingsValues,
  });
  const formDefinitionValues = form as unknown as FormValues;

  const settingsHost = ref<HTMLElement | null>(null);
  const settingsDefinition = ref<FormDefinitionData | null>(
    props.settingsDefinition as FormDefinitionData | null
  );
  const settingsBindingScope = ref(props.settingsBindingScope);
  const settingsErrors = ref<FormErrors>(props.settingsErrors);
  const settingsType = ref(form.type);
  const selectedType = ref(form.type);
  const settingsLoading = ref(false);
  let settingsRequestId = 0;

  useInputGenerator(
    () => form.name,
    (value) => (form.handle = toHandle(value))
  );

  const typeOptionFor = (type: string | undefined) =>
    props.fsOptions.find((option) => option.value === type);
  const currentTypeOption = computed(() => typeOptionFor(settingsType.value));
  const formDefinitionErrors = computed(() => ({
    ...settingsErrors.value,
    ...form.errors,
  }));

  function serializedLegacySettings(): string | undefined {
    if (!settingsHost.value?.querySelector('craft-legacy-settings-island')) {
      return undefined;
    }

    return serializeFormInputs(settingsHost.value);
  }

  watch(selectedType, async (type) => {
    if (type === settingsType.value) {
      return;
    }

    const oldType = settingsType.value;
    const oldTypeId = typeOptionFor(oldType)?.id;
    const requestId = ++settingsRequestId;
    selectedType.value = oldType;
    settingsLoading.value = true;

    try {
      const {data} = await actionClient.post(renderSettings().url, {
        type,
        oldType,
        settings: oldTypeId ? (form.types[oldTypeId] ?? {}) : {},
        typeSettings: serializedLegacySettings(),
      });

      if (requestId !== settingsRequestId) {
        return;
      }

      settingsDefinition.value = data.definition;
      settingsBindingScope.value = data.bindingScope;
      settingsErrors.value = data.errors;
      form.types = data.values.types;
      form.type = type;
      settingsType.value = type;
      selectedType.value = type;
    } catch (error) {
      if (requestId === settingsRequestId) {
        selectedType.value = settingsType.value;
      }

      throw error;
    } finally {
      if (requestId === settingsRequestId) {
        settingsLoading.value = false;
      }
    }
  });

  const {save} = useSettingsSave(form, store, {
    disabled: () => settingsLoading.value,
    transform: (data) => {
      const typeSettings = serializedLegacySettings();

      return typeSettings === undefined ? data : {...data, typeSettings};
    },
  });

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
          v-model="selectedType"
          :disabled="props.readOnly || settingsLoading"
        />
      </template>

      <template v-if="settingsLoading || settingsDefinition">
        <div ref="settingsHost" :aria-busy="settingsLoading">
          <div :id="currentTypeOption?.id">
            <div v-if="settingsLoading" class="flex justify-center p-4">
              <craft-spinner></craft-spinner>
            </div>
            <div :inert="settingsLoading || undefined">
              <FormDefinitionRenderer
                v-if="settingsDefinition"
                :definition="settingsDefinition"
                :binding-scope="settingsBindingScope"
                :values="formDefinitionValues"
                :errors="formDefinitionErrors"
                :read-only="props.readOnly"
              />
            </div>
          </div>
        </div>
      </template>
    </craft-field-group>
  </Pane>
</template>

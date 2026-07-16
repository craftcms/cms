<script setup lang="ts">
  import {t, toHandle} from '@craftcms/cp';
  import type {SelectOption} from '@/common/types';
  import Pane from '@/common/components/Pane.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/cp/vue/CraftInputHandle.vue';
  import Select from '@/common/form/Select.vue';
  import {useForm} from '@inertiajs/vue3';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import FilesystemSettings from '@/components/Filesystems/FilesystemSettings.vue';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave.js';
  import {store} from '@actions/Settings/FilesystemsController';
  import {provide, ref} from 'vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';

  // @TODO make actual type
  type Filesystem = any;

  const props = defineProps<{
    oldHandle: string | null;
    filesystem: Filesystem;
    fsOptions: Array<SelectOption>;
    fsInstances: Record<string, Filesystem>;
    fsTypes: Array<string>;
    readOnly: boolean;
  }>();

  const form = useForm({
    name: props.filesystem.name ?? '',
    handle: props.filesystem.handle ?? '',
    type: props.filesystem.type ?? '',
    settings: {
      hasUrls: props.filesystem.hasUrls ?? false,
      url: props.filesystem.url ?? '',
    },
  });

  useInputGenerator(
    () => form.name,
    (v) => (form.handle = toHandle(v))
  );

  /**
   * We create a special ref for the type specific settings and then provide
   * it down. That way Vue components registered and rendered within
   * DynamicHtmlRenderer can pick up the injected ref and alter it so the
   * settings values can be picked up by the form.
   *
   * @TODO I need to make sure this works with plugins, or make it work with
   * plugins.
   */
  const fsTypeSettings = ref<{
    path?: string | null;
  }>({
    path: props.filesystem.path ?? null,
  });

  provide('fsTypeSettings', fsTypeSettings);

  const {save} = useSettingsSave(
    form,
    store['/{cpTrigger?}/{actionTrigger?}/fs/save'],
    {
      transform: (data) => ({
        ...data,
        settings: {
          ...data.settings,
          ...fsTypeSettings.value,
        },
      }),
    }
  );

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
        :disabled="readOnly"
      />

      <CraftInputHandle
        :label="t('Handle')"
        id="handle"
        name="handle"
        v-model="form.handle"
        :required="true"
        :error="form.errors?.handle"
        data-error-key="handle"
        :disabled="readOnly"
      />

      <hr />

      <template v-if="fsOptions.length">
        <Select
          id="type"
          name="type"
          :label="t('Filesystem Type')"
          :help-text="t('What type of filesystem is this?')"
          :options="fsOptions"
          v-model="form.type"
          :disabled="readOnly"
        />
      </template>

      <template v-for="fsType in fsTypes" :key="fsType">
        <FilesystemSettings
          v-show="form.type === fsType"
          v-model:has-urls="form.settings.hasUrls"
          v-model:url="form.settings.url"
          :filesystem="fsInstances[fsType]"
        />
      </template>
    </craft-field-group>
  </Pane>
</template>

<style scoped lang="scss"></style>

<script setup lang="ts">
  import {serializeFormInputsAsObject, t, toHandle} from '@craftcms/cp';
  import Pane from '@/common/components/Pane.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/cp/vue/CraftInputHandle.vue';
  import Select from '@/common/form/Select.vue';
  import {useForm} from '@inertiajs/vue3';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave.js';
  import {store} from '@actions/Settings/FilesystemsController';
  import {provide, ref} from 'vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import CraftCombobox from '@/common/form/CraftCombobox.vue';
  import CraftSwitch from '@craftcms/cp/vue/CraftSwitch.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import VarDump from '@/common/components/VarDump.vue';

  const props =
    defineProps<CraftCms.Cms.Http.ViewModels.FilesystemsEditViewModel>();

  const form = useForm({
    name: props.filesystem.name ?? '',
    handle: props.filesystem.handle ?? '',
    type: props.filesystem.type ?? '',
    settings: {
      hasUrls: props.filesystem.hasUrls ?? false,
      url: props.filesystem.url ?? '',
    },
  });

  const settingsHost = ref<HTMLElement | null>(null);

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
  const fsTypeSettings = ref<Record<string, any>>({});

  provide('fsTypeSettings', fsTypeSettings);

  const {save} = useSettingsSave(
    form,
    store['/{cpTrigger?}/{actionTrigger?}/fs/save'],
    {
      transform: (data) => {
        const typeSettings = settingsHost.value
          ? serializeFormInputsAsObject(settingsHost.value)
          : {};

        return {
          ...data,
          settings: {
            ...data.settings,
            ...typeSettings,
            ...fsTypeSettings.value,
          },
        };
      },
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

      <VarDump :data="filesystem" />
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

      <template v-if="filesystem.showHasUrlSetting">
        <CraftSwitch
          :label="t('Files in this filesystem have public URLs')"
          name="hasUrls"
          id="has-urls"
          v-model="form.settings.hasUrls"
          :disabled="readOnly"
        />
      </template>

      <template v-if="form.settings.hasUrls && filesystem.showUrlSetting">
        <CraftCombobox
          :label="t('Base URL')"
          :help-text="t('The base URL to the files in this filesystem.')"
          v-model="form.settings.url"
          :options="baseUrlSuggestions"
          name="url"
          :required="true"
          placeholder="//example.com/path/to/folder"
          data-error-key="url"
          :disabled="readOnly"
        ></CraftCombobox>
      </template>

      <div ref="settingsHost">
        <template v-for="(instance, fsType) in fsInstances" :key="fsType">
          <craft-field-group v-if="form.type === fsType">
            <!-- Legacy (Twig) settings render as an isolated HTML island; component
                 settings are compiled as part of the page. Each pane must render
                 its own type's settings — rendering the selected filesystem's here
                 would inject the same island (and its element ids) once per pane. -->
            <HtmlFragmentRenderer
              v-if="instance.settingsFragment"
              :fragment="instance.settingsFragment"
            />
            <DynamicHtmlRenderer v-else :html="instance.settingsHtml ?? ''" />
          </craft-field-group>
        </template>
      </div>
    </craft-field-group>
  </Pane>
</template>

<style scoped lang="scss"></style>

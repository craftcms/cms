<script setup lang="ts">
  import {t, toHandle} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import VarDump from '@/components/VarDump.vue';
  import type {SelectItem, SelectOption} from '@/types';
  import Pane from '@/components/Pane.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/cp/vue/CraftInputHandle.vue';
  import Select from '@/components/form/Select.vue';
  import {useForm} from '@inertiajs/vue3';
  import {useInputGenerator} from '@/composables/useInputGenerator';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';
  import FilesystemSettings from '@/components/Filesystems/FilesystemSettings.vue';

  export type Filesystem = {
    errors: any[];
    name: string | null;
    handle: string | null;
    oldHandle: any;
    hasUrls: boolean;
    url: any;
    uid: any;
    rootUrl: any;
    id: any;
    dateCreated: any;
    dateUpdated: any;
    settingsHtml: string;
    rootPath: string;
    path: any;
    type: string;
    showHasUrlSetting: boolean;
    showUrlSetting: boolean;
  };

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
    hasUrls: props.filesystem.hasUrls ?? false,
    url: props.filesystem.url ?? '',
  });

  useInputGenerator(
    () => form.name,
    (v) => (form.handle = toHandle(v))
  );

  function handleSubmit() {
    const form = event.target as HTMLFormElement;

    form.transform(() => {
      return {
        ...(new FormData(form)),
        ...form
      }
    }).submit();
  }

  function getFs(fsType: string): Filesystem | null {
    if (fsType === form.type) {
      return props.filesystem;
    }

    return props.fsInstances[fsType] ?? null;
  }
</script>

<template>
  <AppLayout>
    <Pane appearance="raised">
      <div class="grid gap-3">
        <CraftInput
          v-model="form.name"
          :label="t('Name')"
          id="name"
          name="name"
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

        <template v-for="fsType in fsTypes">
          <FilesystemSettings
            v-model:has-urls="form.hasUrls"
            v-model:url="form.url"
            :filesystem="fsInstances[fsType]"
          />
        </template>
      </div>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>

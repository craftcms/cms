<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, inject, type Ref} from 'vue';
  import CraftCombobox from '@/common/form/CraftCombobox.vue';
  import {usePage} from '@inertiajs/vue3';

  defineProps<{
    filesystem: any;
    readOnly: boolean;
  }>();

  const page = usePage<{
    basePathSuggestions: Array<any>;
    filesystem: Pick<
      CraftCms.Cms.Http.ViewModels.FilesystemsEditViewModel,
      'filesystem'
    > & {
      path: string | null;
    };
    errors: Record<string, string>;
  }>();

  const fsTypeSettings = inject<Ref<Record<string, any>>>('fsTypeSettings');
  const path = computed({
    get: () => page.props.filesystem.path ?? '',
    set: (v) => {
      if (fsTypeSettings) {
        fsTypeSettings.value.path = v;
      }
    },
  });
</script>

<template>
  <CraftCombobox
    :label="t('Base Path')"
    :help-text="
      t(
        'The base folder path that should be used as the root of the filesystem.'
      )
    "
    v-model="path"
    name="path"
    id="path"
    :required="true"
    :placeholder="t('/path/to/folder')"
    data-error-key="path"
    :options="page.props.basePathSuggestions"
    :disabled="readOnly"
    :error="page.props.errors?.path"
  >
    <template slot="after">
      <craft-callout
        variant="info"
        appearance="plain"
        class="p-0"
        icon="lightbulb"
      >
        {{ t('This can begin with an environment variable or alias.') }}
        <a
          href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
          >{{ t('Learn more') }}</a
        >
      </craft-callout>
    </template>
  </CraftCombobox>
</template>

<style scoped lang="scss"></style>

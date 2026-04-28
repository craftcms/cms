<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/cp/vue/CraftInputHandle.vue';
  import {useForm} from '@inertiajs/vue3';
  import Pane from '@/components/Pane.vue';
  import type {SelectOption} from '@/types';
  import type {VolumeResource} from '@/types/volumes';
  import CraftCombobox from '@/components/form/CraftCombobox.vue';
  import {computed} from 'vue';
  import Select from '../../../packages/craftcms-cp/.claude/worktrees/focused-gould-47a1c7/resources/js/components/form/Select.vue';
  import FieldLayoutDesignerField from '@/components/FieldLayoutDesigner/FieldLayoutDesignerField.vue';
  import {useSettingsSave} from '@/composables/useSettingsSave';
  import VolumesController from '@actions/Settings/VolumesController';

  const props = defineProps<{
    volume: VolumeResource | null;
    errors: Record<string, string[]>;
    fsOptions: Array<SelectOption>;
    subpathOptions: Array<SelectOption>;
    readOnly?: boolean;
    isMultisite?: boolean;
  }>();

  const form = useForm({
    volumeId: props.volume?.id ?? '',
    name: props.volume?.name ?? '',
    handle: props.volume?.handle ?? '',
    fsHandle: props.volume?.fsHandle ?? '',
    subpath: props.volume?.subpath ?? '',
    transformFsHandle: props.volume?.transformFsHandle ?? '',
    transformSubpath: props.volume?.transformSubpath ?? '',
    titleTranslationMethod: props.volume?.titleTranslationMethod.value ?? '',
    titleTranslationKeyFormat: props.volume?.titleTranslationKeyFormat ?? '',
    altTranslationMethod: props.volume?.altTranslationMethod.value ?? '',
    altTranslationKeyFormat: props.volume?.altTranslationKeyFormat ?? '',
  });

  const {save} = useSettingsSave(form, VolumesController.save);

  const transformFsOptions = computed(() => {
    return [
      {
        label: t('Same as asset filesystem'),
        value: '',
      },
      ...props.fsOptions.filter((option) => option.value !== ''),
    ];
  });

  const translationMethodOptions = computed(() => {
    return [
      {label: t('Not translatable'), value: 'none'},
      {value: 'site', label: t('Translate for each site')},
      {value: 'siteGroup', label: t('Translate for each site group')},
      {value: 'language', label: t('Translate for each language')},
      {value: 'custom', label: t('Custom…')},
    ];
  });
</script>

<template>
  <AppLayout :form="form" @save="save">
    <Pane apearance="raised">
      <div class="grid gap-3">
        <CraftInput
          :label="t('Name')"
          v-model="form.name"
          autofocus
          required
          :error="form.errors.name"
          data-error-key="name"
          :disabled="readOnly"
        />

        <CraftInputHandle
          :label="t('Handle')"
          v-model="form.handle"
          required
          :error="form.errors?.handle"
          data-error-key="handle"
          :disabled="readOnly"
        />
        <hr />

        <CraftCombobox
          v-model="form.fsHandle"
          :label="t('Asset Filesystem')"
          id="fs-handle"
          :help-text="t('Choose which filesystem assets should be stored in.')"
          :error="form.errors?.fsHandle"
          :options="fsOptions"
          :disabled="readOnly"
        >
          <template #after>
            <craft-callout
              variant="info"
              appearance="plain"
              class="p-0"
              icon="lightbulb"
            >
              {{
                t(
                  'This can be set to an environment variable matching one of the option values.'
                )
              }}
            </craft-callout>
          </template>
        </CraftCombobox>

        <CraftCombobox
          v-model="form.subpath"
          :label="t('Subpath')"
          id="subpath"
          :help-text="t('Where assets should be stored on the filesystem.')"
          :options="subpathOptions"
          :error="form.errors.subpath"
          :callouts="['envVars']"
          :disabled="readOnly"
        />

        <CraftCombobox
          v-model="form.transformFsHandle"
          :label="t('Transform Filesystem')"
          id="transform-fs-handle"
          :help-text="
            t('Choose which filesystem image transforms should be stored in.')
          "
          :error="form.errors.transformFsHandle"
          :options="transformFsOptions"
          data-error-key="transformFsHafndle"
          :disabled="readOnly"
        >
          <template #after>
            <craft-callout
              variant="info"
              appearance="plain"
              class="p-0"
              icon="lightbulb"
            >
              {{
                t(
                  'This can be set to an environment variable matching one of the option values.'
                )
              }}
            </craft-callout>
          </template>
        </CraftCombobox>

        <CraftCombobox
          v-model="form.transformSubpath"
          :label="t('Transform Subpath')"
          id="transformSubpath"
          :help-text="t('Where transforms should be stored on the filesystem.')"
          :options="subpathOptions"
          :error="form.errors.subpath"
          :callouts="['envVars']"
          :disabled="readOnly"
        />

        <template v-if="true">
          <Select
            v-model="form.titleTranslationMethod"
            id="title-translation-method"
            :label="t('{name} Translation Method', {name: t('Title')})"
            :help-text="
              t('How should {name} values be translated?', {name: t('Title')})
            "
            :options="translationMethodOptions"
          />

          <template v-if="form.titleTranslationMethod === 'custom'">
            <CraftInput
              v-model="form.titleTranslationKeyFormat"
              :label="t('{name} Translation Key Format', {name: t('Title')})"
              :help-text="
                t(
                  'Template that defines the {name} field’s custom “translation key” format. Values will be copied to all sites that produce the same key.',
                  {name: t('Title')}
                )
              "
              id="title-translation-key-format"
              name="titleTranslationKeyFormat"
              :error="form.errors?.titleTranslationKeyFormat"
              data-error-key="titleTranslationKeyFormat"
              :disabled="readOnly"
            />
          </template>

          <Select
            v-model="form.altTranslationMethod"
            id="alt-translation-method"
            :label="
              t('{name} Translation Method', {name: t('Alternative Text')})
            "
            :help-text="
              t('How should {name} values be translated?', {
                name: t('Alternative Text'),
              })
            "
            :options="translationMethodOptions"
          />

          <template v-if="form.altTranslationMethod === 'custom'">
            <CraftInput
              v-model="form.altTranslationKeyFormat"
              :label="
                t('{name} Translation Key Format', {
                  name: t('Alternative Text'),
                })
              "
              :help-text="
                t(
                  'Template that defines the {name} field’s custom “translation key” format. Values will be copied to all sites that produce the same key.',
                  {name: t('Alternative Text')}
                )
              "
              id="alt-translation-key-format"
              name="altTranslationKeyFormat"
              :error="form.errors?.altTranslationKeyFormat"
              data-error-key="altTranslationKeyFormat"
              :disabled="readOnly"
            />
          </template>
        </template>
        <hr />

        <FieldLayoutDesignerField v-model="form.fieldLayout" />
      </div>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>

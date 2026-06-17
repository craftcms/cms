<script setup lang="ts">
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import {t, toHandle} from '@craftcms/cp';
  import {computed} from 'vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/cp/vue/CraftInputHandle.vue';
  import CraftTextarea from '@craftcms/cp/vue/CraftTextarea.vue';
  import CraftSwitch from '@craftcms/cp/vue/CraftSwitch.vue';
  import CraftSelect from '@craftcms/cp/vue/CraftSelect.vue';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import useCraftData from '@/common/composables/useCraftData';
  import Pane from '@/common/components/Pane.vue';
  import {store} from '@actions/Settings/EntryTypesController';
  import type {SelectOption} from '@/common/types';

  interface EntryTypeData {
    id: number | null;
    name: string | null;
    handle: string | null;
    description: string | null;
    uiLabelFormat: string;
    titleTranslationMethod: string;
    titleTranslationKeyFormat: string | null;
    titleFormat: string | null;
    allowLineBreaksInTitles: boolean;
    showSlugField: boolean;
    slugTranslationMethod: string;
    slugTranslationKeyFormat: string | null;
    showStatusField: boolean;
  }

  const props = defineProps<{
    title: string;
    crumbs: Array<any>;
    entryType: EntryTypeData;
    brandNew: boolean;
    fieldLayoutConfig: Record<string, any>;
    translationMethodOptions: Array<SelectOption>;
    typeName: string;
    lowerTypeName: string;
    isMultiSite: boolean;
    flash?: Record<any, any>;
    errors: Record<any, any> | null;
  }>();

  const {readOnly} = useCraftData();

  const form = useForm({
    entryTypeId: props.entryType.id,
    name: props.entryType.name ?? '',
    handle: props.entryType.handle ?? '',
    description: props.entryType.description ?? '',
    uiLabelFormat: props.entryType.uiLabelFormat ?? '',
    titleTranslationMethod: props.entryType.titleTranslationMethod,
    titleTranslationKeyFormat: props.entryType.titleTranslationKeyFormat ?? '',
    titleFormat: props.entryType.titleFormat ?? '',
    allowLineBreaksInTitles: props.entryType.allowLineBreaksInTitles,
    showSlugField: props.entryType.showSlugField,
    slugTranslationMethod: props.entryType.slugTranslationMethod,
    slugTranslationKeyFormat: props.entryType.slugTranslationKeyFormat ?? '',
    showStatusField: props.entryType.showStatusField,
    // The field layout designer UI is deferred — round-trip the existing layout
    // config unchanged so saving preserves it. (Submitted as a JSON string.)
    fieldLayout: JSON.stringify(props.fieldLayoutConfig),
  });

  // Auto-generate the handle from the name for new entry types.
  const handleGenerator = useInputGenerator(
    () => form.name,
    (v) => (form.handle = toHandle(v))
  );

  if (!props.brandNew) {
    handleGenerator.stop();
  }

  const showTitleTranslation = computed(() => props.isMultiSite);
  const showTitleTranslationKeyFormat = computed(
    () => props.isMultiSite && form.titleTranslationMethod === 'custom'
  );
  const showSlugTranslation = computed(
    () => form.showSlugField && props.isMultiSite
  );
  const showSlugTranslationKeyFormat = computed(
    () => form.showSlugField && form.slugTranslationMethod === 'custom'
  );

  const {save} = useSettingsSave(form, store);
</script>

<template>
  <AppLayout :title="title" :form="form" @save="save">
    <Pane appearance="raised">
      <div class="grid gap-3">
        <input
          v-if="entryType.id"
          type="hidden"
          name="entryTypeId"
          :value="entryType.id"
        />

        <!-- Name -->
        <CraftInput
          :label="t('Name')"
          :help-text="
            t('What this {type} will be called in the control panel.', {
              type: lowerTypeName,
            })
          "
          id="name"
          name="name"
          v-model="form.name"
          :disabled="readOnly"
          :error="errors?.name"
          required
          autofocus
        />

        <!-- Handle -->
        <CraftInputHandle
          :label="t('Handle')"
          :help-text="
            t('How you’ll refer to this {type} in the templates.', {
              type: lowerTypeName,
            })
          "
          id="handle"
          name="handle"
          v-model="form.handle"
          :disabled="readOnly"
          :error="errors?.handle"
          required
          @change="handleGenerator.markDirty()"
        />

        <!-- Description -->
        <CraftTextarea
          :label="t('Description')"
          id="description"
          name="description"
          v-model="form.description"
          :disabled="readOnly"
          :error="errors?.description"
        />

        <!--
          DEFERRED: icon picker. @craftcms/cp has no icon picker component yet.
          The existing icon value is preserved on save (store falls back to it
          when the field is absent). Replace with a real picker when available.
        -->
        <div>
          <h3 class="font-bold text-sm">{{ t('Icon') }}</h3>
          <craft-callout variant="info" appearance="outline-fill">
            {{
              t(
                'The icon picker will be available here soon. The existing icon is preserved when saving.'
              )
            }}
          </craft-callout>
        </div>

        <!--
          DEFERRED: color picker. @craftcms/cp has no color picker component yet.
          The existing color value is preserved on save (store falls back to it
          when the field is absent). Replace with a real picker when available.
        -->
        <div>
          <h3 class="font-bold text-sm">{{ t('Color') }}</h3>
          <craft-callout variant="info" appearance="outline-fill">
            {{
              t(
                'The color picker will be available here soon. The existing color is preserved when saving.'
              )
            }}
          </craft-callout>
        </div>

        <!-- UI Label Format -->
        <CraftInput
          :label="t('Title Format')"
          :help-text="
            t(
              'How {type} of this type should be labeled in the control panel.',
              {
                type: lowerTypeName,
              }
            )
          "
          id="uiLabelFormat"
          name="uiLabelFormat"
          v-model="form.uiLabelFormat"
          :disabled="readOnly"
          :error="errors?.uiLabelFormat"
          class="font-mono"
        />
      </div>

      <hr class="my-6" />

      <div class="grid gap-3">
        <!-- Title Translation Method -->
        <CraftSelect
          v-if="showTitleTranslation"
          :label="t('Title Translation Method')"
          :help-text="t('How should the title be translated?')"
          id="titleTranslationMethod"
          name="titleTranslationMethod"
          v-model="form.titleTranslationMethod"
          :disabled="readOnly"
          :error="errors?.titleTranslationMethod"
        >
          <select slot="input">
            <option
              v-for="option in translationMethodOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </CraftSelect>

        <!-- Title Translation Key Format -->
        <CraftInput
          v-if="showTitleTranslationKeyFormat"
          :label="t('Title Translation Key Format')"
          id="titleTranslationKeyFormat"
          name="titleTranslationKeyFormat"
          v-model="form.titleTranslationKeyFormat"
          :disabled="readOnly"
          :error="errors?.titleTranslationKeyFormat"
          class="font-mono"
        />

        <!-- Default Title Format -->
        <CraftInput
          :label="t('Default Title Format')"
          :help-text="
            t('The format that {type} titles should take when generated.', {
              type: lowerTypeName,
            })
          "
          id="titleFormat"
          name="titleFormat"
          v-model="form.titleFormat"
          :disabled="readOnly"
          :error="errors?.titleFormat"
          class="font-mono"
        />

        <!-- Allow line breaks in titles -->
        <CraftSwitch
          :label="t('Allow line breaks in titles')"
          id="allowLineBreaksInTitles"
          name="allowLineBreaksInTitles"
          v-model="form.allowLineBreaksInTitles"
          :disabled="readOnly"
        />

        <!-- Show the Slug field -->
        <CraftSwitch
          :label="t('Show the Slug field')"
          id="showSlugField"
          name="showSlugField"
          v-model="form.showSlugField"
          :disabled="readOnly"
        />

        <!-- Slug Translation Method -->
        <CraftSelect
          v-if="showSlugTranslation"
          :label="t('Slug Translation Method')"
          :help-text="t('How should slugs be translated?')"
          id="slugTranslationMethod"
          name="slugTranslationMethod"
          v-model="form.slugTranslationMethod"
          :disabled="readOnly"
          :error="errors?.slugTranslationMethod"
        >
          <select slot="input">
            <option
              v-for="option in translationMethodOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </CraftSelect>

        <!-- Slug Translation Key Format -->
        <CraftInput
          v-if="showSlugTranslationKeyFormat"
          :label="t('Slug Translation Key Format')"
          id="slugTranslationKeyFormat"
          name="slugTranslationKeyFormat"
          v-model="form.slugTranslationKeyFormat"
          :disabled="readOnly"
          :error="errors?.slugTranslationKeyFormat"
          class="font-mono"
        />

        <!-- Show the Status field -->
        <CraftSwitch
          :label="t('Show the Status field')"
          id="showStatusField"
          name="showStatusField"
          v-model="form.showStatusField"
          :disabled="readOnly"
        />
      </div>

      <hr class="my-6" />

      <!-- Field Layout -->
      <div class="grid gap-3">
        <div>
          <h3 class="font-bold text-sm">{{ t('Field Layout') }}</h3>
          <p class="text-sm text-neutral-500 mb-2">
            {{
              t('Define the field layout for {type} of this type.', {
                type: lowerTypeName,
              })
            }}
          </p>

          <!--
            DEFERRED: the field layout designer UI isn't wired up yet (the
            craft-field-layout-designer web component is mid-rewrite). The
            existing layout is round-tripped via form.fieldLayout so saving
            preserves it; replace this placeholder with the designer once the
            component is interactive.
          -->
          <craft-callout variant="info" appearance="outline-fill">
            {{
              t(
                'The field layout designer will be available here soon. Existing field layouts are preserved when saving.'
              )
            }}
          </craft-callout>
        </div>
      </div>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>

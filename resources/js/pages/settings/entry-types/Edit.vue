<script setup lang="ts">
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import {t, toHandle} from '@craftcms/cp';
  import {computed, ref} from 'vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/cp/vue/CraftInputHandle.vue';
  import CraftTextarea from '@craftcms/cp/vue/CraftTextarea.vue';
  import CraftSwitch from '@craftcms/cp/vue/CraftSwitch.vue';
  import CraftSelect from '@craftcms/cp/vue/CraftSelect.vue';
  import CraftSelectColor from '@craftcms/cp/vue/CraftSelectColor.vue';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import useCraftData from '@/common/composables/useCraftData';
  import Pane from '@/common/components/Pane.vue';
  import {store} from '@/actions/CraftCms/Cms/Http/Controllers/Settings/EntryTypesController';
  import type {SelectOption} from '@/common/types';
  import IconPicker from '@/common/form/IconPicker.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';

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
    color: string | null;
    icon: string | null;
  }

  const props = defineProps<{
    title: string;
    crumbs: Array<any>;
    entryType: EntryTypeData;
    brandNew: boolean;
    fieldLayoutDesigner: {html: string};
    translationMethodOptions: Array<SelectOption>;
    typeName: string;
    lowerTypeName: string;
    isMultiSite: boolean;
    flash?: Record<any, any>;
    errors: Record<any, any> | null;
    metadataHtml: string | null;
    formActions?: Array<any>;
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
    color: props.entryType.color ?? '__blank__',
    icon: props.entryType.icon ?? '',
    // `fieldLayout` (+ generatedFields / card view) are merged in at submit from
    // the designer's own inputs — see the transform passed to useSettingsSave.
  });

  // The field layout designer and generated-fields table render inside
  // `fieldLayoutDesigner.html` as self-booting custom elements (they boot,
  // teardown, and reboot across the after-save DOM swap via their own
  // connected/disconnected callbacks). Nothing to wire up here — `fldHost` just
  // scopes the submit-time `serialize()` queries below, since the elements live
  // in the compiled markup and can't be reffed directly.
  const fldHost = ref<HTMLElement>();

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
    () =>
      props.isMultiSite &&
      form.titleTranslationMethod === 'custom'
  );
  const showSlugTranslation = computed(
    () => form.showSlugField && props.isMultiSite
  );
  const showSlugTranslationKeyFormat = computed(
    () =>
      form.showSlugField &&
      form.slugTranslationMethod === 'custom' &&
      props.isMultiSite
  );

  // Inertia posts the form object, not the designer's own inputs, so read the
  // values back out of the self-booting custom elements at submit. `fieldLayout`
  // must stay a string (the server `JsonHelper::decode()`s it); `generatedFields`
  // is the ordered row list the server `array_values()`-es.
  const {save} = useSettingsSave(form, store, {
    transform: (data) => ({
      ...data,
      fieldLayout:
        fldHost.value
          ?.querySelector('craft-field-layout-designer')
          ?.serialize() ?? '{}',
      generatedFields:
        fldHost.value
          ?.querySelector('craft-generated-fields-table')
          ?.serialize() ?? [],
    }),
  });

  // "Save as a new {type}" submits the current form (including the field layout)
  // with a `saveAsNew` flag, so on-screen edits are carried into the duplicate —
  // rather than a standalone request that would clone the last-saved version.
  // Delete (and any other server-defined actions) come from `props.formActions`.
  const formActionItems = computed(() => {
    const actions = [...(props.formActions ?? [])];

    if (!props.brandNew) {
      actions.unshift({
        label: t('Save as a new {type}', {type: props.lowerTypeName}),
        // Don't preserve state — we navigate to the *new* type's edit screen, so
        // the form must re-initialize from the new props (not keep this record's).
        onClick: () => save({data: {saveAsNew: true}, preserveState: false}),
      });
    }

    return actions;
  });
</script>

<template>
  <AppLayout
    :title="title"
    :form="form"
    @save="save"
    :form-actions="formActionItems"
  >
    <div class="grid gap-6 grid-cols-4">
      <Pane appearance="raised" class="col-span-3">
        <craft-field-group>
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

          <IconPicker
            :label="t('Icon')"
            name="icon"
            v-model="form.icon"
            :disabled="readOnly"
            :error="errors?.icon"
          />

          <CraftSelectColor
            :label="t('Color')"
            allow-transparent
            v-model="form.color"
            :disabled="readOnly"
            :error="errors?.color"
          />

          <!-- UI Label Format -->
          <CraftInput
            :label="t('UI Label Format')"
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
            monospace
          />

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
            monospace
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

          <div ref="fldHost">
            <DynamicHtmlRenderer :html="fieldLayoutDesigner.html" />
          </div>
        </craft-field-group>
      </Pane>
      <div class="col-span-1">
        <div class="sticky top-4">
          <DynamicHtmlRenderer :html="metadataHtml" v-if="metadataHtml" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped lang="scss"></style>

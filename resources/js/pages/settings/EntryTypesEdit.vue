<script setup lang="ts">
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import {t, toHandle} from '@craftcms/cp';
  import {computed, ref, watch} from 'vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/cp/vue/CraftInputHandle.vue';
  import CraftTextarea from '@craftcms/cp/vue/CraftTextarea.vue';
  import CraftSwitch from '@craftcms/cp/vue/CraftSwitch.vue';
  import CraftSelect from '@craftcms/cp/vue/CraftSelect.vue';
  import CraftSelectColor from '@craftcms/cp/vue/CraftSelectColor.vue';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import {useFieldLayoutDesigner} from '@/common/composables/useFieldLayoutDesigner';
  import {useGeneratedFieldsTable} from '@/common/composables/useGeneratedFieldsTable';
  import useCraftData from '@/common/composables/useCraftData';
  import Pane from '@/common/components/Pane.vue';
  import {store} from '@actions/Settings/EntryTypesController';
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
    sidebarDetails: any;
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

  // Boot the (legacy) field layout designer and read its value back at submit.
  const fldHost = ref<HTMLElement>();
  const fld = useFieldLayoutDesigner(fldHost);
  // The generated-fields table lives inside the designer markup; read its value
  // back at submit too (Inertia doesn't post its distributed inputs). It's a
  // custom element, so it re-boots itself when the markup is swapped.
  const generatedFieldsTable = useGeneratedFieldsTable(fldHost);

  // After a save, Inertia replaces the designer markup via `v-html`, which
  // orphans the imperatively-booted FLD/CVD (drag handles go dead). Re-boot it
  // whenever its html changes (destroying the old instance first). The
  // non-immediate watch doesn't fire on first render, so it never double-boots.
  watch(
    () => props.fieldLayoutDesigner.html,
    () => fld.reboot()
  );

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

  const {save} = useSettingsSave(form, store, {
    transform: (data) => ({
      ...data,
      fieldLayout: fld.serialize(),
      generatedFields: generatedFieldsTable.serialize(),
    }),
  });
</script>

<template>
  <AppLayout :title="title" :form="form" @save="save">
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

          <IconPicker :label="t('Icon')" name="icon" v-model="form.icon" />

          <CraftSelectColor
            :label="t('Color')"
            allow-transparent
            v-model="form.color"
          />

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
            monospaced
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

          <div ref="fldHost" v-html="fieldLayoutDesigner.html"></div>
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

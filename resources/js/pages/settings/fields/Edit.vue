<script setup lang="ts">
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useForm} from '@inertiajs/vue3';
  import {actionClient, serializeFormInputs, t, toHandle} from '@craftcms/ui';
  import {computed, ref, watch} from 'vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/ui/vue/CraftInputHandle.vue';
  import CraftTextarea from '@craftcms/ui/vue/CraftTextarea.vue';
  import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
  import CraftSelect from '@craftcms/ui/vue/CraftSelect.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {FormPayload} from '@/modules/forms/types';
  import Pane from '@/common/components/Pane.vue';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import {
    renderSettings,
    store,
  } from '@/actions/CraftCms/Cms/Http/Controllers/FieldsController';

  interface FieldTypeOption {
    value: string;
    label: string;
    icon: string | null;
    id: string;
    compatible: boolean;
  }

  const props = defineProps<{
    field: {
      id: number | null;
      name: string | null;
      handle: string | null;
      instructions: string | null;
      searchable: boolean;
      type: string;
      translationMethod: string;
      translationKeyFormat: string | null;
    };
    brandNew: boolean;
    fieldTypeOptions: Array<FieldTypeOption>;
    supportedTranslationMethods: Record<string, string[]>;
    translationMethodOptions: Array<{value: string; label: string}>;
    isMultiSite: boolean;
    settings: CraftCms.Cms.View.HtmlFragment | null;
    settingsForm: FormPayload | null;
    readOnly: boolean;
    metadataHtml: string | null;
    missingFieldPlaceholder: string | null;
    formActions?: Array<any>;
  }>();

  const form = useForm({
    fieldId: props.field.id,
    type: props.field.type,
    name: props.field.name ?? '',
    handle: props.field.handle ?? '',
    instructions: props.field.instructions ?? '',
    searchable: props.field.searchable,
    translationMethod: props.field.translationMethod,
    translationKeyFormat: props.field.translationKeyFormat ?? '',
    settings: {} as Record<string, any>,
  });

  // Auto-generate the handle from the name until the user edits it directly.
  const handleGenerator = useInputGenerator(
    () => form.name,
    (value) => (form.handle = toHandle(value))
  );

  if (!props.brandNew || props.field.handle) {
    handleGenerator.stop();
  }

  // Types that have not adopted Form remain legacy HTML islands. Their inputs
  // are serialized out of the DOM when the type changes or the field is saved.
  const settingsHost = ref<HTMLElement | null>(null);
  const settingsFragment = ref<CraftCms.Cms.View.HtmlFragment | null>(
    props.settings
  );
  const settingsPayload = ref<FormPayload | null>(props.settingsForm);
  const settingsRenderer = ref<{
    advanceBaseline: () => void;
    currentValues: () => FormPayload['values'];
  } | null>(null);
  const settingsLoading = ref(false);
  let settingsRequestId = 0;

  const typeOptionFor = (type: string | undefined) =>
    props.fieldTypeOptions.find((option) => option.value === type);
  const currentTypeOption = computed(() => typeOptionFor(form.type));

  watch(
    () => form.type,
    async (type, oldType) => {
      // Serialize before the island unmounts so compatible settings carry over.
      const settings = settingsHost.value
        ? serializeFormInputs(settingsHost.value)
        : '';
      const values = settingsRenderer.value?.currentValues().settings;

      const requestId = ++settingsRequestId;
      settingsLoading.value = true;

      try {
        const {data} = await actionClient.post(renderSettings().url, {
          type,
          oldType,
          settings,
          values,
          namespace: `types[${typeOptionFor(type)?.id ?? ''}]`,
          oldNamespace: `types[${typeOptionFor(oldType)?.id ?? ''}]`,
        });

        if (requestId !== settingsRequestId) {
          return;
        }

        settingsPayload.value = data.form ?? null;
        settingsFragment.value = data.form
          ? null
          : {
              html: data.settingsHtml ?? '',
              headHtml: data.headHtml ?? '',
              bodyHtml: data.bodyHtml ?? '',
            };
      } catch {
        // Keep the last valid presentation and current values.
      } finally {
        if (requestId === settingsRequestId) {
          settingsLoading.value = false;
        }
      }

      // Keep the translation method valid for the new type.
      const supported = props.supportedTranslationMethods[type] ?? [];

      if (!supported.includes(form.translationMethod)) {
        form.translationMethod = supported[0] ?? 'none';
      }
    }
  );

  const availableTranslationMethods = computed(
    () => props.supportedTranslationMethods[form.type] ?? []
  );
  const translationMethodOptions = computed(() =>
    props.translationMethodOptions.filter((option) =>
      availableTranslationMethods.value.includes(option.value)
    )
  );
  const showTranslationSettings = computed(
    () => props.isMultiSite && translationMethodOptions.value.length > 1
  );
  const showTranslationKeyFormat = computed(
    () => showTranslationSettings.value && form.translationMethod === 'custom'
  );

  const showTypeChangeWarning = computed(
    () => !props.brandNew && !form.errors.type
  );

  const settingsErrors = computed(() =>
    Object.entries(form.errors)
      .filter(([path]) => path.startsWith('settings.'))
      .map(([path, message]) => ({
        path: path.split('.'),
        messages: [String(message)],
      }))
  );

  async function refreshSettings(
    values: FormPayload['values']
  ): Promise<FormPayload> {
    const {data} = await actionClient.post(renderSettings().url, {
      type: form.type,
      oldType: form.type,
      values: values.settings,
    });

    if (!data.form) {
      throw new Error('The field type did not return a Form payload.');
    }

    return data.form;
  }

  const {save} = useSettingsSave(form, store, {
    transform: (data) => ({
      ...data,
      settings: settingsPayload.value ? data.settings : undefined,
      typeSettings: settingsHost.value
        ? serializeFormInputs(settingsHost.value)
        : '',
    }),
    onSuccess: () => settingsRenderer.value?.advanceBaseline(),
  });

  const formActionItems = computed(() => [
    {
      label: t('Save and add another'),
      // Lands back on this screen with fresh props (the saved type
      // preselected), so the form must re-initialize rather than keep state.
      onClick: () => save({data: {addAnother: 1}, preserveState: false}),
    },
    // Server-defined actions (Delete on existing fields)
    ...(props.formActions ?? []),
  ]);

  useAppLayout(() => ({
    form,
    onSave: save,
    formActions: formActionItems.value,
  }));
</script>

<template>
  <div class="grid gap-6 grid-cols-4">
    <Pane
      appearance="raised"
      :class="metadataHtml ? 'col-span-3' : 'col-span-4'"
    >
      <craft-field-group>
        <CraftInput
          :label="t('Name')"
          :help-text="t('What this field will be called in the control panel.')"
          id="name"
          name="name"
          v-model="form.name"
          :error="form.errors.name"
          :disabled="readOnly"
          required
          autofocus
        />

        <CraftInputHandle
          :label="t('Handle')"
          :help-text="t('How you’ll refer to this field in the templates.')"
          id="handle"
          name="handle"
          v-model="form.handle"
          :error="form.errors.handle"
          :disabled="readOnly"
          required
          @change="handleGenerator.markDirty()"
        />

        <CraftTextarea
          :label="t('Default Instructions')"
          :help-text="t('Helper text to guide the author.')"
          id="instructions"
          name="instructions"
          v-model="form.instructions"
          :error="form.errors.instructions"
          :disabled="readOnly"
        />

        <CraftSwitch
          :label="t('Use this field’s values as search keywords')"
          id="searchable"
          name="searchable"
          v-model="form.searchable"
          :disabled="readOnly"
        />

        <CraftSelect
          :label="t('Field Type')"
          :help-text="t('What type of field is this?')"
          id="type"
          name="type"
          v-model="form.type"
          :error="form.errors.type"
          :disabled="readOnly"
        >
          <select slot="input" :disabled="readOnly">
            <option
              v-for="option in fieldTypeOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.compatible ? option.label : `${option.label} ⚠` }}
            </option>
          </select>

          <craft-callout
            slot="after"
            appearance="plain"
            class="p-0"
            icon="warning"
            variant="danger"
            v-if="showTypeChangeWarning"
          >
            {{ t('Changing this may result in data loss.') }}
          </craft-callout>
        </CraftSelect>

        <div v-if="missingFieldPlaceholder" v-html="missingFieldPlaceholder" />

        <template v-if="showTranslationSettings">
          <CraftSelect
            :label="t('Translation Method')"
            :help-text="t('How should this field’s values be translated?')"
            id="translationMethod"
            name="translationMethod"
            v-model="form.translationMethod"
            :error="form.errors.translationMethod"
            :disabled="readOnly"
          >
            <select slot="input" :disabled="readOnly">
              <option
                v-for="option in translationMethodOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </CraftSelect>

          <CraftInput
            v-if="showTranslationKeyFormat"
            :label="t('Translation Key Format')"
            :help-text="
              t(
                'Template that defines the field’s custom “translation key” format. Field values will be copied to all sites that produce the same key.'
              )
            "
            id="translationKeyFormat"
            name="translationKeyFormat"
            v-model="form.translationKeyFormat"
            :error="form.errors.translationKeyFormat"
            :disabled="readOnly"
            monospace
          />
        </template>

        <hr />

        <div ref="settingsHost">
          <div :id="currentTypeOption?.id">
            <div v-if="settingsLoading" class="flex justify-center p-4">
              <craft-spinner></craft-spinner>
            </div>
            <FormRenderer
              v-if="settingsPayload"
              ref="settingsRenderer"
              :payload="settingsPayload"
              :refresh="refreshSettings"
              :errors="settingsErrors"
              @update:mutation="form.settings = $event"
            />
            <HtmlFragmentRenderer
              v-else
              as="craft-field-group"
              :fragment="settingsFragment"
            />
          </div>
        </div>
      </craft-field-group>
    </Pane>
    <div class="col-span-1" v-if="metadataHtml">
      <div class="sticky top-4">
        <DynamicHtmlRenderer :html="metadataHtml" />
      </div>
    </div>
  </div>
</template>

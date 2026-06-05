<script setup lang="ts" generic="T">
  import {t, toEnvVar} from '@craftcms/ui';
  import {type InertiaForm, usePage} from '@inertiajs/vue3';
  import {computed} from 'vue';
  import type {SelectItem, SelectOption, Site} from '@/common/types';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {toHandle} from '@craftcms/ui/utilities/string.ts.mjs';
  import useCraftData from '@/common/composables/useCraftData';
  import CraftCombobox from '@/common/form/CraftCombobox.vue';
  import {transformBooleanOptions} from '@/common/utils/transformBooleanOptions';

  const props = defineProps<{
    inertiaForm: InertiaForm<any>;
  }>();

  const page = usePage<{
    isMultisite: boolean;
    site: Site;
    nameSuggestions?: Array<SelectItem>;
    languageOptions: Array<SelectItem>;
    baseUrlSuggestions: Array<SelectItem>;
    booleanEnvOptions: Array<SelectItem>;
    groupOptions: Array<SelectOption>;
  }>();
  const {readOnly} = useCraftData();

  const form = computed(() => props.inertiaForm);
  const isMultisite = computed(() => page.props.isMultisite);
  const groupOptions = computed(() => page.props.groupOptions);
  const nameSuggestions = computed(() => {
    return page.props.nameSuggestions;
  });
  const languageOptions = computed(() => page.props.languageOptions);
  const booleanEnvOptions = computed(() =>
    transformBooleanOptions(page.props.booleanEnvOptions)
  );
  const baseUrlSuggestions = computed(() => page.props.baseUrlSuggestions);
  const site = computed(() => page.props.site);

  const handleGenerator = useInputGenerator(
    () => form.value.name,
    (v) => (form.value.handle = toHandle(v))
  );

  const envVarGenerator = useInputGenerator(
    () => form.value.name,
    (value) =>
      (form.value.baseUrl = toEnvVar(value, {
        prefix: '$',
        suffix: '_URL',
      }))
  );

  // For existing sites, mark handle as already dirty
  if (form.value.id) {
    handleGenerator.stop();
    envVarGenerator.stop();
  }
</script>

<template>
  <input v-if="form.id" name="id" v-model="form.id" type="hidden" />

  <craft-select
    :label="t('Group')"
    :help-text="t('Which group should this site belong to?')"
    name="group"
    id="group"
    .model-value="form.group"
    @model-value-changed="form.group = $event.target?.modelValue"
    :disabled="readOnly"
  >
    <select slot="input">
      <option
        v-for="option in groupOptions"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>

    <ul class="error-list" v-if="form.errors?.group" slot="feedback">
      <li v-for="error in form.errors?.group" :key="error">{{ error }}</li>
    </ul>

    <div slot="after" v-if="form?.id && isMultisite">
      <craft-callout
        variant="danger"
        appearance="plain"
        class="p-0"
        icon="triangle-exclamation"
      >
        <span class="sr-only">{{ t('Warning:') }}</span>
        {{ t('Changing this may result in data loss.') }}
      </craft-callout>
    </div>
  </craft-select>

  <CraftCombobox
    v-model="form.name"
    :options="nameSuggestions"
    :label="t('Name')"
    id="name"
    name="name"
    :disabled="readOnly"
    :error="form.errors?.name"
  >
    <div slot="after">
      <craft-callout
        variant="info"
        appearance="plain"
        class="p-0"
        icon="lightbulb"
      >
        {{ t('This can begin with an environment variable.') }}
        <a
          href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
          >{{ t('Learn more') }}</a
        >
      </craft-callout>
    </div>
  </CraftCombobox>

  <craft-input-handle
    :label="t('Handle')"
    :help-text="t(`How you’ll refer to this site in the templates.`)"
    ref="handle"
    id="handle"
    name="handle"
    :has-feedback-for="form.errors?.handle ? 'error' : ''"
    :disabled="readOnly"
    v-model="form.handle"
  >
    <div slot="feedback">
      <ul class="error-list" v-if="form.errors?.handle">
        <li>{{ form.errors.handle }}</li>
      </ul>
    </div>
  </craft-input-handle>

  <CraftCombobox
    v-model="form.language"
    :label="t('Language')"
    name="language"
    id="site-language"
    :help-text="t('The language content in this site will use.')"
    :options="languageOptions"
    :disabled="readOnly"
    :error="form.errors?.language"
    :require-option-match="true"
  >
    <template #after>
      <craft-callout
        variant="info"
        appearance="plain"
        class="p-0"
        icon="lightbulb"
        v-html="
          t(
            'This can be set to an environment variable with a valid language ID ({examples}).',
            {
              examples: '<code>en</code>/<code>en-GB</code>',
            }
          )
        "
      >
      </craft-callout>
    </template>
  </CraftCombobox>

  <template v-if="isMultisite || !site.id">
    <CraftCombobox
      :label="t('Status')"
      name="enabled"
      id="enabled"
      :disabled="readOnly"
      v-model="form.enabled"
      :require-option-match="true"
      :options="booleanEnvOptions"
      :error="form.errors?.enabled"
    >
      <div slot="after">
        <craft-callout
          v-if="site.primary"
          variant="warning"
          appearance="plain"
          class="p-0"
          icon="lightbulb"
        >
          {{ t('The primary site cannot be disabled.') }}
        </craft-callout>

        <craft-callout
          variant="info"
          appearance="plain"
          class="p-0"
          icon="lightbulb"
          v-html="
            t(
              'This can be set to an environment variable with a boolean value ({examples})',
              {
                examples:
                  '<code>yes</code>/<code>no</code>/<code>true</code>/<code>false</code>/<code>on</code>/<code>off</code>/<code>0</code>/<code>1</code>',
              }
            )
          "
        >
        </craft-callout>
      </div>
    </CraftCombobox>
  </template>

  <template v-if="(isMultisite || !site.id) && !site.primary">
    <craft-switch
      :label="t('Make this the primary site')"
      :help-text="
        t('The primary site will be loaded by default on the front end.')
      "
      :disabled="readOnly"
      :checked="form.primary"
      @checked-changed="form.primary = $event.target?.checked"
      v-if="!site.primary"
    >
    </craft-switch>
  </template>

  <craft-switch
    :label="t('This site has its own base URL')"
    id="has-urls"
    name="hasUrls"
    :disabled="readOnly"
    :checked="form.hasUrls"
    @checked-changed="form.hasUrls = $event.target?.checked"
  >
  </craft-switch>

  <template v-if="form.hasUrls">
    <CraftCombobox
      v-model="form.baseUrl"
      :label="t('Base URL')"
      :help-text="t('The base URL for the site.')"
      id="base-url"
      name="baseUrl"
      :error="form.errors?.baseUrl"
      :options="baseUrlSuggestions"
      :disabled="readOnly"
    >
      <template #after>
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
</template>

<style scoped lang="scss"></style>

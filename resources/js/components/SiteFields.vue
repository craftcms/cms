<script setup lang="ts" generic="T">
  import {t, toEnvVar, toHandle} from '@craftcms/cp';
  import {type InertiaForm, usePage} from '@inertiajs/vue3';
  import {computed, useTemplateRef, watch} from 'vue';
  import type {SelectItem, SelectOption, Site} from '@/types';
  import {CraftInput} from '../../../packages/craftcms-cp/src';
  import InputCombobox from '@/components/InputCombobox.vue';

  const props = withDefaults(
    defineProps<{
      inertiaForm: InertiaForm<any>;
      readOnly?: boolean;
    }>(),
    {readOnly: false}
  );

  const page = usePage<{
    isMultisite: boolean;
    site: Site;
    nameSuggestions?: Array<SelectItem>;
    languageOptions: Array<SelectItem>;
    baseUrlSuggestions: Array<SelectItem>;
    booleanEnvOptions: Array<SelectItem>;
    groupOptions: Array<SelectOption>;
  }>();

  function addBooleanHints(option: SelectOption) {
    const isEnvVar =
      option.value.startsWith('$') || option.value.startsWith('@');

    return isEnvVar
      ? {
          ...option,
          data: {
            ...(option.data || {}),
            hint: option.data?.boolean === '1' ? t('Enabled') : t('Disabled'),
          },
        }
      : option;
  }

  const form = computed(() => props.inertiaForm);
  const isMultisite = computed(() => page.props.isMultisite);
  const groupOptions = computed(() => page.props.groupOptions);
  const nameSuggestions = computed(() => {
    return page.props.nameSuggestions;
  });
  const languageOptions = computed(() => page.props.languageOptions);
  const booleanEnvOptions = computed(() =>
    page.props.booleanEnvOptions.map((option) => {
      if (option.type === 'optgroup') {
        return {
          ...option,
          options: option.options.map(addBooleanHints),
        };
      }

      return addBooleanHints(option);
    })
  );
  const baseUrlSuggestions = computed(() => page.props.baseUrlSuggestions);
  const site = computed(() => page.props.site);

  const handleRef = useTemplateRef<CraftInput | null>('handle');
  const baseUrlRef = useTemplateRef<CraftInput | null>('baseUrl');

  const enabledValue = computed({
    get() {
      return form.value.enabled ? '1' : '0';
    },
    set(newValue) {
      form.value.enabled = newValue;
    },
  });

  watch(
    () => form.value?.name,
    (newValue) => {
      if (!handleRef.value?.dirty) {
        form.value.handle = toHandle(newValue);
      }

      if (!baseUrlRef.value?.dirty) {
        form.value.baseUrl = toEnvVar(newValue, {
          prefix: '$',
          suffix: '_URL',
        });
      }
    }
  );
</script>

<template>
  <template v-if="form?.hasErrors">
    <craft-callout variant="danger" icon="triangle-exclamation">
      <div slot="title" class="tw:font-bold">
        {{ t('Could not save settings') }}
      </div>
      <ul>
        <li v-for="(error, idx) in form.errors" :key="idx">
          {{ error }}
        </li>
      </ul>
    </craft-callout>
  </template>
  <input v-if="form.id" name="id" v-model="form.id" type="hidden" />

  <craft-select
    :label="t('Group')"
    :help-text="t('Which group should this site belong to?')"
    name="group"
    id="group"
    .modelValue="form.group"
    @model-value-changed="form.group = $event.target?.modelValue"
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
      <li v-for="error in form.errors?.group">{{ error }}</li>
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

  <craft-input :label="t('Name')" id="name" name="name" :disabled="readOnly">
    <InputCombobox
      slot="input"
      v-model="form.name"
      :options="nameSuggestions"
    />

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

    <div slot="feedback">
      <ul class="error-list" v-if="form.errors?.name">
        <li>{{ form.errors.name }}</li>
      </ul>
    </div>
  </craft-input>

  <craft-input-handle
    :label="t('Handle')"
    :help-text="t(`How you’ll refer to this site in the templates.`)"
    ref="handle"
    id="handle"
    name="handle"
    :has-feedback-for="form.errors?.handle ? 'error' : ''"
    v-model="form.handle"
  >
    <div slot="feedback">
      <ul class="error-list" v-if="form.errors?.handle">
        <li>{{ form.errors.handle }}</li>
      </ul>
    </div>
  </craft-input-handle>

  <craft-input
    :label="t('Language')"
    name="language"
    id="site-language"
    :help-text="t('The language content in this site will use.')"
    :disabled="readOnly"
    :has-feedback-for="form.errors?.language ? 'error' : ''"
  >
    <InputCombobox
      slot="input"
      v-model="form.language"
      :options="languageOptions"
      :require-option-match="true"
    />

    <div slot="after">
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
    </div>

    <div slot="feedback">
      <ul class="error-list" v-if="form.errors?.language">
        <li>{{ form.errors.language }}</li>
      </ul>
    </div>
  </craft-input>

  <template v-if="isMultisite || !site.id">
    <craft-input
      :label="t('Status')"
      name="enabled"
      id="enabled"
      :disabled="readOnly"
      :has-feedback-for="form.errors?.enabled ? 'error' : ''"
    >
      <InputCombobox
        slot="input"
        v-model="enabledValue"
        :options="booleanEnvOptions"
        :require-option-match="true"
      >
        <template #option="{active, selected, option}">
          <craft-option
            :active="active"
            :checked="selected"
            :hint="option.data?.hint"
          >
            <div class="inline-flex items-center gap-1">
              <craft-indicator
                :variant="option.data?.boolean === '1' ? 'success' : 'empty'"
              ></craft-indicator>
              <code
                v-if="
                  option.label.startsWith('$') || option.label.startsWith('@')
                "
                >{{ option.label }}</code
              >
              <span v-else>{{ option.label }}</span>
            </div>
          </craft-option>
        </template>
      </InputCombobox>
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

      <div slot="feedback">
        <ul class="error-list" v-if="form.errors?.enabled">
          <li>{{ form.errors.enabled }}</li>
        </ul>
      </div>
    </craft-input>
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
    <craft-input
      :label="t('Base URL')"
      :help-text="t('The base URL for the site.')"
      id="base-url"
      name="baseUrl"
      :error="form.errors?.baseUrl"
      :disabled="readOnly"
    >
      <InputCombobox
        slot="input"
        v-model="form.baseUrl"
        :options="baseUrlSuggestions"
      />

      <div slot="after">
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
      </div>
    </craft-input>
  </template>
</template>

<style scoped lang="scss"></style>

<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import FieldTip from '@/common/components/FieldTip.vue';
  import {envVarsDocsUrl} from '@/common/utils/docsUrls';
  import Select from '@/common/form/Select.vue';
  import {usePage} from '@inertiajs/vue3';
  import type {BaseOption, SelectItem} from '@/common/types';

  withDefaults(
    defineProps<{
      localeOptions: Array<BaseOption & {id: string}>;
      errors?: Record<string, string>;
    }>(),
    {errors: () => ({}), localeOptions: () => []}
  );

  const name = defineModel<string>('name');
  const baseUrl = defineModel<string>('baseUrl');
  const language = defineModel<string>('language', {required: true});

  const page = usePage<{
    baseUrlTextExpanderTriggers: TextExpanderTriggers;
    languageOptions: Array<SelectItem>;
  }>();
</script>

<template>
  <CraftInput
    name="name"
    :label="t('System Name')"
    id="site-name"
    v-model="name"
    maxlength="255"
    :error="errors?.name"
  />

  <CraftInput
    v-model="baseUrl"
    :label="t('Base URL')"
    :help-text="t('The base URL for the site.')"
    id="base-url"
    name="baseUrl"
    :error="errors?.baseUrl"
    :text-expander-triggers="page.props.baseUrlTextExpanderTriggers"
  >
    <FieldTip
      slot="tip"
      :message="
        t(
          'Type <kbd>$</kbd> to choose an environment variable, or <kbd>@</kbd> to choose an alias.'
        )
      "
      :href="envVarsDocsUrl"
      html
    />
  </CraftInput>

  <Select
    v-model="language"
    :options="localeOptions"
    :label="t('Language')"
    id="site-language"
    name="language"
  >
    <template #option-label="{option}">
      {{ option.value }} ({{ option.label }})
    </template>
  </Select>
</template>

<style scoped lang="scss"></style>

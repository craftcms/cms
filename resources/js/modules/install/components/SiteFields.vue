<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {useFocusField} from '@/common/composables/useFocusField';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftCombobox from '@/common/form/CraftCombobox.vue';
  import Select from '@/common/form/Select.vue';
  import {usePage} from '@inertiajs/vue3';
  import type {BaseOption, SelectItem} from '@/common/types';

  const emit = defineEmits<{
    (e: 'update:modelValue', data: any): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue?: any;
      localeOptions: Array<BaseOption & {id: string}>;
      errors?: Record<string, string>;
    }>(),
    {modelValue: () => ({}), errors: () => ({}), localeOptions: () => []}
  );

  const page = usePage<{
    baseUrlSuggestions: Array<SelectItem>;
    languageOptions: Array<SelectItem>;
  }>();

  const model = computed({
    get() {
      return props.modelValue;
    },
    set(value) {
      emit('update:modelValue', value);
    },
  });

  useFocusField('site-name');
</script>

<template>
  <CraftInput
    name="name"
    :label="t('System Name')"
    id="site-name"
    v-model="model.name"
    maxlength="255"
    ref="site-name"
    :error="errors?.name"
  />

  <CraftCombobox
    v-model="model.baseUrl"
    :label="t('Base URL')"
    :help-text="t('The base URL for the site.')"
    id="base-url"
    name="baseUrl"
    :error="errors?.baseUrl"
    :options="page.props.baseUrlSuggestions"
  >
    <template #after>
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
    </template>
  </CraftCombobox>

  <Select
    v-model="model.language"
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

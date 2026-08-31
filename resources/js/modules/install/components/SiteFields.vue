<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';
  import {computed} from 'vue';
  import {useFocusField} from '@/common/composables/useFocusField';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
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
    baseUrlTextExpanderTriggers: TextExpanderTriggers;
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

  <CraftInput
    v-model="model.baseUrl"
    :label="t('Base URL')"
    :help-text="t('The base URL for the site.')"
    id="base-url"
    name="baseUrl"
    :error="errors?.baseUrl"
    :text-expander-triggers="page.props.baseUrlTextExpanderTriggers"
  >
    <craft-callout
      slot="after"
      variant="info"
      appearance="plain"
      class="p-0"
      icon="lightbulb"
    >
      {{
        t(
          'Type `$` to choose an environment variable, or `@` to choose an alias.'
        )
      }}
      <a
        href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
        >{{ t('Learn more') }}</a
      >
    </craft-callout>
  </CraftInput>

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

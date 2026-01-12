<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import type {SelectOption} from '@/types';
  import Combobox from '@/components/Combobox.vue';

  const modelValue = defineModel();
  withDefaults(
    defineProps<{
      label?: string;
      id?: string;
      name?: string;
      helpText?: string;
      options?: Array<SelectOption>;
      error?: any;
    }>(),
    {
      label: t('Language'),
      name: 'language',
      options: () => [],
    }
  );
</script>

<template>
  <Combobox
    :label="label"
    :id="id"
    :name="name"
    :help-text="helpText"
    :require-option-match="false"
    :has-feedback-for="error ? 'error' : ''"
    :show-all-on-empty="true"
    :options="options"
    :error="error"
    v-model="modelValue"
    v-bind="$attrs"
  >
    <template #after>
      <craft-callout
        variant="info"
        appearance="plain"
        class="p-0"
        icon="lightbulb"
        v-html="
          t(
            'This can be set to an environment variable with a valid language ID ({examples})',
            {examples: '<code>en</code>/<code>en-GB</code>'}
          )
        "
      >
      </craft-callout>
    </template>
  </Combobox>
</template>

<style scoped lang="scss"></style>

<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed} from 'vue';
  import {useFocusField} from '@/composables/useFocusField';

  const emit = defineEmits<{
    (e: 'update:modelValue', data: any): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue?: any;
      localeOptions?: Array<{id: string; name: string; selected: boolean}>;
      errors?: Record<string, string[]>;
    }>(),
    {modelValue: () => ({}), errors: () => ({}), localeOptions: () => []}
  );

  const model = computed({
    get() {
      return props.modelValue;
    },
    set(value) {
      emit('update:modelValue', value);
    },
  });

  function handleUpdate(event: CustomEvent) {
    const target = event.target as HTMLSelectElement & {modelValue: string};
    emit('update:modelValue', {
      ...model.value,
      language: target?.modelValue,
    });
  }

  useFocusField('site-name');
</script>

<template>
  <!-- @TODO add error output -->
  <craft-input
    name="name"
    :label="t('System Name')"
    id="site-name"
    v-model="model.name"
    maxlength="255"
    ref="site-name"
  >
  </craft-input>

  <!-- @TODO this should be autocomplete -->
  <craft-input name="baseUrl" :label="t('Base URL')" v-model="model.baseUrl">
  </craft-input>

  <craft-select
    :label="t('Language')"
    id="site-language"
    name="language"
    .modelValue="model.language"
    @model-value-changed="handleUpdate"
  >
    <select slot="input">
      <option
        v-for="locale in localeOptions"
        :key="locale.id"
        :selected="locale.id === model.language"
        :value="locale.id"
      >
        {{ locale.id }} ({{ locale.name }})
      </option>
    </select>
  </craft-select>
</template>

<style scoped lang="scss"></style>

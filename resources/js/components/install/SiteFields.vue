<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed} from 'vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', data: any): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue?: any;
      localeOptions?: Array<{id: string; name: string; selected: boolean}>;
      errors?: Record<string, string[]>;
    }>(),
    {modelValue: () => ({}), errors: () => ({})}
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
</script>

<template>
  <craft-input
    name="name"
    :label="t('app', 'System Name')"
    id="site-name"
    v-model="model.name"
    maxlength="255"
  >
    <!--    <ul class="error-list" v-if="validationErrors?.name" slot="feedback">-->
    <!--      <li v-for="error in validationErrors?.name">{{ error }}</li>-->
    <!--    </ul>-->
  </craft-input>

  <craft-input
    name="baseUrl"
    :label="t('app', 'Base URL')"
    v-model="model.baseUrl"
  >
    <!--              <div slot="input">Autosuggest</div>-->
  </craft-input>

  <craft-select
    :label="t('app', 'Language')"
    id="site-language"
    name="language"
    .modelValue="model.language"
    @model-value-changed="handleUpdate"
  >
    <craft-option
      v-for="locale in localeOptions"
      :key="locale.id"
      :selected="locale.id === model.language"
      .choiceValue="locale.id"
      >{{ locale.id }} ({{ locale.name }})</craft-option
    >
  </craft-select>
</template>

<style scoped lang="scss"></style>

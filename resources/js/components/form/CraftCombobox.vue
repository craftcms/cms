<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import InputCombobox from '@/components/form/InputCombobox.vue';
  import type {SelectItem} from '@/types';
  import {computed} from 'vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
  }>();
  const props = defineProps<{
    modelValue: string;
    label: string;
    id?: string;
    name?: string;
    disabled?: boolean;
    options?: Array<SelectItem>;
    callouts?: Array<string>;
    error?: string;
  }>();

  const valueProxy = computed({
    get() {
      return props.modelValue;
    },
    set(newValue) {
      emit('update:modelValue', newValue);
    },
  });
</script>

<template>
  <craft-input
    :label="label"
    :id="id"
    :name="name"
    :disabled="disabled"
    :has-feedback-for="error ? 'error' : ''"
    v-bind="$attrs"
  >
    <InputCombobox
      slot="input"
      v-model="valueProxy"
      :options="options"
      :label="label"
    />

    <div slot="after">
      <craft-callout
        v-if="callouts?.includes('envVars')"
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
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </craft-input>
</template>

<style scoped lang="scss"></style>

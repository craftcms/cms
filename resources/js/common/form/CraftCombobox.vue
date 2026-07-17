<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import InputCombobox from '@/common/form/InputCombobox.vue';
  import type {SelectItem} from '@/common/types';
  import {computed, useSlots} from 'vue';

  const modelValue = defineModel<string | number | boolean>();
  defineProps<{
    label: string;
    id?: string;
    name?: string;
    disabled?: boolean;
    clearable?: boolean;
    options?: Array<SelectItem>;
    callouts?: Array<string>;
    error?: string;
    requireOptionMatch?: boolean;
    showAllOnEmpty?: boolean;
    placeholder?: string;
  }>();

  const slots = useSlots();
  const forwardedSlots = computed(() => {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const {default: _, ...rest} = slots;
    return rest;
  });
</script>

<template>
  <craft-input
    :label="label"
    :id="id"
    :name="name"
    :disabled="disabled"
    :has-feedback-for="error ? 'error' : ''"
    :require-options-match="requireOptionMatch"
    v-bind="$attrs"
  >
    <InputCombobox
      slot="input"
      v-model="modelValue"
      :options="options"
      :label="label"
      :disabled="disabled"
      :clearable="clearable"
      :require-option-match="requireOptionMatch"
      :show-all-on-empty="showAllOnEmpty"
      :placeholder="placeholder"
    >
      <!-- Forward all other slots -->
      <template v-for="(_, slotName) in forwardedSlots" #[slotName]="slotData">
        <slot :name="slotName" v-bind="slotData || {}"></slot>
      </template>
    </InputCombobox>

    <div slot="after">
      <slot name="after">
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
      </slot>
    </div>

    <div slot="feedback">
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </craft-input>
</template>

<style scoped lang="scss"></style>

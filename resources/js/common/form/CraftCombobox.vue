<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import CraftComboboxControl from '@craftcms/ui/vue/CraftCombobox.vue';
  import type {SelectItem} from '@/common/types';

  const modelValue = defineModel<string>();
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
    limit?: number;
  }>();
</script>

<template>
  <CraftComboboxControl
    v-model="modelValue"
    :label="label"
    :id="id"
    :name="name"
    :disabled="disabled"
    :clearable="clearable"
    :options="options"
    :require-option-match="requireOptionMatch"
    :show-all-on-empty="showAllOnEmpty"
    :placeholder="placeholder"
    :limit="limit"
    :error="error"
  >
    <div slot="after">
      <slot name="after">
        <craft-callout
          v-if="callouts?.includes('envVars')"
          variant="info"
          appearance="plain"
          class="cp:p-0"
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
  </CraftComboboxControl>
</template>

<style scoped lang="scss"></style>

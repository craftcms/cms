<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import CraftComboboxControl from '@craftcms/ui/vue/CraftCombobox.vue';
  import InputCallout from '@/common/components/InputCallout.vue';
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
    <div slot="after" data-description>
      <slot name="after">
        <InputCallout
          v-if="callouts?.includes('envVars')"
          :message="t('This can begin with an environment variable.')"
          href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
        />
      </slot>
    </div>
  </CraftComboboxControl>
</template>

<style scoped lang="scss"></style>

<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import CraftComboboxControl from '@craftcms/ui/vue/CraftCombobox.vue';
  import FieldTip from '@/common/components/FieldTip.vue';
  import {envVarsDocsUrl} from '@/common/utils/docsUrls';
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
  defineSlots<{
    /**
     * Replaces the callouts below the input. Give the slotted element
     * `slot="after"`, plus `data-description` if it describes the input.
     */
    after?: () => unknown;
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
    <slot name="after">
      <FieldTip
        v-if="callouts?.includes('envVars')"
        slot="tip"
        :message="t('This can begin with an environment variable.')"
        :href="envVarsDocsUrl"
      />
    </slot>
  </CraftComboboxControl>
</template>

<style scoped lang="scss"></style>

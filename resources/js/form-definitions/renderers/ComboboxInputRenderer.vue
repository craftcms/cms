<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
  import {computed} from 'vue';
  import type {SelectItem} from '@/common/types';

  const props = defineProps<{
    options?: SelectItem[];
    placeholder?: string;
    allowAliases?: boolean;
    limit?: number;
    clearable?: boolean;
    modelValue?: unknown;
    readonly?: boolean;
  }>();

  const emit = defineEmits<{
    'update:modelValue': [value: string];
  }>();

  const value = computed({
    get: () => String(props.modelValue ?? ''),
    set: (value) => {
      if (!props.readonly) {
        emit('update:modelValue', value);
      }
    },
  });
</script>

<template>
  <CraftCombobox
    v-model="value"
    :options="options ?? []"
    :placeholder="placeholder"
    :limit="limit"
    :clearable="clearable"
    :disabled="readonly"
  >
    <craft-callout
      v-if="allowAliases"
      slot="after"
      variant="info"
      appearance="plain"
      class="p-0"
      icon="lightbulb"
    >
      {{ t('This can begin with an environment variable or alias.') }}
      <a
        href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
        >{{ t('Learn more') }}</a
      >
    </craft-callout>
  </CraftCombobox>
</template>

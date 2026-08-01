<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';
  import type {SelectItem} from '@/common/types';

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: string];
  }>();

  const value = computed({
    get: () => String(props.binding?.value ?? ''),
    set: (value) => emit('update:value', value),
  });
  const options = computed<SelectItem[]>(() =>
    Array.isArray(props.config.options)
      ? (props.config.options as unknown as SelectItem[])
      : []
  );
  const placeholder = computed(() =>
    typeof props.config.placeholder === 'string'
      ? props.config.placeholder
      : undefined
  );
  const allowAliases = computed(() => props.config.allowAliases === true);
  const limit = computed(() =>
    typeof props.config.limit === 'number' ? props.config.limit : undefined
  );
  const clearable = computed(() => props.config.clearable === true);
</script>

<template>
  <CraftCombobox
    v-bind="attributes"
    v-model="value"
    :options="options"
    :placeholder="placeholder"
    :limit="limit"
    :clearable="clearable"
    :disabled="binding?.readOnly"
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

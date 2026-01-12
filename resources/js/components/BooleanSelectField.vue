<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import type {SelectOption} from '@/types';
  import {computed} from 'vue';
  import {CraftCombobox} from '../../../packages/craftcms-cp/src';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean | string): void;
  }>();
  const props = withDefaults(
    defineProps<{
      label?: string;
      id?: string;
      name?: string;
      helpText?: string;
      modelValue?: any;
      options?: Array<SelectOption>;
      error?: any;
    }>(),
    {
      options: () => [],
    }
  );

  const computedOptions = computed(() => {
    return [
      {
        value: '1',
        label: 'Enabled',
        data: {
          boolean: '1',
        },
      },
      {
        value: '0',
        label: 'Disabled',
        data: {
          boolean: '0',
        },
      },
      ...props.options.filter((option) => Boolean(option.label)),
    ];
  });

  const modelProxy = computed({
    get() {
      if (typeof props.modelValue === 'boolean') {
        return props.modelValue ? '1' : '0';
      }

      if (props.modelValue === null) {
        return '0';
      }

      return props.modelValue;
    },
    set(newValue) {
      emit('update:modelValue', newValue);
    },
  });
</script>

<template>
  <craft-combobox
    :label="label"
    :id="id"
    :name="name"
    :help-text="helpText"
    require-option-match
    :has-feedback-for="error ? 'error' : ''"
    show-all-on-empty
    :options="computedOptions"
    .modelValue="modelProxy"
    @model-value-changed="modelProxy = $event.target?.modelValue"
    v-bind="$attrs"
  >
    <craft-option
      v-for="(item, idx) in computedOptions"
      :key="idx"
      .choiceValue="item.value"
    >
      <div class="flex items-center gap-1">
        <craft-indicator
          :variant="item.data?.boolean === '1' ? 'success' : 'danger'"
        ></craft-indicator>
        <span>{{ item.label }}</span>
      </div>
    </craft-option>

    <div slot="after">
      <slot name="after"></slot>
      <craft-callout
        slot="after"
        variant="info"
        appearance="plain"
        class="p-0"
        icon="lightbulb"
        v-html="
          t(
            'This can be set to an environment variable with a boolean value ({examples})',
            {
              examples:
                '<code>yes</code>/<code>no</code>/<code>true</code>/<code>false</code>/<code>on</code>/<code>off</code>/<code>0</code>/<code>1</code>',
            }
          )
        "
      >
      </craft-callout>
    </div>

    <div slot="feedback">
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </craft-combobox>
</template>

<style scoped lang="scss"></style>

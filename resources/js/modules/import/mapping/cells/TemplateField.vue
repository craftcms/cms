<script setup lang="ts">
  /**
   * A column's template, for when it's filled from one rather than a source column.
   * The server only reads it while the column is mapped to a template, so it's kept
   * when the column is switched away, and switching back doesn't lose it.
   */
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/text-expander/text-expander';
  import {t} from '@craftcms/ui';
  import {computed, useId} from 'vue';
  import {isInitialization} from '@/common/table/cells/types';
  import {getAt, setAt} from '../paths';
  import {useMappingContext, type MappingColumn} from '../types';

  const {col} = defineProps<{col: MappingColumn}>();

  const context = useMappingContext();
  const inputId = useId();

  const path = computed(() => col.prefixedHandleAsArray);

  const value = computed(() => {
    const template = getAt(context.values.templates, path.value);

    return template === null || template === undefined ? '' : String(template);
  });

  /** How the server's template errors are keyed: the column's path joined with dots. */
  const errorKey = computed(() => path.value.join('.'));

  const errors = computed<string[]>(
    () => context.templateErrors.value[errorKey.value] ?? []
  );

  function onChange(event: Event): void {
    if (isInitialization(event)) {
      return;
    }

    const next = (event.currentTarget as {modelValue?: unknown}).modelValue;

    setAt(
      context.values.templates,
      path.value,
      next == null ? '' : String(next)
    );
    delete context.templateErrors.value[errorKey.value];
  }
</script>

<template>
  <craft-field
    class="template-field"
    :label="t('Template')"
    label-sr-only
    :has-errors="errors.length > 0"
    width="full"
    required
  >
    <craft-input
      slot="input"
      monospace
      .modelValue="value"
      required
      :disabled="!context.editable"
      :aria-invalid="errors.length ? 'true' : undefined"
      @model-value-changed="onChange"
    >
      <input
        :id="inputId"
        slot="input"
        :data-mapping-template-invalid="
          errors.length ? context.panelId : undefined
        "
      />
    </craft-input>
    <craft-text-expander
      v-if="context.editable"
      slot="input"
      :for="inputId"
      .triggers="context.templateTextExpanderTriggers"
    />
    <span
      v-if="context.editable"
      slot="tip"
      v-html="t('Type <kbd>{</kbd> to choose an object template variable.')"
    ></span>
    <ul v-if="errors.length" slot="feedback" class="error-list">
      <li v-for="error in errors" :key="error">{{ error }}</li>
    </ul>
  </craft-field>
</template>

<style scoped>
  .template-field {
    white-space: normal;
  }
</style>

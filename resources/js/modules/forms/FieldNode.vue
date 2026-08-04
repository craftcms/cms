<script setup lang="ts">
  import '@craftcms/ui/components/field/field';
  import {computed, getCurrentInstance} from 'vue';
  import type {FormNodePayload, FormPayload} from './types';

  type FieldNodeProps = {
    label?: string | null;
    instructions?: string | null;
    required?: boolean;
  };

  const props = defineProps<{
    node: FormNodePayload<FieldNodeProps>;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
  }>();
  const components = getCurrentInstance()!.appContext.components;
  const control = computed(() => props.node.control!);
  const component = computed(() => {
    const component = components[control.value.component];

    if (!component) {
      throw new Error(
        `Form Control component is not registered: ${control.value.component}`
      );
    }

    return component;
  });
  const editable = computed(() => control.value.mode === 'editable');
  const controlErrors = computed(() =>
    props.errors.flatMap((error) =>
      pathsMatch(error.path, control.value.path) ? error.messages : []
    )
  );
  const value = computed(() => valueAt(control.value.path));

  function valueAt(path: string[]): unknown {
    return path.reduce<unknown>(
      (value, segment) => (value as Record<string, unknown>)[segment],
      props.values
    );
  }

  function setValue(value: unknown): void {
    let target = props.values;

    control.value.path.forEach((segment, index) => {
      if (index === control.value.path.length - 1) {
        target[segment] = value;

        return;
      }

      target = target[segment] as Record<string, unknown>;
    });
  }

  function pathsMatch(path: string[], controlPath: string[]): boolean {
    return (
      path.length === controlPath.length &&
      path.every((segment, index) => segment === controlPath[index])
    );
  }
</script>

<template>
  <craft-field
    :label="node.props.label ?? undefined"
    :help-text="node.props.instructions ?? undefined"
    :required="Boolean(node.props.required)"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    :has-errors="controlErrors.length > 0"
  >
    <component
      :is="component"
      slot="input"
      :control="control"
      :value="value"
      :label="node.props.label ?? undefined"
      :editable="editable"
      :invalid="controlErrors.length > 0"
      :required="Boolean(node.props.required)"
      :aria-invalid="controlErrors.length ? 'true' : undefined"
      @update:value="setValue"
    />
    <ul v-if="controlErrors.length" slot="feedback" class="error-list">
      <li v-for="error in controlErrors" :key="error">{{ error }}</li>
    </ul>
  </craft-field>
</template>

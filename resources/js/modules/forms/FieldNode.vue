<script setup lang="ts">
  import '@craftcms/ui/components/field/field';
  import {computed, getCurrentInstance, inject, onErrorCaptured} from 'vue';
  import {
    FormFailure,
    pathsMatch,
    setValue as setPathValue,
    valueAt,
  } from './runtime';
  import type {
    FormChange,
    FormChangeKind,
    FormNodePayload,
    FormPayload,
  } from './types';

  type FieldNodeProps = {
    label?: string | null;
    instructions?: string | null;
    required?: boolean;
    instructionsPosition?: 'before' | 'after';
    tip?: string;
    warning?: string;
    layoutUid?: string;
    width?: number;
  };

  const props = defineProps<{
    node: FormNodePayload<FieldNodeProps>;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: FormChange): void;
  }>();
  const invalidate = inject(FormFailure)!;
  const components = getCurrentInstance()!.appContext.components;
  const control = computed(() => props.node.control!);
  const component = computed(() => {
    const component = components[control.value.component];

    if (!component) {
      throw new Error(
        `Failed to render Form Control [${control.value.type}] with component [${control.value.component}] at [${control.value.path.join('.')}]: component is not registered.`
      );
    }

    return component;
  });

  onErrorCaptured((error) => {
    invalidate(
      `Failed to render Form Control [${control.value.type}] with component [${control.value.component}] at [${control.value.path.join('.')}]: ${errorMessage(error)}`
    );

    return false;
  });

  function errorMessage(error: unknown): string {
    return error instanceof Error ? error.message : String(error);
  }
  const editable = computed(() => control.value.mode === 'editable');
  const controlErrors = computed(() =>
    props.errors.flatMap((error) =>
      pathsMatch(error.path, control.value.path) ? error.messages : []
    )
  );
  const value = computed(() => valueAt(props.values, control.value.path));

  function setValue(value: unknown, kind: FormChangeKind = 'discrete'): void {
    setPathValue(props.values, control.value.path, value);

    emit('change', {
      kind,
      path: control.value.path,
      scope: props.scope,
      refreshable: props.refreshable,
    });
  }
</script>

<template>
  <craft-field
    :label="node.props.label ?? undefined"
    :help-text="node.props.instructions ?? undefined"
    :instructions-position="node.props.instructionsPosition"
    :required="Boolean(node.props.required)"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    :has-errors="controlErrors.length > 0"
    :class="node.props.width ? `width-${node.props.width}` : undefined"
    :data-layout-element="node.props.layoutUid"
  >
    <span v-if="node.props.tip" slot="tip">{{ node.props.tip }}</span>
    <span v-if="node.props.warning" slot="warning">
      {{ node.props.warning }}
    </span>
    <component
      :is="component"
      slot="input"
      :control="control"
      :value="value"
      :label="node.props.label ?? undefined"
      :editable="editable"
      :invalid="controlErrors.length > 0"
      :required="Boolean(node.props.required)"
      :values="values"
      :errors="errors"
      :touched-paths="touchedPaths"
      :form-scope="scope"
      :form-refreshable="refreshable"
      :aria-invalid="controlErrors.length ? 'true' : undefined"
      :data-form-control-path="JSON.stringify(control.path)"
      :data-form-touched="touchedPaths.has(JSON.stringify(control.path))"
      @update:value="setValue"
      @change="emit('change', $event)"
    />
    <ul v-if="controlErrors.length" slot="feedback" class="error-list">
      <li v-for="error in controlErrors" :key="error">{{ error }}</li>
    </ul>
  </craft-field>
</template>

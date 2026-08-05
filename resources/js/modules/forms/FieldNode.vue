<script setup lang="ts">
  import '@craftcms/ui/components/field/field';
  import {computed, getCurrentInstance, inject, onErrorCaptured} from 'vue';
  import {FormFailure} from './runtime';
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
  const value = computed(() => valueAt(control.value.path));

  function valueAt(path: string[]): unknown {
    return path.reduce<unknown>(
      (value, segment) => (value as Record<string, unknown>)[segment],
      props.values
    );
  }

  function setValue(value: unknown, kind: FormChangeKind = 'discrete'): void {
    let target = props.values;

    control.value.path.forEach((segment, index) => {
      if (index === control.value.path.length - 1) {
        target[segment] = value;

        return;
      }

      target = target[segment] as Record<string, unknown>;
    });

    emit('change', {
      kind,
      path: control.value.path,
      scope: props.scope,
      refreshable: props.refreshable,
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

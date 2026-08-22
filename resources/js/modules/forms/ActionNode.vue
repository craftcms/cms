<script setup lang="ts">
  import {computed, getCurrentInstance, inject, onErrorCaptured} from 'vue';
  import {
    FormFailure,
    formChangeFromEvent,
    pathsMatch,
    setValue as setPathValue,
    valueAt,
  } from './runtime';
  import type {
    FormChange,
    FormChangeKind,
    FormNodePayload,
    FormPayload,
    FormValue,
  } from './types';

  const props = defineProps<{
    node: FormNodePayload;
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
      `Failed to render Form Control [${control.value.type}] with component [${control.value.component}] at [${control.value.path.join('.')}]: ${error instanceof Error ? error.message : String(error)}`
    );

    return false;
  });

  const editable = computed(() => control.value.mode === 'editable');
  const controlErrors = computed(() =>
    props.errors.flatMap((error) =>
      pathsMatch(error.path, control.value.path) ? error.messages : []
    )
  );
  const value = computed(() => valueAt(props.values, control.value.path));

  function setValue(value: FormValue, kind: FormChangeKind = 'discrete'): void {
    setPathValue(props.values, control.value.path, value);

    emit('change', {
      kind,
      path: control.value.path,
      scope: props.scope,
      refreshable: props.refreshable,
    });
  }

  function onChange(change: FormChange | Event): void {
    const formChange = formChangeFromEvent(change);

    if (formChange) {
      emit('change', formChange);
    }
  }
</script>

<template>
  <component
    :is="component"
    :control="control"
    :value="value"
    :editable="editable"
    :invalid="controlErrors.length > 0"
    :required="false"
    :values="values"
    :errors="errors"
    :touched-paths="touchedPaths"
    :form-scope="scope"
    :form-refreshable="refreshable"
    :aria-invalid="controlErrors.length ? 'true' : undefined"
    :data-form-control-path="JSON.stringify(control.path)"
    :data-form-touched="touchedPaths.has(JSON.stringify(control.path))"
    @update:value="setValue"
    @change="onChange"
  />
</template>

<script setup lang="ts">
  import {computed, getCurrentInstance, inject, onErrorCaptured} from 'vue';
  import {
    UiFailure,
    uiChangeFromEvent,
    pathsMatch,
    setValue as setPathValue,
    controlValueAt,
  } from './runtime';
  import type {
    UiChange,
    UiChangeKind,
    UiNodePayload,
    UiPayload,
    UiValue,
  } from './types';

  const props = defineProps<{
    node: UiNodePayload;
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: UiChange): void;
  }>();
  const invalidate = inject(UiFailure)!;
  const components = getCurrentInstance()!.appContext.components;
  const control = computed(() => props.node.control!);
  const component = computed(() => {
    const component = components[control.value.component];

    if (!component) {
      throw new Error(
        `Failed to render UI Control [${control.value.type}] with component [${control.value.component}] at [${control.value.path.join('.')}]: component is not registered.`
      );
    }

    return component;
  });

  onErrorCaptured((error) => {
    invalidate(
      `Failed to render UI Control [${control.value.type}] with component [${control.value.component}] at [${control.value.path.join('.')}]: ${error instanceof Error ? error.message : String(error)}`
    );

    return false;
  });

  const editable = computed(() => control.value.mode === 'editable');
  const controlErrors = computed(() =>
    props.errors.flatMap((error) =>
      pathsMatch(error.path, control.value.path) ? error.messages : []
    )
  );
  const value = computed(() => controlValueAt(props.values, control.value));
  const refreshable = computed(
    () => props.refreshable && Boolean(control.value.reactive)
  );

  function setValue(
    value: UiValue,
    kind: UiChangeKind = 'discrete',
    change?: UiChange
  ): void {
    setPathValue(props.values, control.value.path, value);

    emit(
      'change',
      change ?? {
        kind,
        path: control.value.path,
        scope: props.scope,
        refreshable: refreshable.value,
      }
    );
  }

  function onChange(change: UiChange | Event): void {
    const uiChange = uiChangeFromEvent(change);

    if (uiChange) {
      emit('change', uiChange);
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
    :ui-scope="scope"
    :ui-refreshable="refreshable"
    :aria-invalid="controlErrors.length ? 'true' : undefined"
    :data-ui-control-path="JSON.stringify(control.path)"
    :data-ui-touched="touchedPaths.has(JSON.stringify(control.path))"
    @update:value="setValue"
    @change="onChange"
  />
</template>

<script setup lang="ts">
  import '@craftcms/ui/components/field/field';
  import {computed, getCurrentInstance, inject, provide} from 'vue';
  import {
    FieldLabelSrOnly,
    UiFailure,
    uiChangeFromEvent,
    pathsMatch,
  } from '../runtime';
  import type {
    UiChange,
    UiChangeKind,
    UiNodePayload,
    UiPayload,
    UiValue,
  } from '../types';

  const props = defineProps<{
    node: UiNodePayload;
    value: UiValue;
    label: string;
    editable: boolean;
    invalid?: boolean;
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    uiScope: string[];
    uiRefreshable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: UiValue, kind?: UiChangeKind): void;
    (event: 'change', change: UiChange): void;
  }>();
  const components = getCurrentInstance()!.appContext.components;
  const failure = inject(UiFailure, undefined);
  provide(
    FieldLabelSrOnly,
    computed(() => true)
  );
  const control = computed(() => {
    const control = props.node.control!;
    return !props.editable && control.mode === 'editable'
      ? {...control, mode: 'disabled' as const}
      : control;
  });
  const component = computed(() => {
    const component = components[control.value.component];
    if (!component)
      failure?.(`UI Control [${control.value.component}] is not registered.`);
    return component;
  });
  const messages = computed(() =>
    props.errors.flatMap((error) =>
      pathsMatch(error.path, control.value.path) ? error.messages : []
    )
  );

  function onChange(change: UiChange | Event): void {
    const uiChange = uiChangeFromEvent(change);
    if (uiChange) emit('change', uiChange);
  }
</script>

<template>
  <craft-field
    :label="label"
    .labelSrOnly="true"
    .required="Boolean(node.props.required)"
    .hasErrors="invalid || messages.length > 0"
    :data-ui-control-path="JSON.stringify(control.path)"
    :data-ui-touched="touchedPaths.has(JSON.stringify(control.path))"
  >
    <component
      :is="component"
      slot="input"
      v-if="component"
      :control="control"
      :value="value"
      :label="label"
      :editable="editable && control.mode === 'editable'"
      :invalid="invalid || messages.length > 0"
      :required="Boolean(node.props.required)"
      :values="values"
      :errors="errors"
      :touched-paths="touchedPaths"
      :ui-scope="uiScope"
      :ui-refreshable="uiRefreshable"
      :aria-invalid="invalid || messages.length ? 'true' : undefined"
      @update:value="
        (value: UiValue, kind?: UiChangeKind) =>
          emit('update:value', value, kind)
      "
      @change="onChange"
    />
    <ul v-if="messages.length" slot="feedback" class="error-list">
      <li v-for="message in messages" :key="message">{{ message }}</li>
    </ul>
  </craft-field>
</template>

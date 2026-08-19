<script setup lang="ts">
  import '@craftcms/ui/components/field/field';
  // From the leaf module, not the `@craftcms/ui` barrel: the barrel
  // side-effect-registers every `craft-*` element, which is more than a field
  // needs to say one word and drags that whole graph into anything rendering a
  // form.
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, getCurrentInstance, inject, onErrorCaptured} from 'vue';
  import FormNodeList from './FormNodeList.vue';
  import {
    FormControlOverrides,
    FormFailure,
    FormModifiedGroups,
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
  } from './types';

  type FieldNodeProps = {
    label?: string | null;
    instructions?: string | null;
    required?: boolean;
    instructionsPosition?: 'before' | 'after';
    tip?: string;
    tipHtml?: string;
    warning?: string;
    warningHtml?: string;
    layoutUid?: string;
    width?: number;
    hasActions?: boolean;
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
  const overrides = inject(FormControlOverrides, {});
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
  const override = computed(() => overrides[control.value.path.join('.')]);
  const actions = computed(() => props.node.children ?? []);

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

  /**
   * Whether the element carries unapplied changes to this field.
   *
   * Matched on the delta group rather than the control path so every control
   * making up one field badges together — a field split across several inputs
   * is modified as a whole, the same unit change detection already works in.
   */
  const modifiedGroups = inject(FormModifiedGroups, undefined);
  const modified = computed(
    () => modifiedGroups?.value.has(control.value.deltaGroup.join('.')) ?? false
  );

  function setValue(value: unknown, kind: FormChangeKind = 'discrete'): void {
    setPathValue(props.values, control.value.path, value);

    emit('change', {
      kind,
      path: control.value.path,
      scope: props.scope,
      refreshable: props.refreshable,
    });
  }

  function renderOverride() {
    return override.value?.({
      control: control.value,
      value: value.value,
      values: props.values,
      label: props.node.props.label ?? undefined,
      editable: editable.value,
      invalid: controlErrors.value.length > 0,
      required: Boolean(props.node.props.required),
      setValue,
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
  <craft-field
    :label="node.props.label ?? undefined"
    :help-text="node.props.instructions ?? undefined"
    :instructions-position="node.props.instructionsPosition"
    :required="Boolean(node.props.required)"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    :has-errors="controlErrors.length > 0"
    :status="modified ? 'modified' : undefined"
    :status-label="modified ? t('This field has been modified.') : undefined"
    :class="node.props.width ? `width-${node.props.width}` : undefined"
    :data-layout-element="node.props.layoutUid"
  >
    <div v-if="actions.length" slot="actions">
      <FormNodeList
        :nodes="actions"
        :values="values"
        :errors="errors"
        :touched-paths="touchedPaths"
        :scope="scope"
        :refreshable="refreshable"
        @change="onChange"
      />
    </div>
    <span v-if="node.props.tipHtml" slot="tip" v-html="node.props.tipHtml" />
    <span
      v-if="node.props.warningHtml"
      slot="warning"
      v-html="node.props.warningHtml"
    />
    <div
      v-if="override"
      slot="input"
      data-form-control-override
      :aria-invalid="controlErrors.length ? 'true' : undefined"
      :data-form-control-path="JSON.stringify(control.path)"
      :data-form-touched="touchedPaths.has(JSON.stringify(control.path))"
    >
      <component :is="renderOverride" />
    </div>
    <component
      v-else
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
      @change="onChange"
    />
    <ul v-if="controlErrors.length" slot="feedback" class="error-list">
      <li v-for="error in controlErrors" :key="error">{{ error }}</li>
    </ul>
  </craft-field>
</template>

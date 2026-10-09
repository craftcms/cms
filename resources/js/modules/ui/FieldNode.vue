<script setup lang="ts">
  import '@craftcms/ui/components/field/field';
  // Leaf module, not the barrel — the barrel registers every `craft-*` element.
  import {actionClient, isHttpError} from '@craftcms/ui';
  import {t} from '@craftcms/ui/utilities/translate';
  import {useEventListener} from '@vueuse/core';
  import {
    computed,
    getCurrentInstance,
    inject,
    onErrorCaptured,
    provide,
    shallowRef,
    useTemplateRef,
    watch,
  } from 'vue';
  import CopyElementValuesController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/CopyElementValuesController';
  import CrossSiteCopyModal from './CrossSiteCopyModal.vue';
  import UiNodeList from './UiNodeList.vue';
  import {
    FieldActionItems,
    FieldLabelSrOnly,
    UiControlOverrides,
    UiFailure,
    UiChangedPaths,
    UiModifiedGroups,
    uiChangeFromEvent,
    fieldId,
    pathsMatch,
    setValue as setPathValue,
    controlValueAt,
  } from './runtime';
  import type {
    UiChange,
    UiChangeKind,
    UiControlPayload,
    UiNodePayload,
    UiPayload,
    UiValue,
  } from './types';

  type CrossSiteCopyDetail = {
    trigger?: unknown;
    elementType: string;
    elementId: number;
    draftId: number | null;
    siteId: number;
    layoutElementUid: string;
    label?: string | null;
    siteIds: number[];
  };

  type CrossSiteCopyResponse = {
    message: string;
    field: UiNodePayload<FieldNodeProps>;
    values: UiPayload['values'];
  };

  type FieldNodeProps = {
    id?: string;
    label?: string | null;
    labelHtml?: string;
    /** Visually hides the label, keeping it available to screen readers. */
    labelSrOnly?: boolean;
    fieldset?: boolean;
    showStatus?: boolean;
    headingSuffix?: string;
    headingPrefix?: string;
    translatable?: boolean;
    translationDescription?: string;
    orientation?: 'ltr' | 'rtl';
    inputWidth?: 'full' | 'auto';
    instructions?: string | null;
    instructionsHtml?: string;
    required?: boolean;
    instructionsPosition?: 'before' | 'after';
    tip?: string;
    tipHtml?: string;
    warning?: string;
    warningHtml?: string;
    layoutUid?: string;
    width?: number;
    status?: string;
    statusLabel?: string;
    hasActions?: boolean;
    /** Hidden from view; the control still resolves and still holds its value. */
    hidden?: boolean;
  };

  const props = defineProps<{
    node: UiNodePayload<FieldNodeProps>;
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: UiChange): void;
  }>();
  const resolvedNode = shallowRef(props.node);
  watch(
    () => props.node,
    (node) => (resolvedNode.value = node)
  );
  const invalidate = inject(UiFailure)!;
  provide(
    FieldLabelSrOnly,
    computed(() => Boolean(resolvedNode.value.props.labelSrOnly))
  );
  const overrides = inject(UiControlOverrides, {});
  const components = getCurrentInstance()!.appContext.components;
  const control = computed(() => resolvedNode.value.control!);
  const component = computed(() => {
    const component = components[control.value.component];

    if (!component) {
      throw new Error(
        `Failed to render UI Control [${control.value.type}] with component [${control.value.component}] at [${control.value.path.join('.')}]: component is not registered.`
      );
    }

    return component;
  });
  const override = computed(() => overrides[control.value.path.join('.')]);
  const actions = computed(() => resolvedNode.value.children ?? []);

  provide(FieldActionItems, shallowRef());

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

  const modifiedGroups = inject(UiModifiedGroups, undefined);
  const changedPaths = inject(UiChangedPaths, undefined);
  const showStatus = computed(
    () => resolvedNode.value.props.showStatus !== false
  );
  const modified = computed(
    () => showStatus.value && (holdsChange() || modifiedByServer())
  );

  /**
   * Whether something changed inside this field, for a field holding nested
   * uis.
   *
   * Craft 5's element editor marks a changed input's field and every enclosing
   * field — `parentsUntil(this.$container, '.field')`. That's what puts the
   * badge on a Matrix field whose block was edited even when the block was
   * created in this draft, which the server has no change record for yet. Only
   * the enclosing fields, though: a field inside a block doesn't badge on its
   * own, the block's field says it.
   */
  function holdsChange(): boolean {
    if (!control.value.nestsUis || !changedPaths) {
      return false;
    }

    const own = control.value.path.join('.');

    for (const path of changedPaths.value) {
      if (path === own || path.startsWith(`${own}.`)) {
        return true;
      }
    }

    return false;
  }

  /**
   * The server's modified groups, for controls of the element this UI is
   * for. A control inside a nested UI inherits its owner's delta group, so it
   * would otherwise badge whenever the field holding it changed.
   */
  function modifiedByServer(): boolean {
    if (props.scope.length > control.value.deltaGroup.length) {
      return false;
    }

    return (
      modifiedGroups?.value.has(control.value.deltaGroup.join('.')) ?? false
    );
  }

  function setValue(
    value: UiValue,
    kind: UiChangeKind = 'discrete',
    /**
     * Either an updated control definition (nested Blocks controls) or a
     * fully formed change (editable table rows bound to their own paths).
     */
    detail?: UiControlPayload<object> | UiChange
  ): void {
    setPathValue(props.values, control.value.path, value);

    if (detail && !('component' in detail)) {
      emit('change', detail);

      return;
    }

    emit('change', {
      kind,
      path: control.value.path,
      scope: props.scope,
      refreshable: refreshable.value,
      control: detail,
    });
  }

  function renderOverride() {
    return override.value?.({
      control: control.value,
      value: value.value,
      values: props.values,
      errors: props.errors,
      label: resolvedNode.value.props.label ?? undefined,
      editable: editable.value,
      invalid: controlErrors.value.length > 0,
      required: Boolean(resolvedNode.value.props.required),
      setValue,
    });
  }

  function onChange(change: UiChange | Event): void {
    const uiChange = uiChangeFromEvent(change);

    if (uiChange) {
      emit('change', uiChange);
    }
  }

  const field = useTemplateRef<HTMLElement>('field');
  const copyDetail = shallowRef<CrossSiteCopyDetail>();
  const copySites = shallowRef<Array<{id: number; name: string}>>([]);
  const copying = shallowRef(false);

  useEventListener(window, 'craft:copy-value-from-site', (event: Event) => {
    const detail = (event as CustomEvent<CrossSiteCopyDetail>).detail;

    if (
      !detail ||
      !(detail.trigger instanceof HTMLElement) ||
      detail.trigger.closest('craft-field') !== field.value
    ) {
      return;
    }

    copyDetail.value = detail;
    copySites.value = Craft.sites.filter((site) =>
      detail.siteIds.includes(site.id)
    );
  });

  async function copyValue(fromSiteId: number): Promise<void> {
    const detail = copyDetail.value;

    if (!detail) {
      return;
    }

    copying.value = true;

    try {
      const {data} = await actionClient.post<CrossSiteCopyResponse>(
        CopyElementValuesController.url(),
        {
          elementType: detail.elementType,
          elementId: detail.elementId,
          draftId: detail.draftId,
          siteId: detail.siteId,
          layoutElementUid: detail.layoutElementUid,
          fromSiteId,
          namespace: props.scope.join('.') || null,
        }
      );

      resolvedNode.value = data.field;
      setValue(controlValueAt(data.values, data.field.control!));
      copyDetail.value = undefined;
      Craft.cp?.displayNotice?.(data.message);
    } catch (error) {
      const message = isHttpError<{message?: string}>(error)
        ? error.response?.data?.message
        : undefined;
      Craft.cp?.displayError?.(
        message ?? t('Couldn’t copy the field value from the selected site.')
      );
    } finally {
      copying.value = false;
    }
  }
</script>

<template>
  <craft-field
    ref="field"
    :id="resolvedNode.props.id ?? fieldId(control.path)"
    .translatable="Boolean(resolvedNode.props.translatable)"
    .fieldset="Boolean(resolvedNode.props.fieldset)"
    :translation-description="resolvedNode.props.translationDescription"
    :orientation="resolvedNode.props.orientation"
    :width="resolvedNode.props.inputWidth"
    :label-sr-only="resolvedNode.props.labelSrOnly || undefined"
    :help-text="
      resolvedNode.props.instructionsHtml
        ? undefined
        : (resolvedNode.props.instructions ?? undefined)
    "
    :instructions-position="resolvedNode.props.instructionsPosition"
    :required="Boolean(resolvedNode.props.required)"
    :readonly="control.mode === 'readOnly'"
    :disabled="control.mode === 'disabled'"
    :has-errors="controlErrors.length > 0"
    :status="
      showStatus
        ? modified
          ? 'modified'
          : resolvedNode.props.status
        : undefined
    "
    :status-label="
      !showStatus
        ? undefined
        : modified
          ? t('This field has been modified.')
          : resolvedNode.props.statusLabel
    "
    :class="{
      [`width-${resolvedNode.props.width}`]: Boolean(resolvedNode.props.width),
      hidden: Boolean(resolvedNode.props.hidden),
    }"
    :hidden="resolvedNode.props.hidden || undefined"
    :data-layout-element="resolvedNode.props.layoutUid"
  >
    <span
      v-if="resolvedNode.props.labelHtml !== undefined"
      slot="label"
      v-html="resolvedNode.props.labelHtml"
    />
    <span v-else-if="resolvedNode.props.label != null" slot="label">{{
      resolvedNode.props.label
    }}</span>
    <span
      v-if="resolvedNode.props.headingPrefix"
      slot="heading-prefix"
      v-html="resolvedNode.props.headingPrefix"
    />
    <span
      v-if="resolvedNode.props.headingSuffix"
      slot="heading-suffix"
      v-html="resolvedNode.props.headingSuffix"
    />
    <div v-if="actions.length" slot="actions">
      <UiNodeList
        :nodes="actions"
        :values="values"
        :errors="errors"
        :touched-paths="touchedPaths"
        :scope="scope"
        :refreshable="props.refreshable"
        @change="onChange"
      />
    </div>
    <span
      v-if="resolvedNode.props.instructionsHtml"
      slot="help-text"
      v-html="resolvedNode.props.instructionsHtml"
    />
    <span
      v-if="resolvedNode.props.tipHtml"
      slot="tip"
      v-html="resolvedNode.props.tipHtml"
    />
    <span
      v-if="resolvedNode.props.warningHtml"
      slot="warning"
      v-html="resolvedNode.props.warningHtml"
    />
    <div
      v-if="override"
      slot="input"
      data-ui-control-override
      :aria-invalid="controlErrors.length ? 'true' : undefined"
      :data-ui-control-path="JSON.stringify(control.path)"
      :data-ui-touched="touchedPaths.has(JSON.stringify(control.path))"
    >
      <component :is="renderOverride" />
    </div>
    <component
      v-else
      :is="component"
      slot="input"
      :control="control"
      :value="value"
      :label="resolvedNode.props.label ?? undefined"
      :editable="editable"
      :invalid="controlErrors.length > 0"
      :required="Boolean(resolvedNode.props.required)"
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
    <ul v-if="controlErrors.length" slot="feedback" class="error-list">
      <li v-for="error in controlErrors" :key="error">{{ error }}</li>
    </ul>
  </craft-field>
  <CrossSiteCopyModal
    :active="Boolean(copyDetail)"
    :label="copyDetail?.label"
    :sites="copySites"
    :loading="copying"
    @close="copyDetail = undefined"
    @submit="copyValue"
  />
</template>

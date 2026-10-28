<script setup lang="ts">
  import {
    computed,
    nextTick,
    onErrorCaptured,
    onBeforeUnmount,
    provide,
    ref,
    shallowRef,
    useSlots,
    watch,
  } from 'vue';
  import {useEventListener} from '@vueuse/core';
  import UiNodeList from './UiNodeList.vue';
  import {canonical, pathsMatch, valueAt} from './runtime';
  import {useUiValueGroup} from './uiValueGroup';
  import type {SidebarTabOutlets} from './sidebarTabs';
  import type {
    UiChange,
    UiChangeKind,
    UiControlPayload,
    UiPayload,
    UiValue,
    UiValues,
    NestedUiPayload,
  } from './types';
  import {UiRendererKey} from './renderer/context';
  import {captureControlFocus} from './renderer/controlFocus';
  import {
    disabledNodes,
    withControl,
    withNestedUi,
    withUis,
  } from './renderer/uiTree';
  import {useClearedErrors} from './renderer/useClearedErrors';
  import {useUiRefresh} from './renderer/useUiRefresh';
  import {useUiValues} from './renderer/useUiValues';

  const props = defineProps<{
    payload: UiPayload;
    disabled?: boolean;
    refresh?: (
      values: UiPayload['values'],
      scope?: string[]
    ) => Promise<UiPayload>;
    errors?: UiPayload['errors'];
    /** Delta groups the server reports as modified, as dotted paths. */
    modified?: string[];
    /** Where sidebar tabs render. Without it, they're shown as ordinary tabs. */
    sidebarTabOutlets?: SidebarTabOutlets;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:mutation',
      mutation: UiPayload['values'],
      kind: UiChangeKind
    ): void;
    (event: 'change', change: UiChange, values: UiPayload['values']): void;
    (event: 'update:payload', payload: UiPayload): void;
  }>();
  const slots = useSlots();
  const payload = shallowRef(props.payload);
  const nodes = computed(() =>
    props.disabled ? disabledNodes(payload.value.nodes) : payload.value.nodes
  );

  const root = ref<HTMLElement>();
  const renderError = ref<string>();
  const hostForm = computed(() => root.value?.closest('form'));
  const nativeSubmitting = ref(false);
  const submitting = ref(false);
  let submittedValues: UiValues | undefined;

  const valueState = useUiValues(props.payload);
  const {values, touchedPaths} = valueState;
  const unregisterValueSource = useUiValueGroup()?.register(values);
  const refresher = useUiRefresh({
    refresh: () => props.refresh,
    disabled: () => Boolean(props.disabled),
    snapshot: (scope) => valueState.snapshot(scope),
    fingerprint: valueState.groupCanonical,
    apply: reconcile,
  });
  refresher.seed(
    props.payload.scope,
    canonical(valueAt(values, props.payload.scope))
  );
  const errorState = useClearedErrors(
    () => props.errors ?? payload.value.errors
  );
  const {effectiveErrors} = errorState;

  provide(UiRendererKey, {
    fail: invalidate,
    setValue: valueState.write,
    synchronizeStructure,
    registerBehavior: valueState.registerBehavior,
    clearChildErrors: errorState.clearChildren,
    childErrorsCleared: errorState.childrenCleared,
    overrides: slots,
    pending: computed(() =>
      Boolean(
        props.disabled ||
        nativeSubmitting.value ||
        submitting.value ||
        refresher.refreshingFields.size
      )
    ),
    modifiedGroups: computed(() => new Set(props.modified ?? [])),
    changedPaths: valueState.changedPaths,
    refreshingFields: computed(() => refresher.refreshingFields),
  });

  useEventListener(hostForm, 'submit', (event) => {
    nativeSubmitting.value = true;
    queueMicrotask(() => {
      if (event.defaultPrevented) nativeSubmitting.value = false;
    });
    if (renderError.value) {
      event.preventDefault();
    }
  });

  onErrorCaptured((error) => {
    invalidate(error instanceof Error ? error.message : String(error));

    return false;
  });

  watch(
    () => props.payload,
    (refreshed) => reconcile(refreshed)
  );

  watch(
    () => props.errors,
    () => errorState.restore(),
    {flush: 'sync'}
  );

  watch(
    () => props.disabled,
    () => emitMutation()
  );
  onBeforeUnmount(() => unregisterValueSource?.());

  function onControlChange(change: UiChange): void {
    recordChange(change);
    emit('change', change, currentValues());
  }

  function recordChange(change: UiChange): void {
    if (change.control && pathsMatch(change.control.path, change.path)) {
      payload.value = withControl(payload.value, change.path, change.control);
      valueState.rememberControls(payload.value.nodes);
      emit('update:payload', payload.value);
    }

    valueState.recordEdit(change.path);
    emitMutation(change.kind);

    if (change.refreshable) {
      refresher.schedule(
        change.scope ?? payload.value.scope,
        change.path,
        change.kind
      );
    }
  }

  function reconcile(
    refreshed: UiPayload,
    scope: string[] = payload.value.scope
  ): void {
    if (!pathsMatch(refreshed.scope, scope)) {
      throw new Error(
        `Refreshed UI scope [${refreshed.scope.join('.')}] does not match [${scope.join('.')}].`
      );
    }

    if (!props.errors) {
      errorState.restore(scope);
    }
    renderError.value = undefined;
    const restoreFocus = captureControlFocus();

    valueState.adopt(refreshed);
    payload.value = pathsMatch(scope, payload.value.scope)
      ? refreshed
      : withNestedUi(payload.value, scope, refreshed);
    emit('update:payload', payload.value);
    emitMutation();

    if (restoreFocus) {
      nextTick(restoreFocus);
    }
  }

  /**
   * Throws away the unsaved values and seeds the UI again from `source` —
   * the payload the props already carry, unless one is passed.
   *
   * The mirror image of {@link reconcile}, which merges a refreshed payload
   * *under* what the client is holding because the client owns unsaved values.
   * That contract describes a refresh; it stops applying the moment the user
   * asks for those values to be thrown away — discarding a provisional draft,
   * say — and only the host knows that has happened, so it has to say so.
   *
   * Leaves the UI exactly as mounting it would have: nothing touched,
   * nothing dirty, no refresh in flight.
   */
  function resetValues(source: UiPayload = props.payload): void {
    renderError.value = undefined;
    refresher.cancel();
    refresher.seed(
      source.scope,
      canonical(valueAt(source.values, source.scope))
    );
    nativeSubmitting.value = false;
    submitting.value = false;
    submittedValues = undefined;
    errorState.restore();
    valueState.reset(source);
    payload.value = source;
    emit('update:payload', payload.value);
    emitMutation();
  }

  function mutation(includeGroups: string[][] = []): UiPayload['values'] {
    return valueState.mutation(nodes.value, includeGroups);
  }

  function currentValues(): UiPayload['values'] {
    return valueState.currentValues(payload.value.nodes);
  }

  function emitMutation(kind: UiChangeKind = 'discrete'): void {
    emit('update:mutation', mutation(), kind);
  }

  function invalidate(message: string): void {
    renderError.value ??= message;
  }

  function advanceBaseline(): void {
    valueState.rebase(submittedValues ?? valueState.snapshot());
    submittedValues = undefined;
    emitMutation();
  }

  /**
   * Takes `source` as the values the server now holds, leaving the values the
   * client is holding as they are.
   *
   * For a save that didn't go through a submission — an autosave — whose
   * response is the only record of what it wrote. A value edited back to what
   * it was before that save then still differs from the baseline and goes out
   * with the next one, and edits made while it was in flight stay unsaved.
   */
  function rebase(source: UiPayload['values']): void {
    valueState.rebase(source);
    emitMutation();
  }

  function setValue(
    path: string[],
    value: UiValue,
    kind: UiChange['kind'] = 'discrete'
  ): void {
    valueState.write(path, value);
    recordChange({kind, path});
  }

  /**
   * Sets several values at once. When any of them is refreshable, the layout
   * refreshes once, with all of the values, for the last refreshable one.
   */
  function setValues(
    changes: {path: string[]; value: UiValue; refreshable?: boolean}[]
  ): void {
    const refreshing = [...changes]
      .reverse()
      .find((change) => change.refreshable);

    for (const change of changes) {
      valueState.write(change.path, change.value);
    }

    for (const change of changes) {
      if (change !== refreshing) {
        recordChange({kind: 'discrete', path: change.path});
      }
    }

    if (refreshing) {
      recordChange({
        kind: 'discrete',
        path: refreshing.path,
        refreshable: true,
      });
    }
  }

  function setSubmitting(value: boolean): void {
    if (value && !submitting.value) submittedValues = valueState.snapshot();
    submitting.value = value;
  }

  function synchronizeStructure(
    control: Pick<UiControlPayload, 'path'>,
    uis: NestedUiPayload[]
  ): void {
    if (!valueState.restructured(control.path, uis)) {
      return;
    }

    payload.value = withUis(payload.value, control.path, uis);
    valueState.rememberControls(payload.value.nodes);
  }

  defineExpose({
    advanceBaseline,
    rebase,
    setSubmitting,
    currentValues,
    mutation,
    resetValues,
    setValue,
    setValues,
    canSubmit: () => !renderError.value,
  });
</script>

<template>
  <!-- UI Renderer -->
  <span ref="root" hidden></span>
  <p v-if="renderError" role="alert">{{ renderError }}</p>
  <template v-else>
    <ul v-if="payload.globalErrors.length" class="error-list" role="alert">
      <li v-for="error in payload.globalErrors" :key="error">{{ error }}</li>
    </ul>
    <UiNodeList
      :nodes="nodes"
      :values="values"
      :errors="effectiveErrors"
      :touched-paths="touchedPaths"
      :scope="payload.scope"
      :refreshable="payload.refreshable"
      :sidebar-tab-outlets="sidebarTabOutlets"
      @change="onControlChange"
    />
  </template>
</template>

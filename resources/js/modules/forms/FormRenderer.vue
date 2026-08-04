<script setup lang="ts">
  import {
    computed,
    nextTick,
    onErrorCaptured,
    onBeforeUnmount,
    provide,
    reactive,
    ref,
    shallowRef,
    toRaw,
    watch,
  } from 'vue';
  import {useEventListener} from '@vueuse/core';
  import FormNode from './FormNode.vue';
  import {FormFailure} from './runtime';
  import type {
    FormChange,
    FormControlPayload,
    FormNodePayload,
    FormPayload,
  } from './types';

  const props = defineProps<{
    payload: FormPayload;
    refresh?: (values: FormPayload['values']) => Promise<FormPayload>;
    errors?: FormPayload['errors'];
  }>();
  const emit = defineEmits<{
    (event: 'update:mutation', mutation: FormPayload['values']): void;
  }>();
  const payload = shallowRef(props.payload);
  const root = ref<HTMLElement>();
  const renderError = ref<string>();
  const hostForm = computed(() => root.value?.closest('form'));
  const values = reactive(structuredClone(props.payload.values));
  let baseline = structuredClone(props.payload.values);
  let refreshTimer: ReturnType<typeof setTimeout> | undefined;
  let latestRequest = 0;
  let lastRefreshValues = canonical(values);
  const knownControlPaths = new Map<string, string[]>();
  const touchedPaths = new Set<string>();
  rememberControlPaths(props.payload.nodes);
  provide(FormFailure, invalidate);

  useEventListener(hostForm, 'submit', (event) => {
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
  onBeforeUnmount(() => clearTimeout(refreshTimer));

  function onChange(change: FormChange): void {
    latestRequest++;
    touchedPaths.add(JSON.stringify(change.path));
    emitMutation();

    if (!props.refresh || !payload.value.refreshable) {
      return;
    }

    clearTimeout(refreshTimer);
    refreshTimer = setTimeout(
      requestRefresh,
      change.kind === 'typing' ? 1000 : 100
    );
  }

  async function requestRefresh(): Promise<void> {
    const snapshot = structuredClone(toRaw(values));
    const serialized = canonical(snapshot);

    if (serialized === lastRefreshValues) {
      return;
    }

    lastRefreshValues = serialized;
    const request = ++latestRequest;

    try {
      const refreshed = await props.refresh!(snapshot);

      if (request === latestRequest) {
        reconcile(refreshed);
      }
    } catch {
      // The current presentation and values are already the last valid state.
    }
  }

  function reconcile(refreshed: FormPayload): void {
    renderError.value = undefined;
    const focusedPath = document.activeElement?.closest<HTMLElement>(
      '[data-form-control-path]'
    )?.dataset.formControlPath;

    mergeMissing(values, refreshed.values);
    rememberControlPaths(refreshed.nodes);
    visitControls(refreshed.nodes, (control) => {
      if (control.mode !== 'editable') {
        setValue(values, control.path, valueAt(refreshed.values, control.path));
      }
    });
    payload.value = refreshed;
    emitMutation();

    if (focusedPath) {
      nextTick(() => {
        [...document.querySelectorAll<HTMLElement>('[data-form-control-path]')]
          .find((element) => element.dataset.formControlPath === focusedPath)
          ?.focus();
      });
    }
  }

  function mutation(): FormPayload['values'] {
    const groups = new Map<string, string[]>();
    const editablePaths = new Set<string>();

    visitControls(payload.value.nodes, (control) => {
      if (control.mode === 'editable') {
        groups.set(JSON.stringify(control.deltaGroup), control.deltaGroup);
        editablePaths.add(JSON.stringify(control.path));
      }
    });

    const result: FormPayload['values'] = {};

    for (const path of groups.values()) {
      const current = groupValue(values, path, editablePaths);
      const original = groupValue(baseline, path, editablePaths);

      if (canonical(current) !== canonical(original)) {
        if (path.length === 0 && isRecord(current)) {
          Object.assign(result, current);

          continue;
        }

        setValue(result, path, current);
      }
    }

    return result;
  }

  function emitMutation(): void {
    emit('update:mutation', mutation());
  }

  function invalidate(message: string): void {
    renderError.value ??= message;
  }

  function advanceBaseline(): void {
    baseline = structuredClone(toRaw(values));
    emitMutation();
  }

  function currentValues(): FormPayload['values'] {
    return structuredClone(toRaw(values));
  }

  defineExpose({advanceBaseline, currentValues});

  function visitControls(
    nodes: FormNodePayload[],
    visit: (control: FormControlPayload) => void
  ): void {
    for (const node of nodes) {
      if (node.control) {
        visit(node.control);
      }

      if (node.children) {
        visitControls(node.children, visit);
      }
    }
  }

  function rememberControlPaths(nodes: FormNodePayload[]): void {
    visitControls(nodes, (control) =>
      knownControlPaths.set(JSON.stringify(control.path), control.path)
    );
  }

  function groupValue(
    source: FormPayload['values'],
    groupPath: string[],
    editablePaths: Set<string>
  ): unknown {
    const value = structuredClone(toRaw(valueAt(source, groupPath)));

    for (const [key, controlPath] of knownControlPaths) {
      if (
        !editablePaths.has(key) &&
        controlPath
          .slice(0, groupPath.length)
          .every((segment, index) => segment === groupPath[index])
      ) {
        unsetValue(value, controlPath.slice(groupPath.length));
      }
    }

    return value;
  }

  function valueAt(source: unknown, path: string[]): unknown {
    return path.reduce<unknown>(
      (value, segment) =>
        (value as Record<string, unknown> | undefined)?.[segment],
      source
    );
  }

  function setValue(
    source: Record<string, unknown>,
    path: string[],
    value: unknown
  ): void {
    let target = source;

    path.forEach((segment, index) => {
      if (index === path.length - 1) {
        target[segment] = value;

        return;
      }

      target[segment] ??= {};
      target = target[segment] as Record<string, unknown>;
    });
  }

  function unsetValue(source: unknown, path: string[]): void {
    if (!isRecord(source) || path.length === 0) {
      return;
    }

    const parent = path
      .slice(0, -1)
      .reduce<unknown>(
        (value, segment) =>
          (value as Record<string, unknown> | undefined)?.[segment],
        source
      );

    if (isRecord(parent)) {
      delete parent[path.at(-1)!];
    }
  }

  function mergeMissing(
    target: Record<string, unknown>,
    source: Record<string, unknown>
  ): void {
    for (const [key, value] of Object.entries(source)) {
      if (!(key in target)) {
        target[key] = structuredClone(value);

        continue;
      }

      if (isRecord(target[key]) && isRecord(value)) {
        mergeMissing(target[key], value);
      }
    }
  }

  function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
  }

  function canonical(value: unknown): string {
    return JSON.stringify(canonicalValue(value));
  }

  function canonicalValue(value: unknown): unknown {
    if (Array.isArray(value)) {
      return value.map(canonicalValue);
    }

    if (!isRecord(value)) {
      return value;
    }

    return Object.fromEntries(
      Object.keys(value)
        .sort()
        .map((key) => [key, canonicalValue(value[key])])
    );
  }
</script>

<template>
  <span ref="root" hidden></span>
  <p v-if="renderError" role="alert">{{ renderError }}</p>
  <template v-else>
    <ul v-if="payload.globalErrors.length" class="error-list" role="alert">
      <li v-for="error in payload.globalErrors" :key="error">{{ error }}</li>
    </ul>
    <FormNode
      v-for="node in payload.nodes"
      :key="node.uid ?? node.control?.path.join('.')"
      :node="node"
      :values="values"
      :errors="errors ?? payload.errors"
      :touched-paths="touchedPaths"
      @change="onChange"
    />
  </template>
</template>

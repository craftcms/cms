<script setup lang="ts">
  /**
   * One source's settings, as `ElementSourcesController::ui()` builds them.
   * Values are namespaced at `sources.<key>`, the shape `store()` reads back.
   */
  import {shallowRef} from 'vue';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import type {UiChange, UiPayload} from '@/modules/ui/types';
  import {pathsMatch, valueAt, visitControls} from '@/modules/ui/runtime';
  import {fetchSourceUi} from './fetchSourceUi';
  import type {SourceType} from './types';

  const props = defineProps<{
    elementType: string;
    sourceKey: string;
    type: SourceType;
    payload: UiPayload;
    errors?: UiPayload['errors'];
    /** Sent along with every refresh, to build the settings the same way. */
    params?: Record<string, unknown>;
  }>();

  const emit = defineEmits<{
    change: [change: UiChange, values: UiPayload['values']];
  }>();

  const renderer = shallowRef<InstanceType<typeof UiRenderer>>();

  // `values` is already relative to `scope`, unlike currentValues().
  const refresh = (values: UiPayload['values'], scope?: string[]) =>
    fetchSourceUi(
      props.elementType,
      props.sourceKey,
      props.type,
      values,
      scope,
      props.params
    );

  /** A newly chosen sort attribute starts in its own default direction. */
  function onChange(change: UiChange, values: UiPayload['values']): void {
    if (change.path.at(-1) === 'attr' && change.path.at(-2) === 'defaultSort') {
      const attr = valueAt(values, change.path);
      let dir: string | null = null;

      visitControls(props.payload.nodes, (control) => {
        if (!pathsMatch(control.path, change.path)) return;
        const options = (control.props.options ?? []) as Array<{
          value: unknown;
          defaultDir?: string;
        }>;
        dir =
          options.find((option) => option.value === attr)?.defaultDir ?? null;
      });

      if (dir)
        renderer.value?.setValue([...change.path.slice(0, -1), 'dir'], dir);
    }

    emit('change', change, values);
  }

  defineExpose({
    currentValues: (): UiPayload['values'] =>
      renderer.value?.currentValues() ?? props.payload.values,
    setValue: (path: string[], value: UiPayload['values'][string]) =>
      renderer.value?.setValue(path, value),
  });
</script>

<template>
  <UiRenderer
    ref="renderer"
    :payload="payload"
    :errors="errors ?? []"
    :refresh="payload.refreshable ? refresh : undefined"
    @change="onChange"
  />
</template>

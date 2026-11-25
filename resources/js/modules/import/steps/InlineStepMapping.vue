<script setup lang="ts">
  /**
   * Loads a step's mapping structure and edits the mapping in place. It loads again when
   * anything about the step besides its mapping changes, such as what it imports, where
   * from, or which entry type, since that can change the columns.
   */
  import '@craftcms/ui/components/spinner/spinner';
  import {t} from '@craftcms/ui';
  import {shallowRef, watch} from 'vue';
  import type {
    ImportStep,
    MappingValues,
    MappingValueTree,
  } from '@/modules/import/mapping/types';
  import {fetchStepMapping, type StepMappingPayload} from './step-mapping';
  import StepMappingEditor from './StepMappingEditor.vue';

  const {step, editable, canMap} = defineProps<{
    step: ImportStep;
    editable: boolean;
    /** Whether the server last reported the step as mappable. */
    canMap: boolean;
  }>();

  const emit = defineEmits<{change: [values: MappingValues]}>();

  const data = shallowRef<StepMappingPayload | null>(null);
  const loading = shallowRef(false);
  const error = shallowRef<string | null>(null);
  /** Remounts the editor for each load, so it starts from that load's trees. */
  const loadCount = shallowRef(0);
  const editor = shallowRef<InstanceType<typeof StepMappingEditor> | null>(
    null
  );

  /** The settings the mapping edits, which don't change its structure. */
  const mappingTrees: MappingValueTree[] = [
    'map',
    'matchCriteria',
    'clearableItems',
    'defaultValues',
    'keepMissingNestedElements',
    'fieldSettings',
    'templates',
    'matchMode',
  ];

  /** Everything about the step that can change its columns: all of it but its mapping. */
  function structureKey(): string {
    // PHP sends empty settings as `[]`
    const settings: Record<string, unknown> = Array.isArray(step.settings)
      ? {}
      : {...step.settings};

    for (const tree of mappingTrees) {
      delete settings[tree];
    }

    return JSON.stringify({
      type: step.type,
      source: step.source,
      transformer: step.transformer,
      settings,
    });
  }

  /** Shows the server's errors for the step's templates on their columns. */
  async function showTemplateErrors(
    errors: Record<string, string[]>
  ): Promise<void> {
    await editor.value?.showTemplateErrors(errors);
  }

  defineExpose({showTemplateErrors});

  watch(
    [() => canMap, structureKey],
    async ([mappable], _previous, onCleanup) => {
      let current = true;
      onCleanup(() => (current = false));

      if (!mappable) {
        data.value = null;
        return;
      }

      loading.value = true;
      error.value = null;

      try {
        const response = await fetchStepMapping(step);

        if (current) {
          data.value = response.available ? response : null;
          error.value = response.available ? null : (response.message ?? null);
          loadCount.value++;
        }
      } catch (cause) {
        if (current) {
          error.value =
            cause instanceof Error && cause.message
              ? cause.message
              : t('Couldn’t open the mapping.');
        }
      } finally {
        if (current) {
          loading.value = false;
        }
      }
    },
    {immediate: true}
  );
</script>

<template>
  <section class="grid gap-md">
    <h3 class="m-0 text-base">{{ t('Field mapping') }}</h3>
    <p v-if="error" class="m-0 error" role="alert">{{ error }}</p>
    <StepMappingEditor
      v-if="data"
      ref="editor"
      :key="loadCount"
      :data="data"
      :step="step"
      :editable="editable"
      @change="emit('change', $event)"
    />
    <craft-spinner v-else-if="loading" role="status">{{
      t('Loading')
    }}</craft-spinner>
    <p v-else-if="!error" class="m-0 text-quiet">
      {{ t('Choose what to import, and from where, to map its fields.') }}
    </p>
  </section>
</template>

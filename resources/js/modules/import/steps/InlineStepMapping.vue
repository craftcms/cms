<script setup lang="ts">
  /**
   * A step's mapping at a glance, in full, with buttons to edit it, or a container's own,
   * in their panels. It loads the step's mapping structure for its destinations' labels,
   * again when anything about the step besides its mapping changes, such as what it
   * imports, where from, or which entry type, since that can change the columns.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/spinner/spinner';
  import {t} from '@craftcms/ui';
  import {computed, shallowRef, watch} from 'vue';
  import type {
    ImportStep,
    MappingColumnEntry,
    MappingValues,
    MappingValueTree,
  } from '@/modules/import/mapping/types';
  import {defaultElementTypeOf} from '@/modules/import/mapping/empty-values';
  import type {DestinationLabels} from './mapping-summary';
  import {
    fetchStepMapping,
    openStepMapping,
    openStepNestedMapping,
    type StepMappingPayload,
  } from './step-mapping';
  import StepMappingSummary from './StepMappingSummary.vue';

  const {step, editable, canMap, editUrl} = defineProps<{
    step: ImportStep;
    editable: boolean;
    /** Whether the server last reported the step as mappable. */
    canMap: boolean;
    /** The mapping's own page. Unset for a step yet to be saved. */
    editUrl?: string | null;
  }>();

  const emit = defineEmits<{change: [values: MappingValues]}>();

  const data = shallowRef<StepMappingPayload | null>(null);
  const loading = shallowRef(false);
  const error = shallowRef<string | null>(null);
  /** The server's errors for the step's mapping and templates, by path. */
  const mappingErrors = shallowRef<Record<string, string[]>>({});
  /** What's opening: the whole mapping, or a container's by its key. */
  const opening = shallowRef<{nested: string | null} | null>(null);

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

  /** Each destination's label, by its key in the map: its layout uid, or its handle in a map saved with handles. */
  const labels = computed<DestinationLabels>(() => {
    const result: DestinationLabels = {};
    const add = (entries: MappingColumnEntry[]): void => {
      for (const entry of entries) {
        if ('subfields' in entry) {
          add(entry.subfields as MappingColumnEntry[]);
          continue;
        }

        const label = {
          label: entry.label,
          handle: entry.handle,
          icon: entry.icon ?? null,
          defaultElementType: defaultElementTypeOf(entry),
        };
        result[entry.prefixedHandleAsArray.join('.')] = label;
        result[entry.handle] ??= label;
      }
    };

    add(data.value?.destinationCols ?? []);

    return result;
  });

  /** Shows the server's errors for the step's mapping and templates under it. */
  function showMappingErrors(errors: Record<string, string[]>): void {
    mappingErrors.value = errors;
  }

  defineExpose({showMappingErrors});

  const errorMessages = computed(() =>
    Object.values(mappingErrors.value).flat()
  );

  async function edit(opener: HTMLElement, nested: string | null = null) {
    if (opening.value !== null) {
      return;
    }

    opening.value = {nested};
    const options = {
      step,
      editable,
      opener,
      apply: (values: MappingValues) => {
        mappingErrors.value = {};
        emit('change', values);
      },
    };

    try {
      if (nested === null) {
        await openStepMapping(
          {...options, editUrl},
          editable ? t('Edit mapping') : t('View mapping')
        );
      } else {
        await openStepNestedMapping({...options, key: nested});
      }
    } catch (cause) {
      error.value =
        cause instanceof Error && cause.message
          ? cause.message
          : t('Couldn’t open the mapping.');
    } finally {
      opening.value = null;
    }
  }

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
  <!-- a new step has nothing to map until it knows what it imports, and from where -->
  <section v-if="canMap" class="grid gap-md">
    <p v-if="error" class="m-0 error" role="alert">{{ error }}</p>
    <StepMappingSummary
      v-if="data"
      :step="step"
      :labels="labels"
      :limit="Infinity"
      :editable="editable"
      :opening-nested="opening?.nested ?? null"
      @edit-nested="(key, opener) => edit(opener, key)"
    >
      <template #header="{count}">
        <div class="flex items-center justify-between gap-sm">
          <h3 class="m-0 text-base">{{ t('Field mapping') }}</h3>
          <craft-button
            v-if="count"
            size="small"
            variant="plain"
            :icon="editable ? 'pencil' : 'eye'"
            .loading="opening !== null && opening.nested === null"
            @click="edit($event.currentTarget as HTMLElement)"
          >
            {{ editable ? t('Edit mapping') : t('View mapping') }}
          </craft-button>
        </div>
      </template>
      <template v-if="editable" #empty>
        <div>
          <craft-button
            type="button"
            variant="dashed"
            icon="plus"
            .loading="opening !== null && opening.nested === null"
            @click="edit($event.currentTarget as HTMLElement)"
          >
            {{ t('Add mapping') }}
          </craft-button>
        </div>
      </template>
    </StepMappingSummary>
    <ul v-if="errorMessages.length" class="m-0 ps-lg error" role="alert">
      <li v-for="(message, i) in errorMessages" :key="i">{{ message }}</li>
    </ul>
    <template v-if="!data">
      <h3 class="m-0 text-base">{{ t('Field mapping') }}</h3>
      <craft-spinner v-if="loading" role="status">{{
        t('Loading')
      }}</craft-spinner>
    </template>
  </section>
</template>

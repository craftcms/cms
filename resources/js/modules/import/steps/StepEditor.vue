<script setup lang="ts">
  /**
   * One import step's settings and mapping, inline on the step's page.
   *
   * Every change is reported as it's made; the step only reaches the database when the
   * plan is saved. The mapping loads once the step knows what it imports into and where
   * its data comes from, and reloads when either changes.
   */
  import '@craftcms/ui/components/spinner/spinner';
  import {computed, onMounted, ref, shallowRef, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import {useDelayedLoading} from '@/common/composables/useDelayedLoading';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import type {UiPayload} from '@/modules/ui/types';
  import type {ImportStep, MappingValues} from '@/modules/import/mapping/types';
  import {dirtyState} from '@/modules/import/mapping/paths';
  import {fetchStepUi} from './step-settings';
  import {
    loadStepMapping,
    StepMappingUnavailableError,
    type StepMappingData,
  } from './step-mapping';
  import MappingLoading from './MappingLoading.vue';
  import StepMappingEditor from './StepMappingEditor.vue';
  import StepMatching from './StepMatching.vue';

  const MAPPING_TREES = [
    'map',
    'matchCriteria',
    'clearableItems',
    'keepMissingNestedElements',
    'fieldSettings',
    'templates',
  ] as const;

  const props = defineProps<{
    step: ImportStep;
    editable: boolean;
    /** The server's errors about this step, from the plan's last save. */
    errors: UiPayload['errors'];
    /**
     * Just the step's data source, its advanced settings, the rest of its settings, or its
     * mapping, for a wizard that asks for them in turn.
     */
    part?: 'source' | 'advanced' | 'settings' | 'mapping' | 'matching';
    /** Lets the mapping's fields be narrowed to a search, or to what's mapped. */
    searchable?: boolean;
  }>();

  const emit = defineEmits<{
    'update:step': [step: ImportStep];
    /** Whether the step knows what it imports into and where its data comes from. */
    'update:canMap': [canMap: boolean];
  }>();

  /** The step as it currently stands, including the mapping the form doesn't render. */
  const step = ref<ImportStep>(JSON.parse(JSON.stringify(props.step)));
  const payload = shallowRef<UiPayload | null>(null);
  const loadError = ref<string | null>(null);
  const renderer = ref<{currentValues(): UiPayload['values']} | null>(null);

  /** Whether the step can be mapped, as the server last reported it. */
  const canMap = ref(false);
  /** Why the data source can't be used, as the server last reported it. */
  const sourceError = ref<string | null>(null);

  /**
   * The importer type the rendered form was built for. The form's own type field changes
   * first, and the fields that depend on it only arrive with the refresh, so the Mapping
   * section follows this rather than the step's type.
   */
  const formType = ref(props.step.type);
  const loadingType = ref(false);
  const showLoadingType = useDelayedLoading(loadingType);
  let latestRefresh = 0;

  /**
   * The data source `canMap` was last checked against. Leaving its field checks it, rather
   * than every keystroke, and it's what the mapping is loaded for.
   */
  const checkedSource = ref(props.step.source);
  let latestCanMap = 0;

  const mapping = shallowRef<StepMappingData | null>(null);
  const loadingMapping = ref(false);
  const showLoadingMapping = useDelayedLoading(loadingMapping);
  const mappingMessage = ref<string | null>(null);
  /** Why the data source couldn't be read when the mapping was loaded. */
  const sourceReadError = ref<{source: string | null; message: string} | null>(
    null
  );
  /** Remounts the mapping editor with each load, so it starts from what was loaded. */
  const mappingKey = ref(0);
  let latestMapping = 0;
  let mappingTimer: ReturnType<typeof setTimeout> | undefined;

  /** The validation errors, plus the data source's own problem under its field. */
  const formErrors = computed<UiPayload['errors']>(() => {
    const message =
      sourceError.value ??
      (sourceReadError.value?.source === step.value.source
        ? sourceReadError.value.message
        : null);

    if (
      !message ||
      props.errors.some((error) => error.path.join('.') === 'source')
    ) {
      return props.errors;
    }

    return [...props.errors, {path: ['source'], messages: [message]}];
  });

  /** The errors about the step's templates, keyed by the column's path joined with dots. */
  const templateErrors = computed<Record<string, string[]>>(() =>
    Object.fromEntries(
      props.errors
        .filter(
          (error) =>
            error.path[0] === 'settings' && error.path[1] === 'templates'
        )
        .map((error) => [error.path.slice(2).join('.'), error.messages])
    )
  );

  function settingsOf(target: ImportStep): Record<string, unknown> {
    // PHP sends empty settings as `[]`, whose `map` would be `Array.prototype.map`
    return Array.isArray(target.settings) ? {} : (target.settings ?? {});
  }

  function mappingValues(): MappingValues {
    const settings = settingsOf(step.value);

    return Object.fromEntries(
      MAPPING_TREES.map((tree) => [tree, settings[tree] ?? {}])
    ) as unknown as MappingValues;
  }

  /**
   * What the mapping's destination columns depend on: the importer type, the data source
   * and every setting but the mapping trees, such as the section and entry type.
   */
  const destinationKey = computed(() => {
    const settings = {...settingsOf(step.value)};

    for (const tree of MAPPING_TREES) {
      delete settings[tree];
    }

    // what's done with rows that already exist doesn't change what they're imported into
    delete settings.matchMode;

    return JSON.stringify([formType.value, checkedSource.value, settings]);
  });

  function update(next: ImportStep): void {
    if (dirtyState(next) === dirtyState(step.value)) {
      return;
    }

    step.value = next;
    emit('update:step', JSON.parse(JSON.stringify(next)) as ImportStep);
  }

  /** Folds the form's current values back into the step. */
  function syncFromForm(): void {
    if (!renderer.value) {
      return;
    }

    // a part of the form only reports its own controls, so the rest of the step stands
    const values = renderer.value.currentValues();
    const has = (key: string) => key in values;
    const settings = (
      has('settings') ? values.settings : settingsOf(step.value)
    ) as Record<string, unknown>;
    const batchSize = values.batchSize;
    const next: ImportStep = {
      ...step.value,
      type: has('type') ? (values.type as string) || null : step.value.type,
      source: has('source')
        ? (values.source as string) || null
        : step.value.source,
      transformer: has('transformer')
        ? (values.transformer as string) || null
        : step.value.transformer,
      batchSize: !has('batchSize')
        ? step.value.batchSize
        : batchSize === null || batchSize === undefined || batchSize === ''
          ? null
          : Number(batchSize),
      // the mapping trees live on the step, not in the form, so they're carried over
      settings: {...settings, ...mappingValues()},
    };

    update(next);
  }

  /** The controls each part of the form holds; `settings` holds everything else. */
  const PART_CONTROLS: Record<string, string[]> = {
    source: ['source'],
    advanced: ['batchSize'],
  };

  /** The form, or just the part of it this editor shows. */
  function shownPart(ui: UiPayload): UiPayload {
    if (!props.part || props.part === 'mapping') {
      return ui;
    }

    const controlsOf = (node: UiPayload['nodes'][number]): string[] => [
      ...(node.control?.path?.[0] ? [node.control.path[0]] : []),
      ...(node.children ?? []).flatMap(controlsOf),
    ];
    const partOf = (node: UiPayload['nodes'][number]): string => {
      const controls = controlsOf(node);

      return (
        Object.keys(PART_CONTROLS).find(
          (part) =>
            controls.length > 0 &&
            controls.every((control) => PART_CONTROLS[part]!.includes(control))
        ) ?? 'settings'
      );
    };

    return {
      ...ui,
      nodes: ui.nodes.filter((node) => partOf(node) === props.part),
    };
  }

  const shownPayload = computed(() =>
    payload.value ? shownPart(payload.value) : null
  );

  /** Whether this editor shows the mapping, or the matching that's worked out from it. */
  const readsMapping = computed(
    () => !props.part || props.part === 'mapping' || props.part === 'matching'
  );

  function onMatchingChange(values: {
    matchMode: string;
    matchCriteria: Record<string, unknown>;
  }): void {
    update({
      ...step.value,
      settings: {...settingsOf(step.value), ...values},
    });
  }

  function onMappingChange(values: MappingValues): void {
    // just the mapping leaves what's matched on to the matching, which has its own part
    const {matchCriteria, ...rest} = values;
    const settings = settingsOf(step.value);

    update({
      ...step.value,
      settings: {
        ...settings,
        ...(props.part === 'mapping' ? rest : values),
        ...(props.part === 'mapping'
          ? {matchCriteria: settings.matchCriteria ?? matchCriteria}
          : {}),
      },
    });
  }

  /** The step as the settings form endpoint expects it, with the mapping trees carried over. */
  function stepForRequest(settings: Record<string, unknown>): ImportStep {
    return {...step.value, settings: {...settings, ...mappingValues()}};
  }

  onMounted(async () => {
    try {
      const response = await fetchStepUi(step.value);
      payload.value = response.ui;
      canMap.value = response.canMap;
      sourceError.value = response.sourceError;
    } catch (error) {
      loadError.value =
        error instanceof Error && error.message
          ? error.message
          : t('Couldn’t load this step.');
    }
  });

  async function refresh(values: UiPayload['values']): Promise<UiPayload> {
    syncFromForm();

    const request = ++latestRefresh;
    const canMapRequest = ++latestCanMap;
    const type = step.value.type;
    checkedSource.value = step.value.source;

    if (type !== formType.value) {
      loadingType.value = true;
    }

    try {
      const response = await fetchStepUi(
        stepForRequest(
          (values.settings ?? settingsOf(step.value)) as Record<string, unknown>
        )
      );

      // the form drops a response a newer refresh has overtaken, so this does too
      if (request === latestRefresh) {
        formType.value = type;
      }

      if (canMapRequest === latestCanMap) {
        canMap.value = response.canMap;
        sourceError.value = response.sourceError;
      }

      return shownPart(response.ui);
    } finally {
      if (request === latestRefresh) {
        loadingType.value = false;
      }
    }
  }

  function onFocusOut(event: FocusEvent): void {
    const control =
      event.target instanceof Element
        ? event.target.closest('[data-ui-control-path]')
        : null;

    if (
      control?.getAttribute('data-ui-control-path') !== '["source"]' ||
      (event.relatedTarget instanceof Node &&
        control.contains(event.relatedTarget))
    ) {
      return;
    }

    syncFromForm();

    if (step.value.source === checkedSource.value) {
      return;
    }

    checkedSource.value = step.value.source;
    void checkCanMap();
  }

  /** Asks the server whether the step can be mapped, without rebuilding the form. */
  async function checkCanMap(): Promise<void> {
    const request = ++latestCanMap;

    try {
      const response = await fetchStepUi(
        stepForRequest(settingsOf(step.value))
      );

      if (request === latestCanMap) {
        canMap.value = response.canMap;
        sourceError.value = response.sourceError;
      }
    } catch {
      // the last reported `canMap` still stands
    }
  }

  async function loadMapping(): Promise<void> {
    const request = ++latestMapping;
    mappingMessage.value = null;
    loadingMapping.value = true;

    try {
      const loaded = await loadStepMapping(step.value);

      if (request !== latestMapping) {
        return;
      }

      sourceReadError.value = null;
      mapping.value = loaded;
      mappingKey.value++;
      // the server's guesses are part of the mapping from here on
      onMappingChange(JSON.parse(JSON.stringify(loaded.values)));
    } catch (error) {
      if (request !== latestMapping) {
        return;
      }

      mapping.value = null;

      if (
        error instanceof StepMappingUnavailableError &&
        error.attribute === 'source'
      ) {
        sourceReadError.value = {
          source: step.value.source,
          message: error.message,
        };

        return;
      }

      mappingMessage.value =
        error instanceof Error && error.message
          ? error.message
          : t('Couldn’t load the mapping.');
    } finally {
      if (request === latestMapping) {
        loadingMapping.value = false;
      }
    }
  }

  // another part of the form can change the step, which this one carries on from
  watch(
    () => props.step,
    (next) => {
      if (dirtyState(next) !== dirtyState(step.value)) {
        step.value = JSON.parse(JSON.stringify(next));
      }
    }
  );

  watch(canMap, (mappable) => emit('update:canMap', mappable), {
    immediate: true,
  });

  // settings arrive a field at a time, so a burst of them loads the mapping once
  watch([canMap, destinationKey], ([mappable]) => {
    clearTimeout(mappingTimer);

    if (!mappable || !readsMapping.value) {
      latestMapping++;
      mapping.value = null;
      loadingMapping.value = false;

      return;
    }

    // loading from the moment it's scheduled, so the wait shows under the heading
    loadingMapping.value = true;
    mappingTimer = setTimeout(() => void loadMapping(), 300);
  });
</script>

<template>
  <div class="step-editor" @focusout="onFocusOut">
    <p v-if="loadError" class="error m-0" role="alert">{{ loadError }}</p>

    <craft-spinner v-else-if="!payload" role="status">
      {{ t('Loading') }}
    </craft-spinner>

    <craft-field-group v-else-if="part !== 'mapping'">
      <UiRenderer
        ref="renderer"
        :payload="shownPayload!"
        :errors="formErrors"
        :refresh="payload.refreshable ? refresh : undefined"
        @change="syncFromForm"
      />

      <craft-spinner v-if="showLoadingType" role="status">
        {{ t('Loading') }}
      </craft-spinner>
    </craft-field-group>

    <!-- shown once the step knows what it imports into and where its data comes from -->
    <p
      v-if="readsMapping && part && payload && sourceReadError"
      class="error m-0"
      role="alert"
    >
      {{ sourceReadError.message }}
    </p>
    <section
      v-if="readsMapping && payload && formType && canMap"
      class="step-editor__mapping"
    >
      <!-- once it's loaded, the mapping heads its own sections -->
      <h3 v-if="!mapping && !part" class="step-editor__heading m-0">
        {{ t('Mapping') }}
      </h3>

      <MappingLoading v-if="showLoadingMapping && !mapping" />
      <p v-if="mappingMessage" class="error m-0" role="alert">
        {{ mappingMessage }}
      </p>
      <StepMatching
        v-if="mapping && part === 'matching'"
        :cols="mapping.destinationCols"
        :step="step"
        :target-name="mapping.targetName"
        @change="onMatchingChange"
      />
      <StepMappingEditor
        v-else-if="mapping"
        :key="mappingKey"
        :mapping="mapping"
        :step="step"
        :editable="editable"
        :template-errors="templateErrors"
        :loading="showLoadingMapping"
        :headless="part === 'mapping'"
        :hide-match-summary="part === 'mapping'"
        :hide-match="part === 'mapping'"
        :searchable="searchable"
        @change="onMappingChange"
      />
    </section>
  </div>
</template>

<style scoped>
  .step-editor {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xl);
    padding: var(--c-spacing-lg);
    border-radius: var(--c-radius-lg);
    border: 1px solid var(--c-color-border-quiet);
  }

  .step-editor__mapping {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  /* styled as a field's label, since it heads the mapping as one heads its field */
  .step-editor__heading {
    font-size: var(--c-field-label-font-size, inherit);
    font-weight: var(--c-field-label-font-weight, bold);
    line-height: var(--c-field-label-line-height, inherit);
    color: var(--c-field-label-color, inherit);
  }
</style>

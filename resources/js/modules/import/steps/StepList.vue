<script setup lang="ts">
  /**
   * An import's ordered list of steps.
   *
   * The list owns the steps outright: adding, editing, reordering, disabling and
   * deleting all happen against this component's state, and the steps only reach the server when
   * the import's own form is submitted.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/empty/empty';
  import '@craftcms/ui/components/reorder-button/reorder-button';
  import {computed, ref, shallowRef, useTemplateRef} from 'vue';
  import {t} from '@craftcms/ui';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {openStepMapping} from './step-mapping';
  import {openStepSlideout, type StepSummary} from './step-slideout';
  import StepCard from './StepCard.vue';

  const props = defineProps<{
    editable: boolean;
    /** Every importer type a step can be, for labeling the summary rows. */
    importerTypes: Array<{value: string; label: string}>;
    /** Server-side errors, keyed `steps.<step uid>.<attribute>`. */
    errors?: Record<string, string[]>;
    /** Each saved step's own page, by its uid. */
    stepEditUrls?: Record<string, string>;
    /** Each step at a glance, by its uid, as the plan was loaded. */
    stepSummaries?: Record<string, StepSummary>;
  }>();

  const steps = defineModel<ImportStep[]>({required: true});

  /** Summaries of the steps added or edited since the plan was loaded, from their slideouts. */
  const appliedSummaries = ref<Record<string, StepSummary>>({});

  function summaryFor(uid: string): StepSummary | null {
    return appliedSummaries.value[uid] ?? props.stepSummaries?.[uid] ?? null;
  }

  const addButton = useTemplateRef<HTMLElement>('addButton');

  /** A slideout that couldn't be opened, as opposed to a server validation error. */
  const requestError = shallowRef<string | null>(null);

  /** The slideout being opened: a step's, by its uid, or `new` for Add. */
  const opening = shallowRef<{
    uid: string;
    slideout: 'settings' | 'mapping';
  } | null>(null);

  function openingFor(uid: string): 'settings' | 'mapping' | null {
    return opening.value?.uid === uid ? opening.value.slideout : null;
  }

  const {setItemRef, setHandleRef, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => steps.value.map((step) => step.uid),
      enabled: () => props.editable && steps.value.length > 1,
      onReorder: (from, to) => reorder(from, to),
    });

  const errorsByStep = computed(() => {
    const byStep: Record<string, string[]> = {};

    for (const [path, messages] of Object.entries(props.errors ?? {})) {
      const uid = /^steps\.([^.]+)\./.exec(path)?.[1];

      if (uid === undefined) {
        continue;
      }

      byStep[uid] = [...(byStep[uid] ?? []), ...messages];
    }

    return byStep;
  });

  /** A slideout that failed to open. Errors about the list as a whole belong to its form field. */
  const listErrors = computed(() =>
    requestError.value ? [requestError.value] : []
  );

  function newStep(): ImportStep {
    return {
      // a plain UUID: the server caps a step's uid at 36 characters
      uid: crypto.randomUUID(),
      type: null,
      source: null,
      transformer: null,
      batchSize: null,
      enabled: true,
      settings: {},
    };
  }

  const typeLabels = computed(() =>
    Object.fromEntries(
      props.importerTypes.map((type) => [type.value, type.label])
    )
  );

  function typeLabel(step: ImportStep): string {
    return step.type
      ? (typeLabels.value[step.type] ?? step.type)
      : t('Choose an importer');
  }

  function reorder(from: number, to: number): void {
    if (to < 0 || to >= steps.value.length) {
      return;
    }

    const next = [...steps.value];
    const [moved] = next.splice(from, 1);

    if (!moved) {
      return;
    }

    next.splice(to, 0, moved);
    steps.value = next;
  }

  const enabledCount = computed(
    () => steps.value.filter((step) => step.enabled).length
  );

  /** A step can only be disabled while another step stays enabled. */
  function canToggle(step: ImportStep): boolean {
    return props.editable && (!step.enabled || enabledCount.value > 1);
  }

  function toggleEnabled(step: ImportStep): void {
    steps.value = steps.value.map((existing) =>
      existing.uid === step.uid
        ? {...existing, enabled: !existing.enabled}
        : existing
    );
  }

  function replace(uid: string, step: ImportStep): void {
    steps.value = steps.value.map((existing) =>
      existing.uid === uid ? {...step, uid} : existing
    );
  }

  function remove(step: ImportStep): void {
    if (
      !window.confirm(t('Are you sure you want to delete this import step?'))
    ) {
      return;
    }

    steps.value = steps.value.filter((existing) => existing.uid !== step.uid);
  }

  /**
   * Opening a step's slideout asks the server to build its form, so it can fail. Report it
   * rather than letting the rejection go unhandled, which reads as the button doing nothing.
   */
  async function openStep(
    uid: string,
    options: Parameters<typeof openStepSlideout>[0],
    title: string
  ): Promise<void> {
    if (opening.value !== null) {
      return;
    }

    requestError.value = null;
    opening.value = {uid, slideout: 'settings'};

    try {
      await openStepSlideout(options, title);
    } catch (error) {
      requestError.value =
        error instanceof Error && error.message
          ? error.message
          : t('Couldn’t open this step.');
    } finally {
      opening.value = null;
    }
  }

  async function add(): Promise<void> {
    const step = newStep();

    await openStep(
      'new',
      {
        step,
        editable: props.editable,
        opener: addButton.value,
        apply: (applied, summary) => {
          steps.value = [...steps.value, {...applied, uid: step.uid}];
          appliedSummaries.value = {
            ...appliedSummaries.value,
            [step.uid]: summary,
          };
        },
      },
      t('Add an import step')
    );
  }

  async function edit(
    step: ImportStep,
    opener: HTMLElement | null
  ): Promise<void> {
    await openStep(
      step.uid,
      {
        step,
        editable: props.editable,
        editUrl: props.stepEditUrls?.[step.uid] ?? null,
        opener,
        apply: (applied, summary) => {
          replace(step.uid, applied);
          appliedSummaries.value = {
            ...appliedSummaries.value,
            [step.uid]: summary,
          };
        },
      },
      t('Edit import step')
    );
  }

  /** Opens a step's mapping straight from the list, without its settings slideout. */
  async function editMapping(
    step: ImportStep,
    opener: HTMLElement | null
  ): Promise<void> {
    if (opening.value !== null) {
      return;
    }

    requestError.value = null;
    opening.value = {uid: step.uid, slideout: 'mapping'};

    try {
      await openStepMapping(
        {
          step,
          editable: props.editable,
          opener,
          apply: (values) => {
            // PHP sends empty settings as `[]`, which can't be spread into
            const settings = Array.isArray(step.settings)
              ? {}
              : (step.settings ?? {});
            replace(step.uid, {...step, settings: {...settings, ...values}});
          },
        },
        t('Edit mapping')
      );
    } catch (error) {
      requestError.value =
        error instanceof Error && error.message
          ? error.message
          : t('Couldn’t open the mapping.');
    } finally {
      opening.value = null;
    }
  }
</script>

<template>
  <craft-empty v-if="!steps.length" :label="t('No steps yet.')"></craft-empty>

  <ol v-else class="grid gap-md" role="list">
    <StepCard
      v-for="(step, index) in steps"
      :key="step.uid"
      :ref="
        (el) => setItemRef(el as Parameters<typeof setItemRef>[0], step.uid)
      "
      :step="step"
      :number="index + 1"
      :type-label="typeLabel(step)"
      :summary="summaryFor(step.uid)"
      :editable="editable"
      :can-toggle="canToggle(step)"
      :invalid="Boolean(errorsByStep[step.uid])"
      :opening="openingFor(step.uid)"
      :over="getDropState(step.uid).type === 'is-over'"
      @edit="edit(step, $event)"
      @edit-mapping="editMapping(step, $event)"
      @toggle-enabled="toggleEnabled(step)"
      @remove="remove(step)"
    >
      <template #handle>
        <craft-reorder-button
          v-if="editable && steps.length > 1"
          :ref="
            (el: Parameters<typeof setHandleRef>[0]) =>
              setHandleRef(el, step.uid)
          "
          :position="getRowPosition(index)"
          size="small"
          @craft-reorder="
            reorder(index, index + ($event.detail.direction === 'up' ? -1 : 1))
          "
        ></craft-reorder-button>
      </template>
    </StepCard>
  </ol>

  <ul v-if="listErrors.length" class="error-list" role="alert">
    <li v-for="error in listErrors" :key="error">{{ error }}</li>
  </ul>

  <div class="mt-md">
    <craft-button
      v-if="editable"
      ref="addButton"
      type="button"
      icon="plus"
      variant="dashed"
      .loading="openingFor('new') !== null"
      @click="add"
    >
      {{ t('Add a step') }}
    </craft-button>
  </div>
</template>

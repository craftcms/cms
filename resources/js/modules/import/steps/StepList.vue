<script setup lang="ts">
  /**
   * An import's ordered list of steps.
   *
   * The list owns the steps outright: adding, editing, reordering and deleting all
   * happen against this component's state, and the steps only reach the server when
   * the import's own form is submitted.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/empty/empty';
  import '@craftcms/ui/components/reorder-button/reorder-button';
  import {computed, ref} from 'vue';
  import {t} from '@craftcms/ui';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {openStepSlideout} from './step-slideout';

  const props = defineProps<{
    editable: boolean;
    /** Every importer type a step can be, for labelling the summary rows. */
    importerTypes: Array<{value: string; label: string}>;
    /** Server-side errors, keyed `steps.<step uid>.<attribute>`. */
    errors?: Record<string, string[]>;
  }>();

  const steps = defineModel<ImportStep[]>({required: true});

  const addButton = ref<HTMLElement | null>(null);
  const itemButtons = ref<Record<string, HTMLElement | null>>({});

  /** A slideout that couldn't be opened, as opposed to a server validation error. */
  const requestError = ref<string | null>(null);

  /** Which slideout is being opened: `new` for Add, or the uid of the step being edited. */
  const openingStep = ref<string | null>(null);

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
      settings: {},
    };
  }

  const typeLabels = computed(() =>
    Object.fromEntries(
      props.importerTypes.map((type) => [type.value, type.label])
    )
  );

  function summary(step: ImportStep): string {
    const label = step.type
      ? (typeLabels.value[step.type] ?? step.type)
      : t('Choose an importer');

    return [label, step.source].filter(Boolean).join(' — ');
  }

  function stepLabel(step: ImportStep, index: number): string {
    return `${index + 1}. ${summary(step)}`;
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
    key: string,
    options: Parameters<typeof openStepSlideout>[0],
    title: string
  ): Promise<void> {
    if (openingStep.value !== null) {
      return;
    }

    requestError.value = null;
    openingStep.value = key;

    try {
      await openStepSlideout(options, title);
    } catch (error) {
      requestError.value =
        error instanceof Error && error.message
          ? error.message
          : t('Couldn’t open this step.');
    } finally {
      openingStep.value = null;
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
        apply: (applied) => {
          steps.value = [...steps.value, {...applied, uid: step.uid}];
        },
      },
      t('Add an import step')
    );
  }

  async function edit(step: ImportStep): Promise<void> {
    await openStep(
      step.uid,
      {
        step,
        editable: props.editable,
        opener: itemButtons.value[step.uid] ?? null,
        apply: (applied) => replace(step.uid, applied),
      },
      t('Edit import step')
    );
  }
</script>

<template>
  <craft-pane appearance="raised">
    <h2>{{ t('Steps') }}</h2>
    <p>
      {{
        t(
          'Each step imports one kind of data. Steps run in the order listed here.'
        )
      }}
    </p>

    <craft-empty v-if="!steps.length" :label="t('No steps yet.')"></craft-empty>

    <ol v-else class="m-0 p-0 list-none space-y-2">
      <li
        v-for="(step, index) in steps"
        :key="step.uid"
        :ref="(el) => setItemRef(el, step.uid)"
        class="flex flex-wrap items-center gap-3 rounded p-2"
        :class="{'bg-gray-100': getDropState(step.uid).type === 'is-over'}"
      >
        <craft-reorder-button
          :ref="
            (el: Parameters<typeof setHandleRef>[0]) =>
              setHandleRef(el, step.uid)
          "
          .disabled="!editable || steps.length < 2"
          :position="getRowPosition(index)"
          @craft-reorder="
            reorder(index, index + ($event.detail.direction === 'up' ? -1 : 1))
          "
        ></craft-reorder-button>

        <span class="flex-1">
          <span class="font-bold">{{ stepLabel(step, index) }}</span>
          <span v-if="errorsByStep[step.uid]" class="error block">
            {{
              t('The step “{step}” is invalid. Edit it to fix the problem.', {
                step: summary(step),
              })
            }}
          </span>
        </span>

        <craft-button
          :ref="(el: HTMLElement | null) => (itemButtons[step.uid] = el)"
          type="button"
          .loading="openingStep === step.uid"
          :aria-label="
            t(editable ? 'Edit {type}' : 'View {type}', {
              type: stepLabel(step, index),
            })
          "
          @click="edit(step)"
        >
          {{ editable ? t('Edit') : t('View') }}
        </craft-button>

        <craft-button
          type="button"
          icon="trash"
          :aria-label="t('Delete {type}', {type: stepLabel(step, index)})"
          .disabled="!editable"
          @click="remove(step)"
        ></craft-button>
      </li>
    </ol>

    <ul v-if="listErrors.length" class="error-list" role="alert">
      <li v-for="error in listErrors" :key="error">{{ error }}</li>
    </ul>

    <craft-button
      v-if="editable"
      ref="addButton"
      type="button"
      icon="plus"
      .loading="openingStep === 'new'"
      @click="add"
    >
      {{ t('Add a step') }}
    </craft-button>
  </craft-pane>
</template>

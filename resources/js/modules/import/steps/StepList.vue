<script setup lang="ts">
  /**
   * An import's ordered list of steps, as cards like a workflow's stages, each just its
   * heading: the step's name and its data source.
   *
   * Everything happens against this component's state, and reaches the server when the
   * import's own form is submitted. A step is added and edited in a slideout, so the plan
   * can get its steps before it's ever saved.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/empty/empty';
  import {computed, ref} from 'vue';
  import {ELEMENT_QUICK_EDIT_CONTROL_SELECTOR} from '@/modules/elements/composables/useElementQuickEdit';
  import {t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import SelectableCardList from '@/common/components/SelectableCardList.vue';
  import {useSelectable} from '@/common/composables/useSelectable';
  import type {ActionItems} from '@/common/types';
  import type {UiPayload} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import NestedElementsCreateButton from '@/modules/ui/nested-elements/NestedElementsCreateButton.vue';
  import {openStepSlideout} from './step-slideout';

  const props = defineProps<{
    editable: boolean;
    /** Every importer type a step can be, for labeling the summary rows. */
    importerTypes: Array<{value: string; label: string}>;
    /** Server-side errors, keyed `steps.<step uid>.<attribute>`. */
    errors?: Record<string, string[]>;
    /** A step's own page, for one that's been saved. */
    stepPageUrl?: (step: ImportStep) => string | null;
  }>();

  const steps = defineModel<ImportStep[]>({required: true});

  // the cards are reordered, not selected
  const selection = useSelectable<string>({
    ids: () => steps.value.map((step) => step.uid),
    enabled: false,
    readOnly: true,
  });

  /** The server's errors, regrouped by step and keyed by the path within it. */
  const errorsByStep = computed(() => {
    const byStep: Record<string, UiPayload['errors']> = {};

    for (const [path, messages] of Object.entries(props.errors ?? {})) {
      const [, uid, ...rest] = path.split('.');

      if (uid === undefined || !rest.length || !path.startsWith('steps.')) {
        continue;
      }

      (byStep[uid] ??= []).push({path: rest, messages});
    }

    return byStep;
  });

  const typeLabels = computed(() =>
    Object.fromEntries(
      props.importerTypes.map((type) => [type.value, type.label])
    )
  );

  /** The step being opened, while its slideout loads. */
  const opening = ref<string | null>(null);
  const openError = ref<string | null>(null);

  /** The importer type a new step is being opened with, while its slideout loads. */
  const addingType = ref<string | null>(null);

  function newStep(type: string): ImportStep {
    return {
      // a plain UUID: the server caps a step's uid at 36 characters
      uid: crypto.randomUUID(),
      type,
      source: null,
      transformer: null,
      batchSize: null,
      enabled: true,
      settings: {},
    };
  }

  function typeLabel(step: ImportStep): string {
    return step.type
      ? (typeLabels.value[step.type] ?? step.type)
      : t('Choose an importer');
  }

  function summary(step: ImportStep): string {
    return [typeLabel(step), step.source].filter(Boolean).join(' — ');
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

  function remove(step: ImportStep): void {
    if (
      !window.confirm(t('Are you sure you want to delete this import step?'))
    ) {
      return;
    }

    steps.value = steps.value.filter((existing) => existing.uid !== step.uid);
  }

  function pageUrl(step: ImportStep): string | undefined {
    return props.stepPageUrl?.(step) ?? undefined;
  }

  function stepActions(step: ImportStep): ActionItems {
    const actions: ActionItems = [
      {
        label: props.editable ? t('Edit step') : t('View step'),
        icon: props.editable ? 'pencil' : 'eye',
        onClick: () => void openStep(step, null),
      },
    ];

    if (props.editable) {
      actions.push(
        {
          label: step.enabled ? t('Disable step') : t('Enable step'),
          icon: step.enabled ? 'toggle-off' : 'toggle-on',
          disabled: !canToggle(step),
          onClick: () => toggleEnabled(step),
        },
        {
          label: t('Delete step'),
          icon: 'trash',
          variant: 'danger',
          onClick: () => remove(step),
        }
      );
    }

    return actions;
  }

  async function openSlideout(
    key: string,
    options: Parameters<typeof openStepSlideout>[0]
  ): Promise<void> {
    if (opening.value !== null) {
      return;
    }

    openError.value = null;
    opening.value = key;

    try {
      await openStepSlideout(options);
    } catch (error) {
      openError.value =
        error instanceof Error && error.message
          ? error.message
          : t('Couldn’t open this step.');
    } finally {
      opening.value = null;
    }
  }

  /**
   * Opens a step in a slideout, as a nested element's card opens its editor. A modified
   * click on its link, or a double-click, opens a saved step's page in a new tab instead.
   */
  async function openStep(
    step: ImportStep,
    opener: HTMLElement | null
  ): Promise<void> {
    const index = steps.value.findIndex(
      (candidate) => candidate.uid === step.uid
    );

    await openSlideout(step.uid, {
      step,
      editable: props.editable,
      errors: errorsByStep.value[step.uid] ?? [],
      editUrl: pageUrl(step) ?? null,
      title: props.editable
        ? t('Edit step {number}', {number: index + 1})
        : t('Step {number}', {number: index + 1}),
      opener,
      apply: (applied) => {
        steps.value = steps.value.map((existing) =>
          existing.uid === applied.uid ? applied : existing
        );
      },
    });
  }

  function onCardDblclick(uid: string, event: MouseEvent): void {
    const step = steps.value.find((candidate) => candidate.uid === uid);
    const target = event.target;

    // a double-click on one of the card's own controls is about that control
    if (
      !step ||
      !(target instanceof Element) ||
      target.closest(ELEMENT_QUICK_EDIT_CONTROL_SELECTOR)
    ) {
      return;
    }

    event.preventDefault();
    window.getSelection()?.removeAllRanges();

    const url = pageUrl(step);

    if (url && (event.ctrlKey || event.metaKey)) {
      window.open(url, '_blank', 'noopener');

      return;
    }

    void openStep(step, event.currentTarget as HTMLElement);
  }

  function onEditLink(step: ImportStep, event: MouseEvent): void {
    // a step that's yet to be saved has no page to open instead
    if (
      pageUrl(step) &&
      (event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey)
    ) {
      return;
    }

    event.preventDefault();
    void openStep(step, event.currentTarget as HTMLElement);
  }

  /** Opens a new step of the chosen importer type, which its settings start from. */
  async function add(type: string, opener: HTMLElement): Promise<void> {
    addingType.value = type;

    try {
      await openSlideout('new', {
        step: newStep(type),
        editable: true,
        errors: [],
        editUrl: null,
        title: t('Add an import step'),
        opener,
        apply: (step) => {
          steps.value = [...steps.value, step];
        },
      });
    } finally {
      addingType.value = null;
    }
  }
</script>

<template>
  <SelectableCardList
    v-if="steps.length"
    :ids="steps.map((step) => step.uid)"
    :selection="selection"
    :sortable="editable && steps.length > 1"
    :read-only="!editable"
    single-column
    tag="div"
    item-tag="div"
    list-class="grid gap-md"
    :item-attrs="
      (uid, index) => ({
        role: 'listitem',
        'aria-label': stepLabel(steps[index]!, index),
      })
    "
    :card-attrs="() => ({collapsed: true})"
    role="list"
    @reorder="reorder"
    @item-dblclick="onCardDblclick"
  >
    <template #label="{index}">
      <div class="step-card__heading">
        <div class="step-card__title">
          <a
            :id="`step-label-${steps[index]!.uid}`"
            :href="pageUrl(steps[index]!) ?? '#'"
            class="step-card__open"
            :aria-describedby="
              errorsByStep[steps[index]!.uid]
                ? `step-error-${steps[index]!.uid}`
                : undefined
            "
            @click="onEditLink(steps[index]!, $event)"
            >{{ index + 1 }}. {{ typeLabel(steps[index]!) }}</a
          >
          <craft-badge v-if="!steps[index]!.enabled" fill="gray" size="small">{{
            t('Disabled')
          }}</craft-badge>
          <craft-badge
            v-if="errorsByStep[steps[index]!.uid]"
            :id="`step-error-${steps[index]!.uid}`"
            fill="red"
            size="small"
            >{{ t('Has errors') }}</craft-badge
          >
        </div>
        <code v-if="steps[index]!.source" class="step-card__source">{{
          steps[index]!.source
        }}</code>
      </div>
    </template>

    <template #actions="{index}">
      <craft-button
        variant="plain"
        inherit
        type="button"
        size="small"
        icon="edit"
        :aria-label="
          t('Edit {title}', {title: stepLabel(steps[index]!, index)})
        "
        :title="t('Edit')"
        @click.stop="
          openStep(steps[index]!, $event.currentTarget as HTMLElement)
        "
      ></craft-button>
      <ActionMenu
        v-if="stepActions(steps[index]!).length"
        :actions="stepActions(steps[index]!)"
        :label="t('Step {num} actions', {num: index + 1})"
        :flush="false"
      />
    </template>
  </SelectableCardList>

  <craft-empty v-else appearance="dashed" :label="t('No steps yet.')">
    <NestedElementsCreateButton
      v-if="editable"
      :choices="importerTypes"
      :label="t('Create step')"
      :disabled="opening !== null"
      :adding="addingType"
      menu
      @create="add"
    />
  </craft-empty>

  <div v-if="editable && steps.length" class="step-list__actions">
    <NestedElementsCreateButton
      :choices="importerTypes"
      :label="t('Create step')"
      :disabled="opening !== null"
      :adding="addingType"
      menu
      @create="add"
    />
  </div>
  <p v-if="openError" class="error" role="alert">{{ openError }}</p>
</template>

<style scoped>
  .step-card__heading {
    display: grid;
    gap: var(--c-spacing-xs);
    min-width: 0;
  }

  .step-card__title {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-xs) var(--c-spacing-sm);
  }

  .step-card__open {
    font-weight: 600;
    color: inherit;
  }

  .step-card__source {
    color: var(--c-text-quiet);
    overflow-wrap: anywhere;
  }

  .step-list__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm) var(--c-spacing-md);
    margin-block-start: var(--c-spacing-md);
  }
</style>

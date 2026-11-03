<script setup lang="ts">
  /**
   * A new import plan, a question at a time: its name, then each step's data and fields, then
   * a review. Each section collapses to a summary once it's done, and opens again to change
   * it. Nothing is saved until the review, which posts the whole plan.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/field-group/field-group';
  import {computed, nextTick, reactive, ref} from 'vue';
  import {router} from '@inertiajs/vue3';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {actionClient, t} from '@craftcms/ui';
  import {reviewSteps} from '@actions/Import/ImportPlansController';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import type {UiPayload} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {cloneStep} from '@/modules/import/mapping/paths';
  import StepEditor from '@/modules/import/steps/StepEditor.vue';
  import {validateStep} from '@/modules/import/steps/step-slideout';
  import ImportPlanReview from '@/modules/import/ImportPlanReview.vue';
  import ImportWizardSection from '@/modules/import/ImportWizardSection.vue';
  import SourcePreview from '@/modules/import/steps/SourcePreview.vue';

  const props = defineProps<{
    title: string;
    /** The plan's name, handle and description. */
    ui: UiPayload;
    importerTypes: Array<{value: string; label: string}>;
    submit: UrlMethodPair;
    /** Unset for a user who can't run import plans. */
    runUrl: string | null;
    /** A plan's edit page, with `__handle__` for its handle. */
    editUrl: string;
    indexUrl: string;
  }>();

  useAppLayout(() => ({title: props.title}));

  const MAPPING_TREES = [
    'map',
    'matchCriteria',
    'clearableItems',
    'keepMissingNestedElements',
    'fieldSettings',
    'templates',
  ];

  const details = reactive({name: '', handle: '', description: ''});
  const detailsRenderer = ref<{currentValues(): UiPayload['values']} | null>(
    null
  );
  const steps = ref<ImportStep[]>([]);
  /** Whether each step's data source could be read. */
  const readable = reactive<Record<string, boolean>>({});
  /** Whether each step knows what it imports into and where its data comes from. */
  const mappable = reactive<Record<string, boolean>>({});
  const detailErrors = ref<UiPayload['errors']>([]);
  const stepErrors = reactive<Record<string, UiPayload['errors']>>({});
  const stepHints = reactive<Record<string, string | null>>({});
  /** Why each step's rows couldn't be read with its mapping, from the last check. */
  const readErrors = reactive<Record<string, string | null>>({});

  /** The open section, or `add` while the plan waits for its first step. */
  const current = ref('details');
  const completed = reactive(new Set<string>());
  const checking = ref(false);
  const saving = ref<'save' | 'run' | null>(null);
  const saveError = ref<string | null>(null);

  const STEP_PARTS = ['source', 'destination', 'fields', 'matching'] as const;
  type StepPart = (typeof STEP_PARTS)[number];
  const keyOf = (step: ImportStep, part: StepPart) => `${step.uid}:${part}`;

  const order = computed(() => [
    'details',
    ...steps.value.flatMap((step) =>
      STEP_PARTS.map((part) => keyOf(step, part))
    ),
    'review',
  ]);

  /**
   * Why a section can't be opened yet, if it can't: a step's destination needs data to
   * import, and its fields need to know what that data goes into.
   */
  function waitingFor(key: string): string | null {
    if (key === 'review') {
      return steps.value.length ? null : t('Add a step to review it.');
    }

    const [uid, part] = key.split(':');

    if ((part === 'destination' || part === 'fields') && !readable[uid!]) {
      return t('Available once the data source can be read.');
    }

    if (part === 'fields' && !mappable[uid!]) {
      return t('Available once the destination is chosen.');
    }

    return null;
  }

  /**
   * Sections can be filled in in any order once they can be used; only one shows at a time
   * so the page doesn't overwhelm.
   */
  function stateOf(key: string): 'open' | 'done' | 'todo' | 'locked' {
    if (key === current.value) {
      return 'open';
    }

    if (waitingFor(key)) {
      return 'locked';
    }

    return completed.has(key) ? 'done' : 'todo';
  }

  /** A section's summary, or what it's waiting for while it can't be opened. */
  function summaryOf(key: string): string | undefined {
    return waitingFor(key) ?? summaries.value[key];
  }

  async function open(key: string): Promise<void> {
    current.value = key;
    await nextTick();

    const reduce = window.matchMedia?.(
      '(prefers-reduced-motion: reduce)'
    ).matches;
    document.querySelector(`[data-wizard-key="${key}"]`)?.scrollIntoView?.({
      block: 'start',
      behavior: reduce ? 'auto' : 'smooth',
    });
  }

  function typeLabel(step: ImportStep): string {
    return (
      props.importerTypes.find((type) => type.value === step.type)?.label ??
      step.type ??
      ''
    );
  }

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

  /**
   * Adds a step, which starts at its data source. A data source can only be read for an
   * importer type, so it starts as the first one, and is changed in its destination.
   */
  function addStep(): void {
    const step = newStep(props.importerTypes[0]?.value ?? '');
    steps.value = [...steps.value, step];
    void open(keyOf(step, 'source'));
  }

  function removeStep(step: ImportStep): void {
    steps.value = steps.value.filter((existing) => existing.uid !== step.uid);

    for (const part of STEP_PARTS) {
      completed.delete(keyOf(step, part));
    }

    if (current.value.startsWith(`${step.uid}:`)) {
      current.value = steps.value.length ? 'review' : 'add';
    }
  }

  function updateStep(next: ImportStep): void {
    steps.value = steps.value.map((step) =>
      step.uid === next.uid ? cloneStep(next) : step
    );
  }

  function settingsOf(step: ImportStep): Record<string, unknown> {
    // PHP sends empty settings as `[]`, whose `map` would be `Array.prototype.map`
    return Array.isArray(step.settings) ? {} : (step.settings ?? {});
  }

  const MATCH_MODES: Record<string, string> = {
    createOnly: t('Only create'),
    createAndUpdate: t('Create and update'),
    updateOnly: t('Only update'),
  };

  /** How many columns a match criteria tree matches on. */
  function countChecked(tree: unknown): number {
    if (tree === '1' || tree === 1 || tree === true) {
      return 1;
    }

    if (!tree || typeof tree !== 'object') {
      return 0;
    }

    return Object.values(tree).reduce<number>(
      (count, value) => count + countChecked(value),
      0
    );
  }

  function countMapped(tree: unknown): number {
    if (typeof tree === 'string') {
      return tree === '' ? 0 : 1;
    }

    if (!tree || typeof tree !== 'object') {
      return 0;
    }

    return Object.values(tree).reduce<number>(
      (count, value) => count + countMapped(value),
      0
    );
  }

  const summaries = computed<Record<string, string>>(() => {
    const out: Record<string, string> = {
      details: [details.name, details.handle].filter(Boolean).join(' · '),
    };

    for (const step of steps.value) {
      out[keyOf(step, 'source')] = step.source ?? '';
      out[keyOf(step, 'destination')] = typeLabel(step);
      out[keyOf(step, 'fields')] = t(
        '{count, plural, =0{Nothing mapped yet} =1{# field mapped} other{# fields mapped}}',
        {count: countMapped(settingsOf(step).map)}
      );
      const matched = countChecked(settingsOf(step).matchCriteria);
      out[keyOf(step, 'matching')] = [
        MATCH_MODES[
          (settingsOf(step).matchMode as string) ?? 'createAndUpdate'
        ] ?? MATCH_MODES.createAndUpdate,
        matched
          ? t(
              '{count, plural, =1{matching on # field} other{matching on # fields}}',
              {count: matched}
            )
          : t('matching on nothing'),
      ].join(' · ');
    }

    return out;
  });

  /** The section after this one, or the wait for a first step. */
  function next(key: string): string {
    const following = order.value[order.value.indexOf(key) + 1];

    return following === 'review' && !steps.value.length ? 'add' : following!;
  }

  function continueFromDetails(): void {
    const values = detailsRenderer.value?.currentValues() ?? {};
    details.name = String(values.name ?? '').trim();
    details.handle = String(values.handle ?? '').trim();
    details.description = String(values.description ?? '');

    detailErrors.value = [
      ...(details.name
        ? []
        : [{path: ['name'], messages: [t('Name cannot be blank.')]}]),
      ...(details.handle
        ? []
        : [{path: ['handle'], messages: [t('Handle cannot be blank.')]}]),
    ];

    if (detailErrors.value.length) {
      return;
    }

    completed.add('details');

    // the plan needs a step, so its first one starts here
    if (!steps.value.length) {
      addStep();

      return;
    }

    void open(next('details'));
  }

  function continueFromSource(step: ImportStep): void {
    if (!readable[step.uid]) {
      stepHints[step.uid] = t('Choose a data source that can be read.');

      return;
    }

    stepHints[step.uid] = null;
    completed.add(keyOf(step, 'source'));
    void open(keyOf(step, 'destination'));
  }

  function continueFromDestination(step: ImportStep): void {
    if (!mappable[step.uid]) {
      stepHints[step.uid] = t('Choose what this step imports into.');

      return;
    }

    stepHints[step.uid] = null;
    completed.add(keyOf(step, 'destination'));
    void open(keyOf(step, 'fields'));
  }

  /** Whether an error is about the step's mapping rather than its settings. */
  function isMappingError(error: UiPayload['errors'][number]): boolean {
    return (
      error.path[0] === 'settings' && MAPPING_TREES.includes(error.path[1]!)
    );
  }

  /** Which of a step's sections an error belongs in. */
  function partFor(error: UiPayload['errors'][number]): StepPart {
    if (
      error.path[0] === 'settings' &&
      ['matchCriteria', 'matchMode'].includes(error.path[1]!)
    ) {
      return 'matching';
    }

    if (isMappingError(error)) {
      return 'fields';
    }

    return ['source', 'batchSize'].includes(error.path[0]!)
      ? 'source'
      : 'destination';
  }

  function errorsFor(step: ImportStep, part: StepPart): UiPayload['errors'] {
    return (stepErrors[step.uid] ?? []).filter(
      (error) => partFor(error) === part
    );
  }

  async function continueFromFields(step: ImportStep): Promise<void> {
    checking.value = true;

    try {
      await validateStep(step);
      stepErrors[step.uid] = [];
      readErrors[step.uid] = await readError(step);

      if (readErrors[step.uid]) {
        return;
      }

      completed.add(keyOf(step, 'fields'));
      void open(keyOf(step, 'matching'));
    } catch (error: any) {
      const responseErrors = error?.response?.data?.errors as
        | Record<string, string[]>
        | undefined;

      if (!responseErrors) {
        throw error;
      }

      stepErrors[step.uid] = Object.entries(responseErrors).map(
        ([path, messages]) => ({path: path.split('.'), messages})
      );
      const first = stepErrors[step.uid]!.map(partFor).find(
        (part) => part !== 'fields'
      );

      if (first) {
        completed.delete(keyOf(step, first));
        void open(keyOf(step, first));
      }
    } finally {
      checking.value = false;
    }
  }

  /** Why a step's rows can't be read with its mapping, found by reviewing them, if they can't. */
  async function readError(step: ImportStep): Promise<string | null> {
    try {
      const {data} = await actionClient.post(reviewSteps().url, {
        uid: null,
        steps: [step],
      });

      return data.steps?.[0]?.preview?.error ?? null;
    } catch {
      // the review is a check on top of validation, so a failed one doesn't hold the step up
      return null;
    }
  }

  /** The errors about a step's mapping that aren't shown against one of its columns. */
  function fieldErrors(step: ImportStep): string[] {
    return [
      ...errorsFor(step, 'fields')
        .filter((error) => error.path[1] !== 'templates')
        .flatMap((error) => error.messages),
      ...(readErrors[step.uid] ? [readErrors[step.uid]!] : []),
    ];
  }

  function continueFromMatching(step: ImportStep): void {
    completed.add(keyOf(step, 'matching'));
    void open(next(keyOf(step, 'matching')));
  }

  /** Spreads the server's errors back over the sections they're about, and opens the first. */
  function showSaveErrors(errors: Record<string, string[]>): void {
    detailErrors.value = [];

    for (const uid of Object.keys(stepErrors)) {
      stepErrors[uid] = [];
    }

    let first: string | null = null;

    for (const [path, messages] of Object.entries(errors)) {
      const [head, uid, ...rest] = path.split('.');

      if (head === 'steps' && uid) {
        const error = {path: rest, messages};
        (stepErrors[uid] ??= []).push(error);
        first ??= `${uid}:${partFor(error)}`;
      } else if (head === 'steps') {
        saveError.value = messages[0] ?? null;
      } else {
        detailErrors.value = [...detailErrors.value, {path: [path], messages}];
        first = 'details';
      }
    }

    if (first) {
      void open(first);
    }
  }

  async function save(run: boolean): Promise<void> {
    saving.value = run ? 'run' : 'save';
    saveError.value = null;

    let plan: {uid: string; handle: string};

    try {
      const {data} = await actionClient.post(props.submit.url, {
        ...details,
        steps: steps.value,
      });
      plan = data.import;
    } catch (error: any) {
      const errors = error?.response?.data?.errors;

      if (errors) {
        showSaveErrors(errors);
      } else {
        saveError.value =
          error?.response?.data?.message ?? t('Couldn’t save import plan.');
      }

      saving.value = null;

      return;
    }

    const editUrl = props.editUrl.replace(
      '__handle__',
      encodeURIComponent(plan.handle)
    );

    if (run && props.runUrl) {
      try {
        await actionClient.post(props.runUrl, {uid: plan.uid});
      } catch {
        // the plan's saved either way, so its page is where to try again
        router.visit(editUrl, {
          onSuccess: () =>
            Craft.cp?.displayError?.(
              t('The import plan was saved, but couldn’t be started.')
            ),
        });

        return;
      }

      router.visit(props.indexUrl, {
        onSuccess: () => Craft.cp?.displayNotice?.(t('Import started.')),
      });

      return;
    }

    router.visit(editUrl, {
      onSuccess: () => Craft.cp?.displayNotice?.(t('Import plan saved.')),
    });
  }
</script>

<template>
  <CpContainer>
    <div class="import-wizard">
      <ImportWizardSection
        :title="t('Name the plan')"
        :description="t('What the plan’s called, and how code refers to it.')"
        :summary="summaries.details"
        :state="stateOf('details')"
        keep-alive
        data-wizard-key="details"
        @open="open('details')"
      >
        <craft-field-group>
          <UiRenderer
            ref="detailsRenderer"
            :payload="ui"
            :errors="detailErrors"
          />
        </craft-field-group>
        <template #footer>
          <craft-button
            type="button"
            variant="primary"
            @click="continueFromDetails"
          >
            {{ t('Continue') }}
          </craft-button>
        </template>
      </ImportWizardSection>

      <div class="import-wizard__steps">
        <p class="import-wizard__group m-0">{{ t('Steps') }}</p>
        <section
          v-for="(step, index) in steps"
          :key="step.uid"
          class="import-wizard__step"
          :aria-labelledby="`step-label-${step.uid}`"
        >
          <div class="import-wizard__step-label">
            <span :id="`step-label-${step.uid}`">{{
              t('Step {number} · {type}', {
                number: index + 1,
                type: typeLabel(step),
              })
            }}</span>
            <craft-button
              type="button"
              variant="plain"
              size="small"
              @click="removeStep(step)"
            >
              {{ t('Remove') }}
            </craft-button>
          </div>

          <ImportWizardSection
            :title="t('Data source')"
            :description="
              t(
                'Where the data comes from: a file in the project, or a URL. We’ll read it and show you what we found.'
              )
            "
            :summary="summaryOf(keyOf(step, 'source'))"
            :state="stateOf(keyOf(step, 'source'))"
            :data-wizard-key="keyOf(step, 'source')"
            @open="open(keyOf(step, 'source'))"
          >
            <StepEditor
              :step="step"
              editable
              part="source"
              :errors="errorsFor(step, 'source')"
              @update:step="updateStep"
            />
            <SourcePreview
              :step="step"
              @update:readable="readable[step.uid] = $event"
            />
            <StepEditor
              :step="step"
              editable
              part="advanced"
              :errors="errorsFor(step, 'source')"
              @update:step="updateStep"
            />
            <p
              v-if="stepHints[step.uid] && current === keyOf(step, 'source')"
              class="error m-0"
              role="alert"
            >
              {{ stepHints[step.uid] }}
            </p>
            <template #footer>
              <craft-button
                type="button"
                variant="primary"
                @click="continueFromSource(step)"
              >
                {{ t('Continue') }}
              </craft-button>
            </template>
          </ImportWizardSection>

          <ImportWizardSection
            :title="t('Destination')"
            :description="t('Choose what each row becomes.')"
            :summary="summaryOf(keyOf(step, 'destination'))"
            :state="stateOf(keyOf(step, 'destination'))"
            :data-wizard-key="keyOf(step, 'destination')"
            @open="open(keyOf(step, 'destination'))"
          >
            <StepEditor
              :step="step"
              editable
              part="settings"
              :errors="errorsFor(step, 'destination')"
              @update:step="updateStep"
              @update:can-map="mappable[step.uid] = $event"
            />
            <p
              v-if="
                stepHints[step.uid] && current === keyOf(step, 'destination')
              "
              class="error m-0"
              role="alert"
            >
              {{ stepHints[step.uid] }}
            </p>
            <template #footer>
              <craft-button
                type="button"
                variant="primary"
                @click="continueFromDestination(step)"
              >
                {{ t('Continue') }}
              </craft-button>
            </template>
          </ImportWizardSection>

          <ImportWizardSection
            :title="t('Fields')"
            :description="
              t(
                'We’ve matched what we could. Check the rest, and choose what to match existing content on.'
              )
            "
            :summary="summaryOf(keyOf(step, 'fields'))"
            :state="stateOf(keyOf(step, 'fields'))"
            :data-wizard-key="keyOf(step, 'fields')"
            @open="open(keyOf(step, 'fields'))"
          >
            <StepEditor
              :step="step"
              editable
              part="mapping"
              :errors="errorsFor(step, 'fields')"
              @update:step="updateStep"
            />
            <ul
              v-if="fieldErrors(step).length"
              class="error-list m-0"
              role="alert"
            >
              <li v-for="message in fieldErrors(step)" :key="message">
                {{ message }}
              </li>
            </ul>
            <template #footer>
              <craft-button
                type="button"
                variant="primary"
                :loading="checking || undefined"
                @click="continueFromFields(step)"
              >
                {{ t('Continue') }}
              </craft-button>
            </template>
          </ImportWizardSection>

          <ImportWizardSection
            :title="t('Matching')"
            :description="
              t('What happens to rows that match content you already have.')
            "
            :summary="summaryOf(keyOf(step, 'matching'))"
            :state="stateOf(keyOf(step, 'matching'))"
            :data-wizard-key="keyOf(step, 'matching')"
            @open="open(keyOf(step, 'matching'))"
          >
            <StepEditor
              :step="step"
              editable
              part="matching"
              :errors="errorsFor(step, 'matching')"
              @update:step="updateStep"
            />
            <ul
              v-if="errorsFor(step, 'matching').length"
              class="error-list m-0"
              role="alert"
            >
              <li
                v-for="message in errorsFor(step, 'matching').flatMap(
                  (error) => error.messages
                )"
                :key="message"
              >
                {{ message }}
              </li>
            </ul>
            <template #footer>
              <craft-button
                type="button"
                variant="primary"
                @click="continueFromMatching(step)"
              >
                {{ t('Continue') }}
              </craft-button>
            </template>
          </ImportWizardSection>
        </section>

        <div class="import-wizard__add" data-wizard-key="add">
          <craft-button
            type="button"
            variant="dashed"
            icon="plus"
            @click="addStep"
          >
            {{ t('Add step') }}
          </craft-button>
          <span v-if="steps.length" class="import-wizard__hint">
            {{
              t(
                'For example, import authors before the entries that reference them.'
              )
            }}
          </span>
        </div>
      </div>

      <p class="import-wizard__group m-0">{{ t('Finish') }}</p>
      <ImportWizardSection
        :title="t('Review and run')"
        :description="
          t(
            'What running the plan would do with the first rows of each step’s data. Nothing’s imported until you run it.'
          )
        "
        :summary="summaryOf('review')"
        :state="stateOf('review')"
        data-wizard-key="review"
        @open="open('review')"
      >
        <ImportPlanReview :steps="steps" :plan-uid="null" />
        <p v-if="saveError" class="error m-0" role="alert">{{ saveError }}</p>
        <template #footer>
          <craft-button
            type="button"
            variant="primary"
            :loading="saving === 'save' || undefined"
            .disabled="saving !== null || !steps.length"
            @click="save(false)"
          >
            {{ t('Create import plan') }}
          </craft-button>
          <craft-button
            v-if="runUrl"
            type="button"
            :loading="saving === 'run' || undefined"
            .disabled="saving !== null || !steps.length"
            @click="save(true)"
          >
            {{ t('Create and run') }}
          </craft-button>
        </template>
      </ImportWizardSection>
    </div>
  </CpContainer>
</template>

<style scoped>
  .import-wizard {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    max-inline-size: 72rem;
    padding-block: var(--c-spacing-lg);
  }

  .import-wizard__group {
    padding-block-start: var(--c-spacing-md);
    color: var(--c-text-quiet);
    font-size: 0.8em;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }

  .import-wizard__hint {
    color: var(--c-text-quiet);
  }

  .import-wizard__steps {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .import-wizard__step {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    padding: var(--c-spacing-md);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-lg, var(--c-radius-md));
  }

  .import-wizard__step-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--c-spacing-sm);
    font-weight: 600;
  }

  .import-wizard__add {
    padding-block-start: var(--c-spacing-xs);
  }

  .import-wizard__add {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm) var(--c-spacing-md);
  }
</style>

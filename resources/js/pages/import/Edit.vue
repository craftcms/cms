<script setup lang="ts">
  /**
   * An import plan: its name and handle, the ordered list of steps that make it up, and a
   * review of what running it would do.
   *
   * Each step is a card that edits a draft of the step. Saving the card saves just that step
   * into the plan; cancelling drops the draft.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/icon/icon';
  import {computed, reactive, ref, watch} from 'vue';
  import {router} from '@inertiajs/vue3';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {actionClient, t} from '@craftcms/ui';
  import {storeStep} from '@actions/Import/ImportPlansController';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import type {FormAction} from '@/common/types';
  import UiPage from '@/pages/Ui.vue';
  import type {UiPayload, UiValue} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {cloneSteps, dirtyState} from '@/modules/import/mapping/paths';
  import ImportStepComponent from '@/modules/import/ImportStep.vue';
  import ImportPlanReview from '@/modules/import/ImportPlanReview.vue';
  import StepEditor from '@/modules/import/steps/StepEditor.vue';

  // the Run import button renders beside the page, so the page takes the attributes itself
  defineOptions({
    inheritAttrs: false,
  });

  type ViewModel = CraftCms.Cms.Http.ViewModels.ImportPlanEditViewModel;

  const props = defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    importerTypes: ViewModel['importerTypes'];
    stepPageUrls: Record<string, string>;
    run: ViewModel['run'];
    formActions?: FormAction[];
  }>();

  function asSteps(value: UiValue | undefined): ImportStep[] {
    return Array.isArray(value) ? (value as unknown as ImportStep[]) : [];
  }

  const planUid = computed(() => (props.ui.values.uid as string) || null);

  /** The steps as they stand in the form, which Run import checks against the saved ones. */
  const currentSteps = ref(asSteps(props.ui.values.steps));
  /** The steps as the server last saved them; a step's card can save itself. */
  const savedSteps = ref(asSteps(props.ui.values.steps));

  watch(
    () => props.ui,
    (ui) => {
      currentSteps.value = asSteps(ui.values.steps);
      savedSteps.value = asSteps(ui.values.steps);
    }
  );

  /** Each card's unsaved changes to its step, by uid. */
  const drafts = reactive<Record<string, ImportStep>>({});
  /** Bumped to rebuild a card from its step, dropping whatever its form held. */
  const revisions = reactive<Record<string, number>>({});
  const saving = reactive(new Set<string>());
  const saveErrors = reactive<Record<string, string[]>>({});

  // running uses the saved plan, so unsaved steps would be left out
  const runDisabled = computed(
    () =>
      !currentSteps.value.some((step) => step.enabled) ||
      Object.keys(drafts).length > 0 ||
      dirtyState(currentSteps.value) !== dirtyState(savedSteps.value)
  );

  function setSteps(
    steps: ImportStep[],
    setValue: (value: UiValue, kind: 'discrete') => void
  ): void {
    currentSteps.value = cloneSteps(steps);
    setValue(cloneSteps(steps) as unknown as UiValue, 'discrete');
  }

  /**
   * Keys the errors about individual steps (`steps.<uid>.*`) the way the list expects.
   * An error about the list as a whole is left to the field.
   */
  function stepErrors(errors: UiPayload['errors']): Record<string, string[]> {
    return Object.fromEntries(
      errors
        .filter((error) => error.path[0] === 'steps' && error.path.length > 1)
        .map((error) => [error.path.join('.'), error.messages])
    );
  }

  /** The step as its card currently shows it. */
  function draftOf(step: ImportStep): ImportStep {
    return drafts[step.uid] ?? step;
  }

  function messagesOf(error: unknown): string[] {
    const data = (error as {response?: {data?: any}}).response?.data;
    const errors = data?.errors as
      | Record<string, string[] | string>
      | undefined;

    if (errors && Object.keys(errors).length) {
      return Object.values(errors).flat();
    }

    return [data?.message ?? t('Couldn’t save import step.')];
  }

  /** Saves the card's draft as the step, into the saved plan when there is one. */
  async function saveStep(
    step: ImportStep,
    value: UiValue | undefined,
    setValue: (value: UiValue, kind: 'discrete') => void
  ): Promise<void> {
    const next = cloneSteps([draftOf(step)])[0]!;
    delete saveErrors[step.uid];

    if (planUid.value) {
      saving.add(step.uid);

      try {
        await actionClient.post(storeStep().url, {
          planUid: planUid.value,
          step: next,
        });
      } catch (error) {
        saveErrors[step.uid] = messagesOf(error);

        return;
      } finally {
        saving.delete(step.uid);
      }

      savedSteps.value = savedSteps.value.some(
        (saved) => saved.uid === next.uid
      )
        ? savedSteps.value.map((saved) =>
            saved.uid === next.uid ? next : saved
          )
        : [...savedSteps.value, next];
    }

    delete drafts[step.uid];
    setSteps(
      asSteps(value).map((other) => (other.uid === next.uid ? next : other)),
      setValue
    );
  }

  /** Drops the card's draft, putting its form back to the step as it was. */
  function cancelStep(step: ImportStep): void {
    delete drafts[step.uid];
    delete saveErrors[step.uid];
    revisions[step.uid] = (revisions[step.uid] ?? 0) + 1;
  }

  function addStep(
    value: UiValue | undefined,
    setValue: (value: UiValue, kind: 'discrete') => void
  ): void {
    setSteps(
      [
        ...asSteps(value),
        {
          // a plain UUID: the server caps a step's uid at 36 characters
          uid: crypto.randomUUID(),
          type: null,
          source: null,
          transformer: null,
          batchSize: null,
          enabled: true,
          settings: {},
        },
      ],
      setValue
    );
  }

  function deleteStep(
    step: ImportStep,
    value: UiValue | undefined,
    setValue: (value: UiValue, kind: 'discrete') => void
  ): void {
    if (!window.confirm(t('Are you sure you want to delete this step?'))) {
      return;
    }

    delete drafts[step.uid];
    delete saveErrors[step.uid];
    setSteps(
      asSteps(value).filter((other) => other.uid !== step.uid),
      setValue
    );
  }

  /** The step's importer type, once one's been chosen. */
  function typeName(step: ImportStep): string {
    return (
      props.importerTypes.find((type) => type.value === step.type)?.label ?? ''
    );
  }

  function runImport(): void {
    const run = props.run;

    if (
      !run ||
      !window.confirm(t('Are you sure you want to run this import plan?'))
    ) {
      return;
    }

    router.post(run.url, {uid: run.uid, redirect: run.redirect});
  }

  function typeLabel(step: ImportStep): string {
    return (
      props.importerTypes.find((type) => type.value === step.type)?.label ??
      t('Choose an importer')
    );
  }
</script>

<template>
  <LayoutSlot v-if="run" name="footer-meta">
    <craft-button
      type="button"
      .disabled="runDisabled"
      :title="
        runDisabled && currentSteps.length
          ? t('Save the import plan to run it.')
          : undefined
      "
      @click="runImport"
    >
      {{ t('Run import') }}
    </craft-button>
  </LayoutSlot>

  <UiPage v-bind="$attrs" :ui="ui" :submit="submit" :form-actions="formActions">
    <template #steps="{value, setValue, editable, errors}">
      <div class="grid gap-md">
        <StepEditor
          v-for="(step, index) in asSteps(value)"
          :key="`${step.uid}-${revisions[step.uid] ?? 0}`"
          :step="step"
          :editable="editable"
          :errors="[]"
          :heading-label="t('Step {number}', {number: index + 1})"
          :heading-number="index + 1"
          with-matching
          @update:step="drafts[step.uid] = $event"
        >
          <template #heading>
            <div class="import-step__heading">
              <div class="import-step__titles">
                <h3 class="import-step__type m-0">
                  <span class="sr-only">{{
                    t('Step {number}', {number: index + 1})
                  }}</span>
                  {{ typeName(draftOf(step)) }}
                </h3>
                <p class="import-step__source m-0">
                  {{ draftOf(step).source ?? '' }}
                </p>
              </div>
            </div>
          </template>
          <template v-if="editable" #heading-actions>
            <craft-button
              type="button"
              variant="plain"
              size="small"
              icon
              @click="deleteStep(step, value, setValue)"
            >
              <craft-icon
                name="trash"
                :label="
                  typeName(step)
                    ? t('Delete {name}', {name: typeName(step)})
                    : t('Delete step')
                "
              ></craft-icon>
            </craft-button>
          </template>
          <template v-if="editable" #footer>
            <craft-button
              type="button"
              variant="primary"
              .loading="saving.has(step.uid)"
              @click="saveStep(step, value, setValue)"
            >
              {{ t('Save') }}
            </craft-button>
            <craft-button
              type="button"
              .disabled="saving.has(step.uid)"
              @click="cancelStep(step)"
            >
              {{ t('Cancel') }}
            </craft-button>
            <craft-button
              class="import-step__delete"
              type="button"
              variant="plain"
              icon="trash"
              @click="deleteStep(step, value, setValue)"
            >
              {{ t('Delete') }}
            </craft-button>
            <ul
              v-if="saveErrors[step.uid]?.length"
              class="import-step__errors error-list m-0"
              role="alert"
            >
              <li v-for="message in saveErrors[step.uid]" :key="message">
                {{ message }}
              </li>
            </ul>
          </template>
        </StepEditor>

        <craft-button
          v-if="editable"
          type="button"
          variant="dashed"
          icon="plus"
          @click="addStep(value, setValue)"
          >{{ t('Add Step') }}</craft-button
        >
      </div>
      <!--      <StepTabs-->
      <!--        :model-value="asSteps(value)"-->
      <!--        :editable="editable"-->
      <!--        :importer-types="importerTypes"-->
      <!--        :errors="stepErrors(errors)"-->
      <!--        @update:model-value="-->
      <!--          setSteps($event, (next, kind) => setValue(next, kind))-->
      <!--        "-->
      <!--      />-->
    </template>
    <template #review>
      <ImportPlanReview :steps="currentSteps" :plan-uid="planUid" />
    </template>
  </UiPage>
</template>

<style scoped>
  .import-step__delete {
    margin-inline-start: auto;
  }

  .import-step__errors {
    flex-basis: 100%;
  }

  .import-step__heading {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    min-inline-size: 0;
  }

  .import-step__titles {
    display: grid;
    min-inline-size: 0;
  }

  /* holds its line before there's a value, so the heading doesn't jump */
  .import-step__type,
  .import-step__source {
    min-block-size: 1lh;
  }

  .import-step__type {
    font-size: var(--c-text-base);
  }

  .import-step__source {
    color: var(--c-text-quiet);
    font-family: var(--c-font-mono, monospace);
    overflow-wrap: anywhere;
  }
</style>

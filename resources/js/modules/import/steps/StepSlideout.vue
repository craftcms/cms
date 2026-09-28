<script setup lang="ts">
  /**
   * One import step's settings, as a slideout panel.
   *
   * Opened with `openSlideoutWith()` — see `step-slideout.ts` for why. The panel edits
   * a copy of the step and hands it back on Apply; the step only reaches the database
   * when the import itself is saved.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/spinner/spinner';
  import {computed, ref, shallowRef} from 'vue';
  import {useForm} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useDelayedLoading} from '@/common/composables/useDelayedLoading';
  import {useSlideout} from '@/common/slideouts';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {FormPayload} from '@/modules/forms/types';
  import type {
    MappingValues,
    StepPayload,
  } from '@/modules/import/mapping/types';
  import {
    fetchStepForm,
    takeStepSlideoutContext,
    validateStep,
  } from './step-slideout';
  import {openStepMapping} from './step-mapping';

  const props = defineProps<{
    contextId: string;
    title: string;
  }>();

  const context = takeStepSlideoutContext(props.contextId);
  const slideout = useSlideout();
  const payload = shallowRef<FormPayload>(context.payload);
  const errors = shallowRef<FormPayload['errors']>([]);
  const renderer = ref<{
    currentValues(): FormPayload['values'];
  } | null>(null);

  /** The step as it currently stands, including mapping the form doesn't render. */
  const step = ref<StepPayload>(context.step);
  const mappingButton = ref<HTMLElement | null>(null);
  const mappingMessage = ref<string | null>(null);
  const openingMapping = ref(false);

  /**
   * Backs the shell's Apply button and gives it an accurate dirty check for the
   * unsaved-changes prompt.
   */
  const form = useForm({state: JSON.stringify(context.step)});

  useAppLayout(() => ({
    title: props.title,
    submitButtonLabel: t('Apply'),
    form,
    onSave: done,
  }));

  /**
   * Whether the step can be mapped, as the server last reported it. An element importer
   * has no destination columns until its field layout resolves from what it imports into,
   * so this tracks every refresh rather than being derived from the step here.
   */
  const canMap = ref(context.canMap);

  /**
   * The importer type the rendered form was built for. The form's own type field changes
   * first, and the fields that depend on it only arrive with the refresh, so the Mapping
   * section follows this rather than the step's type to appear alongside them.
   */
  const formType = ref(context.step.type);
  const loadingType = ref(false);
  const showLoadingType = useDelayedLoading(loadingType);
  let latestRefresh = 0;

  /**
   * The data file `canMap` was last checked against. The file doesn't change the form, so
   * it isn't reactive; leaving its field checks it instead, rather than on every keystroke.
   */
  let checkedFile = context.step.file;
  const checkingMap = ref(false);
  let latestCheck = 0;
  /** Shared by refreshes and file checks, so whichever started last sets `canMap`. */
  let latestCanMap = 0;

  const mappedCount = computed(() => countLeaves(mappingValues().map));

  function mappingValues(): MappingValues {
    const settings = step.value.settings ?? {};

    return {
      map: (settings.map ?? {}) as Record<string, unknown>,
      matchCriteria: (settings.matchCriteria ?? {}) as Record<string, unknown>,
      clearableItems: (settings.clearableItems ?? {}) as Record<
        string,
        unknown
      >,
      keepMissingNestedElements: (settings.keepMissingNestedElements ??
        {}) as Record<string, unknown>,
    };
  }

  function countLeaves(tree: unknown): number {
    if (tree === null || tree === undefined || tree === '') {
      return 0;
    }

    if (typeof tree !== 'object') {
      return 1;
    }

    return Object.values(tree as Record<string, unknown>).reduce<number>(
      (total, value) => total + countLeaves(value),
      0
    );
  }

  /** Folds the form's current values back into the step. */
  function syncFromForm(): void {
    const values = renderer.value?.currentValues() ?? {};
    const settings = (values.settings ?? {}) as Record<string, unknown>;
    const batchSize = values.batchSize;

    step.value = {
      ...step.value,
      type: (values.type as string) || null,
      file: (values.file as string) || null,
      transformer: (values.transformer as string) || null,
      batchSize:
        batchSize === null || batchSize === undefined || batchSize === ''
          ? null
          : Number(batchSize),
      // the mapping trees live on the step, not in the form, so they're carried over
      settings: {...settings, ...mappingValues()},
    };

    form.state = JSON.stringify(step.value);
  }

  function onChange(): void {
    syncFromForm();
  }

  async function editMapping(): Promise<void> {
    if (openingMapping.value) {
      return;
    }

    syncFromForm();
    mappingMessage.value = null;
    openingMapping.value = true;

    let opened: boolean;

    try {
      opened = await openStepMapping(
        {
          step: step.value,
          urls: context.urls,
          editable: context.editable,
          opener: mappingButton.value,
          apply: (applied) => {
            step.value = {
              ...step.value,
              settings: {...step.value.settings, ...applied},
            };
            form.state = JSON.stringify(step.value);
          },
        },
        t('Edit mapping')
      );
    } catch (error) {
      mappingMessage.value =
        error instanceof Error && error.message
          ? error.message
          : t('Couldn’t open the mapping.');

      return;
    } finally {
      openingMapping.value = false;
    }

    if (!opened) {
      mappingMessage.value = t(
        'Choose what this step imports into, and a data file, before mapping.'
      );
    }
  }

  /** The step as the settings form endpoint expects it, with the mapping trees carried over. */
  function stepForRequest(settings: Record<string, unknown>): StepPayload {
    return {...step.value, settings: {...settings, ...mappingValues()}};
  }

  async function refresh(values: FormPayload['values']): Promise<FormPayload> {
    syncFromForm();

    const request = ++latestRefresh;
    const canMapRequest = ++latestCanMap;
    const type = step.value.type;
    checkedFile = step.value.file;

    if (type !== formType.value) {
      loadingType.value = true;
    }

    try {
      const response = await fetchStepForm(
        context.urls.settingsUrl,
        stepForRequest((values.settings ?? {}) as Record<string, unknown>)
      );

      // the form drops a response a newer refresh has overtaken, so this does too
      if (request === latestRefresh) {
        formType.value = type;
      }

      if (canMapRequest === latestCanMap) {
        canMap.value = response.canMap;
      }

      return response.form;
    } finally {
      if (request === latestRefresh) {
        loadingType.value = false;
      }
    }
  }

  function onFocusOut(event: FocusEvent): void {
    const control =
      event.target instanceof Element
        ? event.target.closest('[data-form-control-path]')
        : null;

    if (
      control?.getAttribute('data-form-control-path') !== '["file"]' ||
      (event.relatedTarget instanceof Node &&
        control.contains(event.relatedTarget))
    ) {
      return;
    }

    syncFromForm();

    if (step.value.file === checkedFile) {
      return;
    }

    checkedFile = step.value.file;
    void checkCanMap();
  }

  /** Asks the server whether the step can be mapped, without rebuilding the form. */
  async function checkCanMap(): Promise<void> {
    const request = ++latestCanMap;
    const check = ++latestCheck;
    checkingMap.value = true;

    try {
      const response = await fetchStepForm(
        context.urls.settingsUrl,
        stepForRequest(step.value.settings ?? {})
      );

      if (request === latestCanMap) {
        canMap.value = response.canMap;
      }
    } catch {
      // the last reported `canMap` still stands
    } finally {
      if (check === latestCheck) {
        checkingMap.value = false;
      }
    }
  }

  function setErrors(next: Record<string, string | string[]>): void {
    errors.value = Object.entries(next).map(([path, messages]) => ({
      path: path.split('.'),
      messages: Array.isArray(messages) ? messages : [messages],
    }));
  }

  async function done(): Promise<void> {
    syncFromForm();
    errors.value = [];

    if (context.urls.validateUrl) {
      try {
        await validateStep(context.urls.validateUrl, step.value);
      } catch (error: any) {
        const responseErrors = error?.response?.data?.errors;

        if (responseErrors) {
          setErrors(responseErrors);
        }

        return;
      }
    }

    context.apply(JSON.parse(JSON.stringify(step.value)) as StepPayload);

    // Before close(): closing drops the panel from the store, and its handler with it.
    slideout?.saved();
    slideout?.close({force: true});
  }
</script>

<template>
  <div class="grid gap-6" @focusout="onFocusOut">
    <FormRenderer
      ref="renderer"
      :payload="payload"
      :errors="errors"
      :refresh="payload.refreshable ? refresh : undefined"
      @change="onChange"
    />

    <craft-spinner v-if="showLoadingType" role="status">
      {{ t('Loading') }}
    </craft-spinner>

    <section v-if="formType">
      <h3>{{ t('Mapping') }}</h3>

      <p>
        {{
          mappedCount === 0
            ? t('Nothing mapped yet.')
            : t('{count} columns mapped.', {count: mappedCount})
        }}
      </p>

      <craft-button
        ref="mappingButton"
        .disabled="!context.editable || !canMap"
        :loading="openingMapping || checkingMap"
        @click="editMapping"
      >
        {{ t('Edit mapping') }}
      </craft-button>

      <p v-if="mappingMessage" role="alert">{{ mappingMessage }}</p>
    </section>
  </div>
</template>

<script setup lang="ts">
  /**
   * One import step's settings, as a slideout panel.
   *
   * Opened with `openSlideoutWith()` — see `step-slideout.ts` for why. The panel edits
   * a copy of the step and hands it back on Done; the step only reaches the database
   * when the import itself is saved.
   */
  import '@craftcms/ui/components/button/button';
  import {computed, ref, shallowRef} from 'vue';
  import {useForm} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSlideout} from '@/common/slideouts';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {FormPayload} from '@/modules/forms/types';
  import type {
    MappingValues,
    StepPayload,
  } from '@/modules/import/mapping/types';
  import {fetchStepForm, takeStepSlideoutContext} from './step-slideout';
  import {openStepMapping} from './step-mapping';

  const props = defineProps<{
    contextId: string;
    title: string;
  }>();

  const context = takeStepSlideoutContext(props.contextId);
  const slideout = useSlideout();
  const payload = shallowRef<FormPayload>(context.payload);
  const renderer = ref<{
    currentValues(): FormPayload['values'];
  } | null>(null);

  /** The step as it currently stands, including mapping the form doesn't render. */
  const step = ref<StepPayload>(context.step);
  const mappingButton = ref<HTMLElement | null>(null);
  const mappingMessage = ref<string | null>(null);

  /**
   * Backs the shell's Done button and gives it an accurate dirty check for the
   * unsaved-changes prompt.
   */
  const form = useForm({state: JSON.stringify(context.step)});

  useAppLayout(() => ({
    title: props.title,
    submitButtonLabel: t('Done'),
    form,
    onSave: done,
  }));

  /**
   * Whether the step can be mapped, as the server last reported it. An element importer
   * has no destination columns until its field layout resolves from what it imports into,
   * so this tracks every refresh rather than being derived from the step here.
   */
  const canMap = ref(context.canMap);

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
    syncFromForm();
    mappingMessage.value = null;

    const opened = await openStepMapping(
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

    if (!opened) {
      mappingMessage.value = t(
        'Choose what this step imports into, and a data file, before mapping.'
      );
    }
  }

  async function refresh(values: FormPayload['values']): Promise<FormPayload> {
    syncFromForm();

    const response = await fetchStepForm(context.urls.settingsUrl, {
      ...step.value,
      settings: {
        ...((values.settings ?? {}) as Record<string, unknown>),
        ...mappingValues(),
      },
    });

    canMap.value = response.canMap;

    return response.form;
  }

  function done(): void {
    syncFromForm();
    context.apply(JSON.parse(JSON.stringify(step.value)) as StepPayload);

    // Before close(): closing drops the panel from the store, and its handler with it.
    slideout?.saved();
    slideout?.close({force: true});
  }
</script>

<template>
  <div class="grid gap-6">
    <FormRenderer
      ref="renderer"
      :payload="payload"
      :refresh="payload.refreshable ? refresh : undefined"
      @change="onChange"
    />

    <section v-if="canMap">
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
        .disabled="!context.editable"
        @click="editMapping"
      >
        {{ t('Edit mapping') }}
      </craft-button>

      <p v-if="mappingMessage">{{ mappingMessage }}</p>
    </section>
  </div>
</template>

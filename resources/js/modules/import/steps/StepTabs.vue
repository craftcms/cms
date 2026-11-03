<script setup lang="ts">
  /**
   * An import plan's steps on its edit page, a tab each. A step's settings fold away above
   * its mapping, with its matching below.
   *
   * Everything happens against the steps it's given, and reaches the server when the plan's
   * own form is saved.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/empty/empty';
  import {computed, reactive, ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import type {UiPayload} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import StepEditor from './StepEditor.vue';
  import SourcePreview from './SourcePreview.vue';

  const props = defineProps<{
    editable: boolean;
    importerTypes: Array<{value: string; label: string}>;
    /** Server-side errors, keyed `steps.<step uid>.<attribute>`. */
    errors?: Record<string, string[]>;
  }>();

  const steps = defineModel<ImportStep[]>({required: true});

  const activeUid = ref<string | null>(steps.value[0]?.uid ?? null);
  /** The steps whose settings are showing; a new step starts with them out. */
  const settingsOpen = reactive(new Set<string>());

  const active = computed(
    () =>
      steps.value.find((step) => step.uid === activeUid.value) ??
      steps.value[0] ??
      null
  );

  // a saved plan's steps can be replaced wholesale, which leaves the tab on one that's gone
  watch(steps, (next) => {
    if (!next.some((step) => step.uid === activeUid.value)) {
      activeUid.value = next[0]?.uid ?? null;
    }
  });

  /** The server's errors, regrouped by step and keyed by the path within it. */
  const errorsByStep = computed(() => {
    const byStep: Record<string, UiPayload['errors']> = {};

    for (const [path, messages] of Object.entries(props.errors ?? {})) {
      const [head, uid, ...rest] = path.split('.');

      if (head === 'steps' && uid && rest.length) {
        (byStep[uid] ??= []).push({path: rest, messages});
      }
    }

    return byStep;
  });

  function typeLabel(step: ImportStep): string {
    return (
      props.importerTypes.find((type) => type.value === step.type)?.label ??
      t('Choose an importer')
    );
  }

  function updateStep(next: ImportStep): void {
    steps.value = steps.value.map((step) =>
      step.uid === next.uid ? next : step
    );
  }

  /** Adds a step, as the first importer type, with its settings out to choose the rest. */
  function addStep(): void {
    const step: ImportStep = {
      // a plain UUID: the server caps a step's uid at 36 characters
      uid: crypto.randomUUID(),
      type: props.importerTypes[0]?.value ?? null,
      source: null,
      transformer: null,
      batchSize: null,
      enabled: true,
      settings: {},
    };

    steps.value = [...steps.value, step];
    activeUid.value = step.uid;
    settingsOpen.add(step.uid);
  }

  function move(step: ImportStep, by: number): void {
    const from = steps.value.indexOf(step);
    const to = from + by;

    if (to < 0 || to >= steps.value.length) {
      return;
    }

    const next = [...steps.value];
    next.splice(to, 0, next.splice(from, 1)[0]!);
    steps.value = next;
  }

  function remove(step: ImportStep): void {
    if (
      !window.confirm(t('Are you sure you want to delete this import step?'))
    ) {
      return;
    }

    steps.value = steps.value.filter((existing) => existing.uid !== step.uid);
  }

  const enabledCount = computed(
    () => steps.value.filter((step) => step.enabled).length
  );

  function actionsFor(step: ImportStep): ActionItems {
    const index = steps.value.indexOf(step);

    return [
      {
        label: step.enabled ? t('Disable step') : t('Enable step'),
        icon: step.enabled ? 'toggle-off' : 'toggle-on',
        // a plan runs at least one step
        disabled: step.enabled && enabledCount.value < 2,
        onClick: () => updateStep({...step, enabled: !step.enabled}),
      },
      {
        label: t('Move earlier'),
        icon: 'arrow-left',
        disabled: index === 0,
        onClick: () => move(step, -1),
      },
      {
        label: t('Move later'),
        icon: 'arrow-right',
        disabled: index === steps.value.length - 1,
        onClick: () => move(step, 1),
      },
      {
        label: t('Delete step'),
        icon: 'trash',
        variant: 'danger',
        onClick: () => remove(step),
      },
    ];
  }

  function toggleSettings(step: ImportStep): void {
    if (settingsOpen.has(step.uid)) {
      settingsOpen.delete(step.uid);
    } else {
      settingsOpen.add(step.uid);
    }
  }
</script>

<template>
  <div class="step-tabs">
    <div class="step-tabs__tabs" role="tablist" :aria-label="t('Steps')">
      <button
        v-for="(step, index) in steps"
        :key="step.uid"
        :id="`step-tab-${step.uid}`"
        type="button"
        role="tab"
        class="step-tabs__tab"
        :aria-selected="active?.uid === step.uid"
        :aria-controls="`step-panel-${step.uid}`"
        @click="activeUid = step.uid"
      >
        <span>{{ index + 1 }}. {{ typeLabel(step) }}</span>
        <craft-badge v-if="!step.enabled" fill="gray" size="small">{{
          t('Disabled')
        }}</craft-badge>
        <craft-badge v-if="errorsByStep[step.uid]" fill="red" size="small">{{
          t('Has errors')
        }}</craft-badge>
      </button>
      <craft-button
        v-if="editable"
        class="step-tabs__add"
        type="button"
        variant="plain"
        size="small"
        icon="plus"
        @click="addStep"
      >
        {{ t('Add step') }}
      </craft-button>
    </div>

    <craft-empty
      v-if="!active"
      appearance="dashed"
      :label="t('No steps yet.')"
    ></craft-empty>

    <section
      v-else
      :id="`step-panel-${active.uid}`"
      :key="active.uid"
      class="step-tabs__panel"
      role="tabpanel"
      :aria-labelledby="`step-tab-${active.uid}`"
    >
      <div class="step-tabs__strip">
        <div class="step-tabs__summary">
          <strong>{{ typeLabel(active) }}</strong>
          <code v-if="active.source" class="step-tabs__source">{{
            active.source
          }}</code>
          <span v-else class="step-tabs__quiet">{{
            t('No data source yet')
          }}</span>
        </div>
        <craft-button
          type="button"
          size="small"
          :aria-expanded="settingsOpen.has(active.uid)"
          @click="toggleSettings(active)"
        >
          {{
            settingsOpen.has(active.uid)
              ? t('Hide step settings')
              : t('Step settings')
          }}
        </craft-button>
        <ActionMenu
          v-if="editable"
          :actions="actionsFor(active)"
          :label="t('Step actions')"
          :flush="false"
        />
      </div>

      <div v-if="settingsOpen.has(active.uid)" class="step-tabs__settings">
        <StepEditor
          :step="active"
          :editable="editable"
          part="source"
          :errors="errorsByStep[active.uid] ?? []"
          @update:step="updateStep"
        />
        <SourcePreview :step="active" />
        <StepEditor
          :step="active"
          :editable="editable"
          part="advanced"
          :errors="errorsByStep[active.uid] ?? []"
          @update:step="updateStep"
        />
        <StepEditor
          :step="active"
          :editable="editable"
          part="settings"
          :errors="errorsByStep[active.uid] ?? []"
          @update:step="updateStep"
        />
      </div>

      <StepEditor
        :step="active"
        :editable="editable"
        part="mapping"
        searchable
        :errors="errorsByStep[active.uid] ?? []"
        @update:step="updateStep"
      />

      <section
        class="step-tabs__matching"
        :aria-labelledby="`step-matching-${active.uid}`"
      >
        <h3 :id="`step-matching-${active.uid}`" class="step-tabs__heading m-0">
          {{ t('Matching') }}
        </h3>
        <StepEditor
          :step="active"
          :editable="editable"
          part="matching"
          :errors="errorsByStep[active.uid] ?? []"
          @update:step="updateStep"
        />
      </section>
    </section>
  </div>
</template>

<style scoped>
  .step-tabs {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  .step-tabs__tabs {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: var(--c-spacing-xs);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .step-tabs__tab {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    margin-block-end: -1px;
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-md);
    border: 1px solid transparent;
    border-block-end: 0;
    border-start-start-radius: var(--c-radius-md);
    border-start-end-radius: var(--c-radius-md);
    background: none;
    color: inherit;
    font: inherit;
    cursor: pointer;
  }

  .step-tabs__tab[aria-selected='true'] {
    border-color: var(--c-color-neutral-border-quiet);
    background-color: var(--c-color-neutral-fill-quiet);
    font-weight: 600;
    box-shadow: inset 0 2px 0 var(--c-color-accent-border-loud);
  }

  .step-tabs__add {
    margin-inline-start: var(--c-spacing-xs);
    margin-block-end: var(--c-spacing-xs);
  }

  .step-tabs__panel {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  .step-tabs__strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm) var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-md);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    background-color: var(--c-color-neutral-fill-quiet);
  }

  .step-tabs__summary {
    display: flex;
    flex: 1;
    flex-wrap: wrap;
    align-items: baseline;
    gap: var(--c-spacing-xs) var(--c-spacing-md);
    min-inline-size: 0;
  }

  .step-tabs__source {
    overflow-wrap: anywhere;
  }

  .step-tabs__quiet,
  .step-tabs__source {
    color: var(--c-text-quiet);
  }

  .step-tabs__matching {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    padding-block-start: var(--c-spacing-md);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  /* styled as a field's label, as the mapping's heading is */
  .step-tabs__heading {
    font-size: var(--c-field-label-font-size, inherit);
    font-weight: var(--c-field-label-font-weight, bold);
    color: var(--c-field-label-color, inherit);
  }

  .step-tabs__settings {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-lg);
    padding: var(--c-spacing-md);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
  }
</style>

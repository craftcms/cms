<script setup lang="ts">
  /**
   * One step in an import's list: what it imports and from where, its settings at a
   * glance, and its mapping, with buttons to edit each.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import {t} from '@craftcms/ui';
  import {computed, useTemplateRef} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import {hasMapping} from '@/modules/import/mapping/paths';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import type {StepSummary} from './step-slideout';
  import StepMappingSummary from './StepMappingSummary.vue';

  const {
    step,
    number,
    typeLabel,
    summary,
    editable,
    canToggle,
    invalid,
    opening,
    over,
  } = defineProps<{
    step: ImportStep;
    /** The step's place in the list, from 1. */
    number: number;
    typeLabel: string;
    summary: StepSummary | null;
    editable: boolean;
    /** Whether the step can be disabled or enabled; the last enabled one can't be. */
    canToggle: boolean;
    invalid: boolean;
    /** Which of the step's slideouts is opening. */
    opening: 'settings' | 'mapping' | null;
    /** Whether a dragged step is over this one. */
    over: boolean;
  }>();

  const emit = defineEmits<{
    edit: [opener: HTMLElement | null];
    editMapping: [opener: HTMLElement | null];
    toggleEnabled: [];
    remove: [];
  }>();

  const settingsButton = useTemplateRef<HTMLElement>('settingsButton');
  const mappingButton = useTemplateRef<HTMLElement>('mappingButton');

  const labelId = computed(() => `step-label-${step.uid}`);
  const errorId = computed(() => `step-error-${step.uid}`);

  /** What the step imports and from where, e.g. “Entries — people.csv”. */
  const description = computed(() =>
    [typeLabel, step.source].filter(Boolean).join(' — ')
  );

  const sourceParts = computed(() => step.source?.split(/(?<=\/)/) ?? []);

  /** The step as it's named to screen reader users, e.g. “1. Entries — people.csv”. */
  const name = computed(() => `${number}. ${description.value}`);

  const matchModeLabels: Record<string, string> = {
    createOnly: t('Create new only'),
    createAndUpdate: t('Create and update'),
    updateOnly: t('Update existing only'),
  };

  /** The step's settings as the server summed them up, plus what it does with rows that match. */
  const settingsRows = computed(() => {
    const settings = Array.isArray(step.settings) ? {} : (step.settings ?? {});
    const matching = Object.values(
      (settings.matchCriteria ?? {}) as Record<string, unknown>
    ).some((value) => hasMapping(value));
    const mode = settings.matchMode as string | null | undefined;

    return [
      ...(summary?.settings ?? []),
      ...(matching && mode && matchModeLabels[mode]
        ? [{label: t('If matched'), value: matchModeLabels[mode]}]
        : []),
    ];
  });

  const actions = computed<ActionItems>(() => [
    {
      label: step.enabled ? t('Disable step') : t('Enable step'),
      icon: step.enabled ? 'circle-dashed' : 'circle',
      disabled: !canToggle,
      onClick: () => emit('toggleEnabled'),
    },
    {
      label: t('Delete step'),
      icon: 'trash',
      variant: 'danger',
      onClick: () => emit('remove'),
    },
  ]);

  function fieldCount(count: number): string {
    return t('{count, plural, =1{# field} other{# fields}}', {count});
  }
</script>

<template>
  <li
    class="step-card"
    :class="{'step-card--over': over, 'opacity-60': !step.enabled}"
    :aria-labelledby="labelId"
  >
    <aside class="step-card__rail">
      <div class="flex items-center justify-between gap-sm">
        <div class="flex items-center gap-sm">
          <span class="step-card__number" aria-hidden="true">{{ number }}</span>
        </div>

        <div class="flex items-center gap-sm">
          <ActionMenu
            v-if="editable"
            :label="t('Actions for {item}', {item: name})"
            :actions="actions"
          />
          <slot name="handle"></slot>
        </div>
      </div>

      <div class="grid gap-xs">
        <div :id="labelId" class="flex items-center">
          <div class="sr-only">{{ `${t('Step {number}', {number})}: ` }}</div>
          <div class="text-xl/snug font-bold">{{ typeLabel }}</div>
          <craft-badge v-if="!step.enabled" size="small" no-prefix>{{
            t('Disabled')
          }}</craft-badge>
        </div>
        <!-- a long path wraps after a slash, and only breaks a segment that can't fit -->
        <code v-if="step.source" class="step-card__source text-xs text-quiet"
          ><template v-for="(part, i) in sourceParts" :key="i"
            ><wbr v-if="i > 0" />{{ part }}</template
          ></code
        >
      </div>

      <p v-if="invalid" :id="errorId" class="error m-0">
        {{
          t('The step “{step}” is invalid. Edit it to fix the problem.', {
            step: description,
          })
        }}
      </p>

      <dl v-if="settingsRows.length" class="step-card__settings">
        <template v-for="setting in settingsRows" :key="setting.label">
          <dt class="text-quiet">{{ setting.label }}</dt>
          <dd class="m-0">{{ setting.value }}</dd>
        </template>
      </dl>

      <div class="mt-auto pt-md">
        <craft-button
          ref="settingsButton"
          size="small"
          flush
          variant="plain"
          :icon="editable ? 'pencil' : 'eye'"
          .loading="opening === 'settings'"
          :aria-describedby="invalid ? errorId : undefined"
          :aria-label="
            editable
              ? t('Edit {type}', {type: name})
              : t('View {type}', {type: name})
          "
          @click="emit('edit', settingsButton)"
        >
          {{ editable ? t('Edit settings') : t('View settings') }}
        </craft-button>
      </div>
    </aside>

    <section class="step-card__mapping">
      <StepMappingSummary
        :step="step"
        :labels="summary?.destinationLabels ?? {}"
      >
        <template #header="{count}">
          <div class="flex items-center justify-between gap-sm">
            <h3 class="m-0 text-base">
              {{ t('Mapping') }}
              <span v-if="count" class="font-normal text-sm text-quiet">
                · {{ fieldCount(count) }}
              </span>
            </h3>
            <craft-button
              v-if="count"
              ref="mappingButton"
              size="small"
              variant="plain"
              :icon="editable ? 'pencil' : 'eye'"
              .loading="opening === 'mapping'"
              :aria-label="
                editable
                  ? t('Edit mapping for {type}', {type: name})
                  : t('View mapping for {type}', {type: name})
              "
              @click="emit('editMapping', mappingButton)"
            >
              {{ editable ? t('Edit mapping') : t('View mapping') }}
            </craft-button>
          </div>
        </template>
        <!-- like a nested element field's add button -->
        <template v-if="editable" #empty>
          <div>
            <craft-button
              ref="mappingButton"
              type="button"
              variant="dashed"
              icon="plus"
              .loading="opening === 'mapping'"
              :aria-label="t('Add mapping for {type}', {type: name})"
              @click="emit('editMapping', mappingButton)"
            >
              {{ t('Add mapping') }}
            </craft-button>
          </div>
        </template>
      </StepMappingSummary>
    </section>
  </li>
</template>

<style scoped>
  .step-card {
    display: grid;
    grid-template-columns: minmax(14rem, 18rem) minmax(0, 1fr);
    overflow: hidden;
    background-color: var(--c-surface-default);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-lg);
  }

  .step-card--over {
    border-color: var(--c-color-accent-border-normal);
  }

  .step-card__rail {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-lg);
    background-color: var(--c-surface-sunken);
    border-inline-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .step-card__number {
    display: grid;
    place-items: center;
    /* A lil optical adjustment */
    margin-inline-start: -0.25em;
    inline-size: calc(var(--c-spacing) * 6);
    block-size: calc(var(--c-spacing) * 6);
    border-radius: var(--c-radius-full);
    border: 1px solid var(--c-color-border-quiet);
    color: var(--c-color-on-quiet);
    font-size: var(--c-text-sm);
    font-weight: var(--font-weight-semibold);
  }

  .step-card__source {
    overflow-wrap: anywhere;
  }

  .step-card__settings {
    display: grid;
    grid-template-columns: max-content minmax(0, 1fr);
    gap: var(--c-spacing-sm) var(--c-spacing-xl);
    margin-block: var(--c-spacing-md);
    font-size: var(--c-text-sm);
  }

  .step-card__mapping {
    padding: var(--c-spacing-lg);
    min-width: 0;
  }

  @media (width < var(--breakpoint-md)) {
    .step-card {
      grid-template-columns: minmax(0, 1fr);
    }

    .step-card__rail {
      border-inline-end: 0;
      border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    }
  }
</style>

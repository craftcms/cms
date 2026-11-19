<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, shallowRef} from 'vue';
  import FilterChip from './FilterChip.vue';
  import FilterGroupChip from './FilterGroupChip.vue';
  import FilterPill from './FilterPill.vue';
  import SaveViewButton from './SaveViewButton.vue';
  import RuleEditPopover from './RuleEditPopover.vue';
  import {
    isGroup,
    removeGroup,
    replaceRule,
    rootGroup,
    toggleOperator,
  } from '@/modules/conditions/conditionTree';
  import {
    summarizeRule,
    type RuleSummaryPart,
  } from '@/modules/conditions/summarizeRule';
  import type {ConditionConfig} from '@/modules/conditions/types';
  import type {ConditionRuleSummary} from '@/modules/elements/index/composables/useContentIndexData';

  const props = defineProps<{
    /** The applied condition. */
    condition: ConditionConfig | null;
    /** Each applied rule as the builder renders it, by UID. */
    summaries: ConditionRuleSummary[];
    disabled?: boolean;
    /** Whether the view differs from the source's own. */
    edited?: boolean;
    /** Whether the filters differ from the source's own condition. */
    conditionEdited?: boolean;
    /** Whether the view can be saved as a source. */
    canSave?: boolean;
    /** The source's name, when its saved view can be overwritten. */
    updatableSource?: string | null;
    /** Why the source can't take these changes, if it can't. */
    updateBlockedReason?: string | null;
    /** How the sort and display settings differ from the source's. */
    viewChanges?: Array<{kind: string; label: string; value?: string}>;
    sortDirection?: 'asc' | 'desc';
  }>();

  const emit = defineEmits<{
    'update-condition': [condition: ConditionConfig | null];
    reset: [];
    update: [];
    'save-new': [];
    /** Puts the sort and display settings back to the source's. */
    'reset-view': [];
  }>();

  /** The sort, then how many display settings differ, as one chip each. */
  const viewChips = computed(() => {
    const changes = props.viewChanges ?? [];
    const sort = changes.find((change) => change.kind === 'sort');
    const display = changes.filter((change) => change.kind !== 'sort');
    const chips: ViewChip[] = [];

    if (sort) {
      chips.push({
        menu: 'sort',
        label: t('Sort'),
        icon: props.sortDirection === 'asc' ? 'asc' : 'desc',
        parts: [{kind: 'value', text: sort.value ?? ''}],
      });
    }

    if (display.length) {
      chips.push({
        menu: 'settings',
        label: t('Settings'),
        icon: 'sliders',
        // Just how many, so it stays short; the chip opens the menu that
        // shows which.
        parts: [
          {
            kind: 'value',
            text: t('{count} changed', {count: display.length}),
          },
        ],
      });
    }

    return chips;
  });

  export interface ViewChip {
    menu: 'sort' | 'settings';
    label: string;
    icon: string;
    parts: RuleSummaryPart[];
  }

  interface FilterChipSummary {
    label: string;
    parts: RuleSummaryPart[];
  }

  const root = computed(() => rootGroup(props.condition));
  const chipSummaries = computed(
    () =>
      new Map<string, FilterChipSummary>(
        props.summaries
          .filter((summary) => summary.uid)
          .map((summary) => [
            summary.uid!,
            {
              label: summary.label,
              parts: summary.ui ? summarizeRule(summary.ui) : [],
            },
          ])
      )
  );

  /** The rule or nested group being edited. */
  const editing = shallowRef<{
    uid?: string;
    groupPath?: number[];
    anchor: HTMLElement;
  } | null>(null);
  const editingGroup = computed(() => editing.value?.groupPath?.join('.'));

  function toggleEditing(uid: string, anchor: HTMLElement): void {
    editing.value = editing.value?.uid === uid ? null : {uid, anchor};
  }

  function toggleEditingGroup(groupPath: number[], anchor: HTMLElement): void {
    editing.value =
      editingGroup.value === groupPath.join('.') ? null : {groupPath, anchor};
  }

  function summaryOf(uid?: string): FilterChipSummary {
    return (
      (uid && chipSummaries.value.get(uid)) || {label: t('Filter'), parts: []}
    );
  }

  // Removing anything can shift the paths an open editor points at.
  function removeGroupAt(path: number[]): void {
    editing.value = null;
    if (props.condition) {
      emit('update-condition', removeGroup(props.condition, path));
    }
  }

  function remove(uid: string): void {
    editing.value = null;
    if (props.condition) {
      emit('update-condition', replaceRule(props.condition, uid, null));
    }
  }

  function toggleRoot(): void {
    if (props.condition) {
      emit('update-condition', toggleOperator(props.condition, []));
    }
  }
</script>

<template>
  <div
    v-if="root.rules.length || viewChips.length || conditionEdited"
    class="filter-bar"
    role="region"
    :aria-label="t('Applied filters')"
  >
    <!-- The filters, wrapping as one run of chips. -->
    <div class="filter-bar__chips">
      <span
        v-if="root.rules.length"
        class="filter-group"
        role="group"
        :aria-label="root.operator === 'or' ? t('Match any') : t('Match all')"
      >
        <button
          type="button"
          class="filter-group__operator"
          :disabled="disabled"
          :title="
            root.operator === 'or'
              ? t('Match any of these filters. Click to require all.')
              : t('Match all of these filters. Click to allow any.')
          "
          @click="toggleRoot"
        >
          {{ root.operator === 'or' ? t('Any') : t('All') }}
        </button>
        <template v-for="(item, index) in root.rules" :key="index">
          <!-- A nested group folds into one chip that opens its rules. -->
          <FilterGroupChip
            v-if="isGroup(item)"
            :group="item"
            :expanded="editingGroup === String(index)"
            :disabled="disabled"
            @edit="(anchor) => toggleEditingGroup([index], anchor)"
            @remove="removeGroupAt([index])"
          />
          <FilterChip
            v-else
            :label="summaryOf(item.uid).label"
            :parts="summaryOf(item.uid).parts"
            :expanded="editing?.uid === item.uid"
            :disabled="disabled"
            @edit="(anchor) => item.uid && toggleEditing(item.uid, anchor)"
            @remove="item.uid && remove(item.uid)"
          />
        </template>
      </span>
    </div>
    <span class="filter-bar__end">
      <template v-if="viewChips.length">
        <!-- The view's changes as one group, apart from the filters. -->
        <FilterPill
          :label="t('View')"
          :remove-label="t('Reset view')"
          :disabled="disabled"
          @remove="emit('reset-view')"
        >
          <!-- The index makes each open the menu its settings are in. -->
          <template v-for="chip in viewChips" :key="chip.menu">
            <slot name="view-chip" :chip="chip" />
          </template>
        </FilterPill>
        <span class="filter-bar__rule" aria-hidden="true"></span>
      </template>
      <craft-button
        v-if="edited"
        type="button"
        variant="plain"
        size="small"
        .disabled="disabled"
        @click="emit('reset')"
        >{{ t('Clear') }}</craft-button
      >
      <SaveViewButton
        v-if="edited && canSave"
        :updatable-source="updatableSource"
        :update-blocked-reason="updateBlockedReason"
        :disabled="disabled"
        @update="emit('update')"
        @save-new="emit('save-new')"
      />
    </span>
    <RuleEditPopover
      v-if="editing && condition"
      :key="editing.uid ?? editingGroup"
      :anchor="editing.anchor"
      :condition="condition"
      :uid="editing.uid"
      :group-path="editing.groupPath"
      :label="
        editing.uid ? chipSummaries.get(editing.uid)?.label : t('Filter group')
      "
      @close="editing = null"
      @apply="emit('update-condition', $event)"
    />
  </div>
</template>

<style scoped lang="scss">
  .filter-bar {
    display: flex;
    flex-wrap: wrap;
    // Level with the first line of filters, however many lines they wrap to.
    align-items: flex-start;
    gap: var(--c-spacing-sm);
    margin-block-start: var(--c-spacing-lg);
    // Spans the container's padding like the table below, and pads its
    // content back in by the same amount.
    margin-inline: calc(var(--cp-container-padding) * -1);
    padding-block: var(--c-spacing-md);
    padding-inline: var(--cp-container-padding);
    background-color: var(--c-surface-sunken);
  }

  // The chips wrap among themselves, so the end controls keep their place.
  .filter-bar__chips {
    display: flex;
    flex: 1;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm);
    min-width: 0;
  }

  .filter-bar__rule {
    align-self: stretch;
    inline-size: 1px;
    margin-block: var(--c-spacing-xs);
    background-color: var(--c-color-neutral-border-quiet);
  }

  .filter-group {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm);
    min-width: 0;
  }

  .filter-group__operator {
    height: var(--c-size-control-sm);
    padding-inline: var(--c-spacing-sm);
    border: 0;
    border-radius: var(--c-radius-full);
    background: none;
    color: var(--c-text-quiet);
    font: inherit;
    font-size: var(--c-text-sm);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    cursor: pointer;

    &:hover:not(:disabled) {
      background-color: var(--c-color-neutral-fill-quiet);
      color: inherit;
    }
  }

  .filter-bar__end {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-md);
    margin-inline-start: auto;
  }
</style>

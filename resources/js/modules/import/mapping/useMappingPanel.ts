/**
 * The shell shared by every mapping slideout: `StepMapping.vue` and `NestedMapping.vue`
 * both edit a copy of the mapping trees and provide the same {@link MappingContextKey}
 * for `MappingTable` and its cells to read and write through, differing only in what
 * they render around that context.
 */
import {nextTick, provide, reactive, ref, useId, watch} from 'vue';
import {t} from '@craftcms/ui';
import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';
import {useForm} from '@inertiajs/vue3';
import {useAppLayout} from '@/common/composables/useAppLayout';
import {useSlideout} from '@/common/slideouts';
import {validateStep} from '@/modules/import/steps/step-slideout';
import {openNestedMapping} from './nested-mapping';
import {emptyValueModeOf} from './empty-values';
import {dirtyState, getAt} from './paths';
import {
  type CompoundMappingColumn,
  type MappingColumn,
  MappingContextKey,
  TEMPLATE_RULE,
  type MappingValues,
  type SourceColumn,
  type ImportStep,
  type SuggestedMap,
} from './types';

export interface UseMappingPanelOptions {
  title: string;
  values: MappingValues;
  suggestedMap: SuggestedMap;
  sourceDataCols: SourceColumn[];
  templateTextExpanderTriggers: TextExpanderTriggers;
  editable: boolean;
  /** The draft step being mapped, so a nested panel opened from here can post it. */
  step: ImportStep;
  /**
   * The step's own columns, when this panel maps them rather than a container's, so
   * Apply can check that a required attribute isn't left unmapped.
   */
  cols?: Array<MappingColumn | CompoundMappingColumn>;
  /** The mapping's own page, linked from the slideout's header. */
  editUrl?: string | null;
  submitLabel?: string;
  /** Hands the trees back; a rejection keeps the panel open. */
  apply(values: MappingValues): void | Promise<void>;
}

/**
 * Provides the mapping context `MappingTable` and its cells read and write through, over
 * reactive copies of the given trees, with nested mappings opening in their own panels.
 */
export function provideMappingContext(
  options: Omit<
    UseMappingPanelOptions,
    'title' | 'apply' | 'editUrl' | 'submitLabel'
  >
) {
  const values = reactive<MappingValues>(options.values);
  const suggestedMap = reactive<SuggestedMap>(options.suggestedMap);
  const templateErrors = ref<Record<string, string[]>>({});
  const mapErrors = ref<Record<string, string[]>>({});
  const panelId = useId();

  provide(MappingContextKey, {
    values,
    suggestedMap,
    sourceDataCols: options.sourceDataCols,
    templateTextExpanderTriggers: options.templateTextExpanderTriggers,
    templateErrors,
    mapErrors,
    panelId,
    editable: options.editable,
    openNested(col: MappingColumn, opener: HTMLElement | null): void {
      void openNestedMapping({
        col,
        step: options.step,
        values,
        editable: options.editable,
        opener,
        apply: (applied) => Object.assign(values, applied),
      });
    },
  });

  return {values, suggestedMap, templateErrors, mapErrors, panelId};
}

export function useMappingPanel(options: UseMappingPanelOptions): {
  values: MappingValues;
  suggestedMap: SuggestedMap;
} {
  const slideout = useSlideout();
  const {values, suggestedMap, templateErrors, mapErrors, panelId} =
    provideMappingContext(options);

  /**
   * Backs the shell's Apply button and gives it an accurate dirty check for the
   * unsaved-changes prompt. Inertia diffs against the value it was created with, so
   * a serialization of the trees stands in for them — `dirtyState()`'s, so a cleared
   * control or a reordered key doesn't read as an edit.
   */
  const form = useForm({state: dirtyState(values)});

  watch(
    values,
    () => {
      form.state = dirtyState(values);
    },
    {deep: true}
  );

  useAppLayout(() => ({
    title: options.title,
    submitButtonLabel: options.submitLabel ?? t('Apply'),
    // a mapping that can only be looked at has nothing to apply
    saveDisabled: !options.editable,
    editUrl: options.editUrl ?? null,
    form,
    defaultFormActions: [],
    onSave: apply,
  }));

  async function apply(): Promise<void> {
    if (
      options.editable &&
      (usesTemplates(values.map) || leavesRequiredUnmapped())
    ) {
      const errors = await validateMapping();
      templateErrors.value = errors.templates;
      // a container's panel has none of the step's own columns to show them on
      mapErrors.value = options.cols ? errors.map : {};

      if (
        Object.keys(templateErrors.value).length ||
        Object.keys(mapErrors.value).length
      ) {
        await nextTick();
        document
          .querySelector<HTMLElement>(`[data-mapping-invalid="${panelId}"]`)
          ?.focus();

        return;
      }
    }

    try {
      await options.apply(JSON.parse(JSON.stringify(values)) as MappingValues);
    } catch {
      return;
    }

    form.defaults();

    // Before close(): closing drops the panel from the store, and its handler with it.
    slideout?.saved();
    slideout?.close({force: true});
  }

  /**
   * Whether a required attribute, such as an entry's authors, is left unmapped, which the
   * server can object to. A required field is left to the element's own validation.
   */
  function leavesRequiredUnmapped(): boolean {
    return (options.cols ?? []).some(
      (col) =>
        !('subfields' in col) &&
        col.required &&
        col.isProperty &&
        !getAt(values.map, col.prefixedHandleAsArray) &&
        emptyValueModeOf(values, col) !== 'default'
    );
  }

  /**
   * Validates the step with this panel's trees, returning the errors about its templates
   * and about what fills its columns, each keyed by the column's path. The rest of the
   * step is validated when the step itself is applied.
   */
  async function validateMapping(): Promise<{
    templates: Record<string, string[]>;
    map: Record<string, string[]>;
  }> {
    // PHP sends empty settings as `[]`
    const settings = Array.isArray(options.step.settings)
      ? {}
      : (options.step.settings ?? {});

    try {
      await validateStep({...options.step, settings: {...settings, ...values}});
    } catch (error: any) {
      const errors: Record<string, string[]> =
        error?.response?.data?.errors ?? {};

      return {
        templates: errorsUnder(errors, 'settings.templates.'),
        map: errorsUnder(errors, 'settings.map.'),
      };
    }

    return {templates: {}, map: {}};
  }

  return {values, suggestedMap};
}

/** The errors under a path, keyed by what follows it. */
function errorsUnder(
  errors: Record<string, string[]>,
  prefix: string
): Record<string, string[]> {
  return Object.fromEntries(
    Object.entries(errors)
      .filter(([path]) => path.startsWith(prefix))
      .map(([path, messages]) => [path.slice(prefix.length), messages])
  );
}

/** Whether any column in a `map` tree is filled from a template. */
function usesTemplates(tree: unknown): boolean {
  if (tree === TEMPLATE_RULE) {
    return true;
  }

  return (
    tree !== null &&
    typeof tree === 'object' &&
    Object.values(tree as Record<string, unknown>).some(usesTemplates)
  );
}

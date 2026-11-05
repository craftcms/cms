/**
 * The shell shared by every mapping slideout: `StepMapping.vue` and `NestedMapping.vue`
 * both edit a copy of the mapping trees and provide the same {@link MappingContextKey}
 * for `MappingTable`/`MappingRow` to read and write through, differing only in what
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
import {dirtyState} from './paths';
import {
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
  apply(values: MappingValues): void;
}

export function useMappingPanel(options: UseMappingPanelOptions): {
  values: MappingValues;
  suggestedMap: SuggestedMap;
} {
  const slideout = useSlideout();
  const values = reactive<MappingValues>(options.values);
  const suggestedMap = reactive<SuggestedMap>(options.suggestedMap);
  const templateErrors = ref<Record<string, string[]>>({});
  const panelId = useId();

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
    submitButtonLabel: t('Apply'),
    form,
    defaultFormActions: [],
    onSave: apply,
  }));

  provide(MappingContextKey, {
    values,
    suggestedMap,
    sourceDataCols: options.sourceDataCols,
    templateTextExpanderTriggers: options.templateTextExpanderTriggers,
    templateErrors,
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

  async function apply(): Promise<void> {
    if (options.editable && usesTemplates(values.map)) {
      templateErrors.value = await validateTemplates();

      if (Object.keys(templateErrors.value).length) {
        await nextTick();
        document
          .querySelector<HTMLElement>(
            `[data-mapping-template-invalid="${panelId}"]`
          )
          ?.focus();

        return;
      }
    }

    options.apply(JSON.parse(JSON.stringify(values)) as MappingValues);

    // Before close(): closing drops the panel from the store, and its handler with it.
    slideout?.saved();
    slideout?.close({force: true});
  }

  /**
   * Validates the step with this panel's trees, returning the errors that are about
   * templates, keyed by the column's path. The rest of the step is validated when the
   * step itself is applied.
   */
  async function validateTemplates(): Promise<Record<string, string[]>> {
    // PHP sends empty settings as `[]`
    const settings = Array.isArray(options.step.settings)
      ? {}
      : (options.step.settings ?? {});

    try {
      await validateStep({...options.step, settings: {...settings, ...values}});
    } catch (error: any) {
      const errors: Record<string, string[]> =
        error?.response?.data?.errors ?? {};

      return Object.fromEntries(
        Object.entries(errors)
          .filter(([path]) => path.startsWith('settings.templates.'))
          .map(([path, messages]) => [
            path.slice('settings.templates.'.length),
            messages,
          ])
      );
    }

    return {};
  }

  return {values, suggestedMap};
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

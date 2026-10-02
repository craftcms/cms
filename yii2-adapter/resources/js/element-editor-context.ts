import {provide, shallowRef, type InjectionKey} from 'vue';
import type {InertiaForm} from '@inertiajs/vue3';
import {expandPostArray} from '@/common/utils/forms';
import type {FormValues} from '@/modules/forms/types';
import type {
  ElementEditFormData,
  ElementEditPayload,
  useElementEditor,
} from '@/modules/elements/composables/useElementEditor';

export type ElementEditor = ReturnType<typeof useElementEditor>;
export interface LegacyEditorPayload extends ElementEditPayload {
  editorContentHtml?: string | null;
  editorSidebarHtml?: string | null;
  editorAssets?: CraftCms.Cms.View.HtmlFragment;
}
interface LegacyFormData extends ElementEditFormData {
  legacyEditorValues?: Record<string, Record<string, string | string[]>>;
}

export function provideLegacyEditorContext() {
  const editor = shallowRef<ElementEditor>();
  const contentReady = shallowRef(false);
  const sidebarReady = shallowRef(false);
  const context = {
    editor,
    contentReady,
    sidebarReady,
    publish(
      current: ElementEditor,
      region: string,
      values: Record<string, string | string[]>,
      initial: boolean
    ) {
      const form = current.form as InertiaForm<LegacyFormData>;
      if (!form.legacyEditorValues) {
        form.defaults('legacyEditorValues', {});
      }
      const changed =
        JSON.stringify(form.legacyEditorValues?.[region]) !==
        JSON.stringify(values);
      form.legacyEditorValues = {...form.legacyEditorValues, [region]: values};
      if (initial) {
        form.defaults(
          'legacyEditorValues',
          JSON.parse(JSON.stringify(form.legacyEditorValues))
        );
      } else if (changed) {
        current.autosave.schedule();
      }
    },
    ready(current: ElementEditor, region: string) {
      editor.value = current;
      (region === 'content' ? contentReady : sidebarReady).value = true;
    },
  };
  provide(LegacyEditorContextKey, context);
  return context;
}

export const LegacyEditorContextKey: InjectionKey<
  ReturnType<typeof provideLegacyEditorContext>
> = Symbol('LegacyEditorContext');

export function legacyEditorRequestValues(data: object): FormValues {
  const {legacyEditorValues, ...values} = data as FormValues;
  for (const customValues of Object.values(legacyEditorValues ?? {})) {
    const inputs = Object.fromEntries(
      Object.entries(customValues as Record<string, string | string[]>).map(
        ([name, value]) =>
          name.endsWith('[]')
            ? [name.slice(0, -2), Array.isArray(value) ? value : [value]]
            : [name, value]
      )
    );
    mergeValues(values, expandPostArray(inputs));
  }
  return values;
}

function mergeValues(values: FormValues, custom: FormValues): FormValues {
  for (const [name, value] of Object.entries(custom)) {
    if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
      const current = values[name];
      const nested =
        current !== null &&
        typeof current === 'object' &&
        !Array.isArray(current) &&
        !(current instanceof File)
          ? {...current}
          : {};
      values[name] = mergeValues(nested, value as FormValues);
    } else {
      values[name] = value;
    }
  }
  return values;
}

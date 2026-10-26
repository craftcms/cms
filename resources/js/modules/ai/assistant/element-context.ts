import {getActionUrl, t} from '@craftcms/ui';
import type {TextExpanderOption} from '@craftcms/ui/components/text-expander/text-expander';
import {shallowReactive} from 'vue';
import AssistantController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AssistantController';
import {assistantApi} from '@/bootstrap/assistant';
import {actionFetch} from './assistant';
import {
  assistantEditor,
  assistantEditors,
  rememberAssistantEditor,
  type AssistantContextField,
  type AssistantContextItem,
  type AssistantContextStatus,
  type AssistantEditor,
} from './context';

/** The handle of the context type of elements, on the server. */
export const ELEMENT_CONTEXT_TYPE = 'craft:element';

const ELEMENT_ICON = 'file-lines';

/**
 * The fields of an editor’s element in the assistant’s context: `null` for
 * all of them, or `undefined` when it isn’t in the context.
 *
 * Elements are added with the same API as plugins use, `Cp.$assistant`, and
 * keep the fields chosen in their item’s `meta`.
 */
export function elementContextFields(
  editor: AssistantEditor
): AssistantContextField[] | null | undefined {
  const item = assistantApi.getContext(elementKey(editor));

  return item
    ? ((item.meta.fields as AssistantContextField[] | null | undefined) ?? null)
    : undefined;
}

/**
 * Sets which of an editor’s fields are in the context: `null` for the whole
 * element, or none for the element without its fields. Its chip takes it out.
 */
export function setElementContext(
  editor: AssistantEditor,
  fields: AssistantContextField[] | null
): void {
  const label = editor.label();
  const params = editor.params();
  const ref = assistantApi.addContext({
    type: ELEMENT_CONTEXT_TYPE,
    key: elementKey(editor),
    label: fields?.length
      ? `${label} › ${fields.map((field) => field.label).join(', ')}`
      : label,
    icon: ELEMENT_ICON,
    status: () => editorStatuses.get(editor.id) ?? null,
    meta: {fields, editorId: editor.id},
    // Without the editor’s values, which later messages read from the draft
    // the editor saves its changes to.
    reference: () => ({
      ...(assistantEditor(editor.id)?.params() ?? params),
      fields: fields?.map((field) => field.path) ?? null,
      namespace: assistantEditor(editor.id)?.scope() ?? [],
    }),
    // Without the editor’s values once it’s closed, in which case the
    // element’s saved values are used.
    data: () => {
      const open = assistantEditor(editor.id);

      return {
        ...(open?.params() ?? params),
        fields: fields?.map((field) => field.path) ?? null,
        namespace: open?.scope() ?? [],
        values: open?.values() ?? {},
      };
    },
  });

  rememberAssistantEditor(ref, editor.id);
}

/** Adds a field, unless the whole element, or the field, already is. */
export function addElementField(
  editor: AssistantEditor,
  field: AssistantContextField
): void {
  const fields = elementContextFields(editor);

  if (fields === null || fields?.some(({path}) => path === field.path)) {
    return;
  }

  setElementContext(editor, [...(fields ?? []), field]);
}

/**
 * The item each request names the latest open editor’s element with, without
 * its content, so the model reads it only when a question is about it. It has
 * the editor’s values for when the model reads it.
 */
export function openElementRequest(): Array<Record<string, unknown>> {
  const editor = latestEditor();

  if (!editor) {
    return [];
  }

  // Changes proposed for it are applied to this editor.
  rememberAssistantEditor(editor.id, editor.id);

  return [
    {
      ...editor.params(),
      type: ELEMENT_CONTEXT_TYPE,
      ref: editor.id,
      open: true,
      fields: null,
      namespace: editor.scope(),
      values: editor.values(),
    },
  ];
}

/** A field of an editor’s element that can be added to the context. */
export interface ElementFieldOption {
  /** Its path, such as `fields.summary`. */
  value: string;
  label: string;
}

/** The fields of each open editor’s element that can be added, by editor ID. */
const editorFields = shallowReactive(new Map<string, ElementFieldOption[]>());
/** The status of each open editor’s element, by editor ID. */
const editorStatuses = shallowReactive(
  new Map<string, AssistantContextStatus | null>()
);

/** The fields of an editor’s element that can be added, as last loaded. */
export function elementFieldOptions(
  editor: AssistantEditor
): ElementFieldOption[] {
  return editorFields.get(editor.id) ?? [];
}

/**
 * Loads the fields of an editor’s element that can be added, with the
 * editor’s unsaved values, since its nested entries come and go. A failure
 * keeps the ones from before.
 */
export async function loadElementFields(
  editor: AssistantEditor
): Promise<void> {
  try {
    const response = await actionFetch(
      getActionUrl(AssistantController.contextFields.url()),
      {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          ...editor.params(),
          namespace: editor.scope(),
          values: editor.values(),
        }),
      }
    );
    const body = (await response.json()) as {
      fields: ElementFieldOption[];
      status: AssistantContextStatus | null;
    };
    editorFields.set(editor.id, body.fields);
    editorStatuses.set(editor.id, body.status);
  } catch {
    // The element can still be added as a whole.
  }
}

/** What an `@` suggestion is, as the server describes it. */
interface ElementMention {
  elementType: string;
  elementId: number;
  siteId: number | null;
  status?: AssistantContextStatus | null;
}

function mentionOf(
  option: Readonly<TextExpanderOption>
): ElementMention | null {
  const data = option.data as Partial<ElementMention> | undefined;

  return typeof data?.elementType === 'string' &&
    typeof data.elementId === 'number'
    ? (data as ElementMention)
    : null;
}

/**
 * Adds an element the user mentioned to the context: the open editor’s, if
 * it’s the one, or otherwise as it’s saved.
 */
function addMentionedElement(option: Readonly<TextExpanderOption>): void {
  const mention = mentionOf(option);

  if (!mention) {
    return;
  }

  const editor = assistantEditors().find(
    (open) =>
      open.params().elementId === mention.elementId &&
      open.params().siteId === mention.siteId
  );

  if (editor) {
    setElementContext(editor, null);

    return;
  }

  const params = {
    elementType: mention.elementType,
    elementId: mention.elementId,
    siteId: mention.siteId,
    fields: null,
    namespace: [],
  };

  assistantApi.addContext({
    type: ELEMENT_CONTEXT_TYPE,
    key: `${ELEMENT_CONTEXT_TYPE}:${mention.elementType}:${mention.elementId}:${mention.siteId}`,
    label: option.label,
    icon: ELEMENT_ICON,
    status: mention.status ?? undefined,
    data: {...params, values: {}},
    reference: () => params,
  });
}

// Craft’s own expanders: `{` for a field of the open element, and `@` for any
// element the user can view. Each adds what’s chosen to the context.
assistantApi.registerTextExpander({
  id: 'craft:fields',
  trigger: '{',
  boundary: 'anywhere',
  label: t('Fields'),
  options: () => {
    const editor = latestEditor();

    return editor
      ? elementFieldOptions(editor).map(({value, label}) => ({
          label,
          value: label,
          data: {path: value},
        }))
      : [];
  },
  select: (option) => {
    const editor = latestEditor();
    const path = (option.data as {path?: unknown} | undefined)?.path;

    if (editor && typeof path === 'string') {
      addElementField(editor, {path, label: option.label});
    }
  },
});

assistantApi.registerTextExpander({
  id: 'craft:elements',
  trigger: '@',
  label: t('Elements'),
  source: () => AssistantController.mentions.url(),
  limit: 10,
  select: addMentionedElement,
});

// Craft’s own source: the latest open editor’s element, with all its fields.
// Which of them stay in is chosen with `AssistantContextPicker` once it’s in.
assistantApi.registerContextSource({
  id: 'craft:element',
  label: () => latestEditor()?.label() ?? '',
  icon: ELEMENT_ICON,
  order: 0,
  available: () => {
    const editor = latestEditor();

    return editor !== null && elementContextFields(editor) === undefined;
  },
  add: () => {
    const editor = latestEditor();

    if (editor) {
      setElementContext(editor, null);
    }
  },
});

/**
 * The open editor an element item in the context came from, whose fields its
 * chip chooses, or `null` for another item, or once the editor’s closed.
 */
export function elementContextEditor(
  item: AssistantContextItem
): AssistantEditor | null {
  return item.type === ELEMENT_CONTEXT_TYPE &&
    typeof item.meta.editorId === 'string'
    ? assistantEditor(item.meta.editorId)
    : null;
}

/** The editor the assistant offers to add. */
export function latestEditor(): AssistantEditor | null {
  return assistantEditors().at(-1) ?? null;
}

function elementKey(editor: AssistantEditor): string {
  return `${ELEMENT_CONTEXT_TYPE}:${editor.id}`;
}

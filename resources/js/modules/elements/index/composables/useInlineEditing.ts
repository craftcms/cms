import {serializeFormInputs, t} from '@craftcms/ui';
import {firstFocusableWithin} from '@/common/utils/dom';
import type {CellContext} from '@tanstack/vue-table';
import type {CraftTableFeatures} from '@/modules/admin-table/craftTable';
import {h, nextTick, ref, toValue, type MaybeRefOrGetter, type Ref} from 'vue';
import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
import type {InlineAttributeFormHost} from '@/modules/forms/inline-attribute-form-host';

export type InlineEditingErrors = Record<
  string | number,
  Record<string, string[]>
>;

export interface InlineEditingSaveResult {
  errors?: InlineEditingErrors;
  message?: string;
}

export interface InlineEditableRow extends Record<string, unknown> {
  id: string | number;
  inlineEditable?: boolean;
  inlineInputHtml?: Record<string, string>;
}

export function useInlineEditing<Row extends InlineEditableRow>(options: {
  container: Readonly<Ref<HTMLElement | null | undefined>>;
  editableRows: MaybeRefOrGetter<Row[]>;
  busy: MaybeRefOrGetter<boolean>;
  loading: MaybeRefOrGetter<boolean>;
  load(editable: boolean): Promise<void>;
  save(body: URLSearchParams): Promise<InlineEditingSaveResult | false>;
}) {
  const editingIds = ref<Array<string | number>>([]);
  const errors = ref<InlineEditingErrors>({});
  let initialInputs = '';

  function renderCell(
    context: CellContext<CraftTableFeatures, Row, unknown>,
    showErrors: boolean
  ) {
    const entry = context.row.original;
    const columnId = context.column.id;
    const html = editingIds.value.includes(entry.id)
      ? entry.inlineInputHtml?.[columnId]
      : null;
    const value = entry[columnId];
    const fallback =
      typeof value === 'string' || typeof value === 'number'
        ? String(value)
        : '';

    return h('div', {'data-inline-id': entry.id}, [
      h(DynamicHtmlRenderer, {
        html: html || fallback,
      }),
      ...(showErrors
        ? Object.values(errors.value[entry.id] ?? {})
            .flat()
            .map((message) => h('p', {role: 'alert'}, message))
        : []),
    ]);
  }

  function hosts(): InlineAttributeFormHost[] {
    return [
      ...(options.container.value?.querySelectorAll<InlineAttributeFormHost>(
        'craft-inline-attribute-form'
      ) ?? []),
    ];
  }

  async function start(): Promise<void> {
    await options.load(true);
    editingIds.value = toValue(options.editableRows)
      .filter((entry) => Boolean(entry.inlineInputHtml))
      .map((entry) => entry.id);
    await nextTick();

    const formHosts = hosts();
    await Promise.all(formHosts.map((host) => host.ready));
    initialInputs = serializeFormInputs(options.container.value!);

    if (!formHosts[0]?.focusFirst()) {
      const firstCell =
        options.container.value?.querySelector<HTMLElement>('[data-inline-id]');
      if (firstCell) {
        firstFocusableWithin(firstCell)?.focus();
      }
    }
  }

  function clear(): void {
    editingIds.value = [];
    errors.value = {};
    initialInputs = '';
  }

  function cancel(): void {
    const container = options.container.value;
    if (!container) {
      return;
    }

    if (
      initialInputs !== serializeFormInputs(container) &&
      !window.confirm(t('Are you sure you want to cancel your changes?'))
    ) {
      return;
    }

    clear();
  }

  async function save(): Promise<void> {
    const container = options.container.value;
    if (!container || toValue(options.busy) || toValue(options.loading)) {
      return;
    }

    const formHosts = hosts();
    if (formHosts.some((host) => !host.canSubmit())) {
      return;
    }

    const submittedInputs = serializeFormInputs(container);
    const body = new URLSearchParams(
      window.Craft.findDeltaData(
        initialInputs.replaceAll('+', '%20'),
        submittedInputs.replaceAll('+', '%20'),
        editingIds.value.map((id) => `inline[element-${id}]`)
      )
    );
    if (!body.has('modifiedDeltaNames[]')) {
      clear();
      window.Craft?.cp?.displayNotice?.(t('No changes to save.'));

      return;
    }

    const result = await options.save(body);
    if (result === false) {
      return;
    }

    if (result.errors && Object.keys(result.errors).length) {
      errors.value = result.errors;
      window.Craft?.cp?.displayError?.(t('Couldn’t save.'));

      for (const host of formHosts) {
        host.errors =
          result.errors[
            Number(
              host.closest('[data-inline-id]')?.getAttribute('data-inline-id')
            )
          ] ?? {};
      }

      return;
    }

    clear();
    window.Craft?.cp?.displayNotice?.(result.message ?? t('Changes saved.'));
  }

  function onKeydown(event: KeyboardEvent): void {
    const plainTextareaEnter =
      event.target instanceof HTMLTextAreaElement &&
      !event.ctrlKey &&
      !event.metaKey;
    const shouldSave =
      (event.key === 'Enter' && !plainTextareaEnter) ||
      ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's');
    if (!shouldSave || !editingIds.value.length) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    void save();
  }

  return {
    editingIds,
    errors,
    renderCell,
    start,
    clear,
    cancel,
    save,
    onKeydown,
  };
}

import {computed, ref, watch} from 'vue';
import {canonical, pathsMatch} from '../runtime';
import type {FormControlPayload, FormValues, NestedFormPayload} from '../types';
import {bindFormScope} from '../formScope';
import type {TableControlProps, TableRow, TableValue} from './types';

export function useTableRows(props: {
  value: TableValue;
  control: FormControlPayload<TableControlProps>;
}) {
  const rows = ref<TableRow[]>([]);
  let nextKey = props.control.props.rowIdPrefix ? 1 : 0;
  const keyed = computed(() => Boolean(props.control.props.keyed));

  function entries(value: TableValue): Array<[string, FormValues]> {
    if (!value) return [];

    return Array.isArray(value)
      ? value.map((row, index) => [String(index), row])
      : Object.entries(value);
  }

  function externalValue(): Exclude<TableValue, null> {
    return keyed.value
      ? Object.fromEntries(rows.value.map((row) => [row.key, {...row.value}]))
      : rows.value.map((row) => ({...row.value}));
  }

  function createRow(value: FormValues, key?: string): TableRow {
    if (key === undefined) {
      if (keyed.value) {
        const prefix =
          props.control.props.rowIdPrefix ??
          rows.value[0]?.key.match(/^(.*\D)\d+$/)?.[1] ??
          'row';
        do {
          key = `${prefix}${nextKey++}`;
        } while (rows.value.some((row) => row.key === key));
      } else {
        key = String(rows.value.length);
      }
    }

    return {id: crypto.randomUUID(), key, value: {...value}};
  }

  function reconcile(value: TableValue): void {
    const remaining = new Set(rows.value);
    rows.value = entries(value).map(([key, value], index) => {
      const existing = keyed.value
        ? rows.value.find((row) => row.key === key)
        : ([...remaining].find(
            (row) => canonical(row.value) === canonical(value)
          ) ?? rows.value[index]);
      if (existing && remaining.has(existing)) {
        remaining.delete(existing);
        existing.key = key;
        existing.value = {...value};

        return existing;
      }

      return createRow(value, key);
    });
    for (const row of rows.value) {
      const suffix = row.key.match(/(\d+)$/)?.[1];
      if (suffix) nextKey = Math.max(nextKey, Number(suffix) + 1);
    }
    attachForms();
  }

  function attachForms(): void {
    for (const [index, row] of rows.value.entries()) {
      const scope = [
        ...props.control.path,
        keyed.value ? row.key : String(index),
      ];
      const concrete = props.control.forms?.find((form) =>
        pathsMatch(form.scope, scope)
      );
      if (concrete) row.form = concrete;
    }
  }

  watch(
    () => props.value,
    (value) => {
      const orderChanged =
        keyed.value &&
        entries(value).some(([key], index) => rows.value[index]?.key !== key);
      if (orderChanged || canonical(value) !== canonical(externalValue()))
        reconcile(value);
    },
    {deep: true}
  );
  watch(() => props.control.forms, attachForms);
  reconcile(props.value);

  const forms = computed(() =>
    rows.value.flatMap((_, index) => {
      const form = rowForm(index);
      return form ? [form] : [];
    })
  );

  function rowForm(index: number): NestedFormPayload | undefined {
    const row = rows.value[index];
    if (!row) return undefined;

    const form = row.form ?? props.control.props.rowTemplate;
    return form
      ? bindFormScope(
          form,
          [...props.control.path, keyed.value ? row.key : String(index)],
          props.control.deltaGroup
        )
      : undefined;
  }

  return {rows, forms, keyed, externalValue, createRow, rowForm};
}

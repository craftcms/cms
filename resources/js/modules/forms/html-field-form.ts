import {
  fieldId,
  isRecord,
  pathsMatch,
  setValue,
  valueAt,
  visitControls,
} from './runtime';
import type {FormNodePayload, FormPayload, NestedFormPayload} from './types';

/** Legacy callers namespace the HTML input after the native payload was captured. */
export function rebaseFieldForm(
  form: FormPayload,
  original: string[],
  path: string[]
): FormPayload {
  const rebase = (candidate: string[]) =>
    original.every((segment, index) => candidate[index] === segment)
      ? [...path, ...candidate.slice(original.length)]
      : candidate;
  const nodes = (source: FormNodePayload[]): FormNodePayload[] =>
    source.map((node) => ({
      ...node,
      ...(node.control && pathsMatch(node.control.path, original)
        ? {props: {id: `${fieldId(path)}-native`}}
        : {}),
      ...(node.children ? {children: nodes(node.children)} : {}),
      ...(node.control
        ? {
            control: {
              ...node.control,
              path: rebase(node.control.path),
              deltaGroup: rebase(node.control.deltaGroup),
              forms: node.control.forms?.map((nested) => ({
                ...nested,
                scope: rebase(nested.scope),
                nodes: nodes(nested.nodes),
              })),
            },
          }
        : {}),
    }));
  const values: FormPayload['values'] = {};
  setValue(values, path, valueAt(form.values, original));

  return {
    ...form,
    nodes: nodes(form.nodes),
    values,
    scope: path.slice(
      0,
      Math.max(0, path.length - original.length + form.scope.length)
    ),
    errors: form.errors.map((error) => ({...error, path: rebase(error.path)})),
  };
}

/** Refresh only the mounted field; the surrounding HTML form owns its other inputs. */
export function isolateFieldForm(
  form: FormPayload,
  path: string[],
  scope?: string[]
): FormPayload {
  const find = (nodes: FormNodePayload[]): FormNodePayload | undefined => {
    for (const node of nodes) {
      if (node.control && pathsMatch(node.control.path, path)) {
        return node;
      }
      const child = find(node.children ?? []);
      if (child) return child;
      for (const nested of node.control?.forms ?? []) {
        const child = find(nested.nodes);
        if (child) return child;
      }
    }
  };
  const node = find(form.nodes);
  if (!node?.control) {
    throw new Error('The nested element field is no longer available.');
  }
  const values: FormPayload['values'] = {};
  setValue(values, path, valueAt(form.values, path));
  const isolated = {
    ...form,
    values,
    errors: form.errors.filter((error) =>
      path.every((segment, index) => error.path[index] === segment)
    ),
    globalErrors: [],
    nodes: [
      {...node, props: {id: `${fieldId(path)}-native`}, children: undefined},
    ],
  };
  if (!scope || pathsMatch(scope, isolated.scope)) {
    return isolated;
  }
  const forms: NestedFormPayload[] = [];
  visitControls(isolated.nodes, (control) =>
    forms.push(...(control.forms ?? []))
  );
  const nested = forms.find((form) => pathsMatch(form.scope, scope));
  if (!nested) {
    throw new Error('The nested element form is no longer available.');
  }
  return {
    ...isolated,
    scope: nested.scope,
    refreshable: nested.refreshable,
    nodes: nested.nodes,
    errors: isolated.errors.filter((error) =>
      scope.every((segment, index) => error.path[index] === segment)
    ),
  };
}

/** Keep locally created fields until the server's next layout includes them. */
export function preserveFieldForms(
  form: FormPayload,
  previous: FormPayload
): void {
  const controls = new Map<string, FormNodePayload['control']>();
  visitControls(previous.nodes, (control) =>
    controls.set(JSON.stringify(control.path), control)
  );
  visitControls(form.nodes, (control) => {
    const before = controls.get(JSON.stringify(control.path));
    if (
      !before ||
      before.component !== 'craft:nested-element-blocks' ||
      before.component !== control.component ||
      control.mode !== 'editable'
    )
      return;
    const scopes = new Set(
      (control.forms ?? []).map((nested) => JSON.stringify(nested.scope))
    );
    const missing = (before.forms ?? []).filter(
      (nested) => !scopes.has(JSON.stringify(nested.scope))
    );
    if (!missing.length) return;
    Object.assign(control, {
      forms: [...(control.forms ?? []), ...missing],
      props: {
        ...control.props,
        ...(isRecord(before.props.blocks)
          ? {
              blocks: {
                ...before.props.blocks,
                ...(isRecord(control.props.blocks) ? control.props.blocks : {}),
              },
            }
          : {}),
      },
    });
  });
}

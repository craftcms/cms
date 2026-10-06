import {setValue, valueAt} from './runtime';
import type {
  FormPayload,
  FormControlPayload,
  FormNodePayload,
  NestedFormPayload,
} from './types';

export function bindFormScope(
  form: NestedFormPayload,
  scope: string[],
  deltaGroup?: string[]
): NestedFormPayload {
  function bindPath(path: string[]): string[] {
    return [...scope, ...path.slice(form.scope.length)];
  }

  function bindNodes(nodes: FormNodePayload[]): FormNodePayload[] {
    return nodes.map((node) => ({
      ...node,
      children: node.children ? bindNodes(node.children) : undefined,
      control: node.control ? bindControl(node.control) : undefined,
    }));
  }

  function bindControl(control: FormControlPayload): FormControlPayload {
    return {
      ...control,
      path: bindPath(control.path),
      deltaGroup: deltaGroup ?? bindPath(control.deltaGroup),
      forms: control.forms?.map((nested) => ({
        ...nested,
        scope: bindPath(nested.scope),
        nodes: bindNodes(nested.nodes),
      })),
    };
  }

  return {...form, scope, nodes: bindNodes(form.nodes)};
}

export function scopeFormPayload(
  payload: FormPayload,
  scope: string[],
  deltaGroup?: string[]
): FormPayload {
  const values = {};
  const value = valueAt(payload.values, payload.scope);
  if (scope.length) setValue(values, scope, value);
  else Object.assign(values, value);

  return {
    ...payload,
    ...bindFormScope(payload, scope, deltaGroup),
    values,
    errors: payload.errors.map((error) => ({
      ...error,
      path: [...scope, ...error.path.slice(payload.scope.length)],
    })),
  };
}

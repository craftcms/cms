import {setValue, valueAt} from './runtime';
import type {
  UiPayload,
  UiControlPayload,
  UiNodePayload,
  NestedUiPayload,
} from './types';

export function bindUiScope(
  form: NestedUiPayload,
  scope: string[],
  deltaGroup?: string[]
): NestedUiPayload {
  function bindPath(path: string[]): string[] {
    return [...scope, ...path.slice(form.scope.length)];
  }

  function bindNodes(nodes: UiNodePayload[]): UiNodePayload[] {
    return nodes.map((node) => ({
      ...node,
      children: node.children ? bindNodes(node.children) : undefined,
      control: node.control ? bindControl(node.control) : undefined,
    }));
  }

  function bindControl(control: UiControlPayload): UiControlPayload {
    return {
      ...control,
      path: bindPath(control.path),
      deltaGroup: deltaGroup ?? bindPath(control.deltaGroup),
      uis: control.uis?.map((nested) => ({
        ...nested,
        scope: bindPath(nested.scope),
        nodes: bindNodes(nested.nodes),
      })),
    };
  }

  return {...form, scope, nodes: bindNodes(form.nodes)};
}

export function scopeUiPayload(
  payload: UiPayload,
  scope: string[],
  deltaGroup?: string[]
): UiPayload {
  const values = {};
  const value = valueAt(payload.values, payload.scope);
  if (scope.length) setValue(values, scope, value);
  else Object.assign(values, value);

  return {
    ...payload,
    ...bindUiScope(payload, scope, deltaGroup),
    values,
    errors: payload.errors.map((error) => ({
      ...error,
      path: [...scope, ...error.path.slice(payload.scope.length)],
    })),
  };
}

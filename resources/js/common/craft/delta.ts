import {isPlainObject, param} from './params';

/**
 * Delta form submissions: posting only the parts of a form that changed.
 *
 * Form data is grouped by "delta names" (field or setting namespaces). A group
 * whose serialized params changed is posted along with a `modifiedDeltaNames[]`
 * entry for it; unchanged groups are left out.
 */

export type GroupedParams = Record<string, string[]>;

export type DeltaCallback = (name: string, params: string[]) => void;

function sortBySpecificity(names: string[], mostSpecific: boolean): void {
  names.sort((a, b) => {
    if (a.length === b.length) {
      return 0;
    }
    if (mostSpecific) {
      return a.length < b.length ? 1 : -1;
    }
    return a.length > b.length ? 1 : -1;
  });
}

/**
 * Groups serialized params by the delta name they belong to. Params outside
 * every delta name go under `__root__` when `withRoot` is set.
 *
 * Sorts `deltaNames` in place, most specific first.
 */
export function groupParams(
  params: string | string[],
  deltaNames: string[],
  withRoot = false
): GroupedParams {
  const list = typeof params === 'string' ? params.split('&') : params;
  const grouped: GroupedParams = {};

  if (withRoot) {
    grouped.__root__ = [];
  }

  deltaNames.sort((a, b) => b.length - a.length);

  for (const name of deltaNames) {
    grouped[name] = [];
  }

  const encode = (o: string) => encodeURIComponent(o).replace('%3D', '=');

  paramLoop: for (const p of list.map((p) => decodeURIComponent(p))) {
    for (const name of deltaNames) {
      const paramName = p.substring(0, name.length + 1);
      if ([`${name}=`, `${name}[`].includes(paramName)) {
        (grouped[name] ??= []).push(encode(p));
        continue paramLoop;
      }
    }

    if (withRoot) {
      grouped.__root__!.push(encode(p));
    }
  }

  return grouped;
}

/**
 * {@link groupParams}, with the groups for `initialValues` replaced by those
 * values, serialized the way the form would have posted them.
 */
export function groupParamsByDeltaNames(
  params: string | string[],
  deltaNames: string[],
  withRoot: boolean,
  initialValues: Record<string, unknown> | false
): GroupedParams {
  const grouped = groupParams(params, deltaNames, withRoot);

  if (!initialValues) {
    return grouped;
  }

  const serializeParam = (name: string, value: unknown): string => {
    let serialized = value;
    if (Array.isArray(value) || isPlainObject(value)) {
      serialized = param(value);
    } else if (typeof value === 'string') {
      serialized = encodeURIComponent(value);
    } else if (value === null) {
      serialized = '';
    }
    return `${encodeURIComponent(name)}=${String(serialized)}`;
  };

  for (const name in initialValues) {
    if (!Object.prototype.hasOwnProperty.call(initialValues, name)) {
      continue;
    }

    const value = initialValues[name];
    if (isPlainObject(value)) {
      grouped[name] = [];
      for (const subName in value) {
        if (Object.prototype.hasOwnProperty.call(value, subName)) {
          grouped[name].push(
            serializeParam(`${name}[${subName}]`, value[subName])
          );
        }
      }
    } else {
      grouped[name] = [serializeParam(name, value)];
    }
  }

  return grouped;
}

/**
 * Returns the delta names whose params differ between the old and new form
 * data, and the new params grouped by delta name.
 */
export function findModifiedDeltaNames(
  oldData: unknown,
  newData: unknown,
  deltaNames: unknown,
  initialDeltaValues: unknown = {},
  modifiedDeltaNames: unknown = [],
  mostSpecific = false
): [string[], GroupedParams] {
  // Both are split on `&` below, so anything else counts as empty.
  const oldParams = typeof oldData === 'string' ? oldData : '';
  const newParams = typeof newData === 'string' ? newData : '';
  const names = Array.isArray(deltaNames) ? [...deltaNames] : [];
  const initialValues = isPlainObject(initialDeltaValues)
    ? initialDeltaValues
    : {};
  const modified: string[] = Array.isArray(modifiedDeltaNames)
    ? [...modifiedDeltaNames]
    : [];

  sortBySpecificity(names, mostSpecific);

  const groupedOldParams = groupParamsByDeltaNames(
    oldParams,
    names,
    false,
    initialValues
  );
  const groupedNewParams = groupParamsByDeltaNames(
    newParams,
    names,
    true,
    false
  );

  for (const name of names) {
    if (
      !modified.includes(name) &&
      typeof groupedNewParams[name] === 'object' &&
      (typeof groupedOldParams[name] !== 'object' ||
        JSON.stringify(groupedOldParams[name]) !==
          JSON.stringify(groupedNewParams[name]))
    ) {
      modified.push(name);
    }
  }

  sortBySpecificity(modified, mostSpecific);

  return [modified, groupedNewParams];
}

/**
 * Returns the params that should be posted: everything outside the delta
 * names, plus the groups that changed and a `modifiedDeltaNames[]` entry for
 * each.
 */
export function findDeltaData(
  oldData: unknown,
  newData: unknown,
  deltaNames: unknown,
  callback?: DeltaCallback | null,
  initialDeltaValues?: unknown,
  forceModifiedDeltaNames?: unknown,
  asArray?: false
): string;
export function findDeltaData(
  oldData: unknown,
  newData: unknown,
  deltaNames: unknown,
  callback: DeltaCallback | null | undefined,
  initialDeltaValues: unknown,
  forceModifiedDeltaNames: unknown,
  asArray: true
): string[];
export function findDeltaData(
  oldData: unknown,
  newData: unknown,
  deltaNames: unknown,
  callback: DeltaCallback | null = null,
  initialDeltaValues: unknown = {},
  forceModifiedDeltaNames: unknown = [],
  asArray = false
): string | string[] {
  const [modifiedDeltaNames, groupedNewParams] = findModifiedDeltaNames(
    oldData,
    newData,
    deltaNames,
    initialDeltaValues,
    forceModifiedDeltaNames
  );

  let params = groupedNewParams.__root__!;
  for (const name of modifiedDeltaNames) {
    params = params.concat(groupedNewParams[name]!);
    params.push(`modifiedDeltaNames[]=${name}`);
    if (callback) {
      callback(name, groupedNewParams[name]!);
    }
  }

  return asArray ? params : params.join('&');
}

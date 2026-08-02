import type {FormValues} from './types';

export function scopedPath(bindingScope: string, inputName: string): string {
  return bindingScope ? `${bindingScope}.${inputName}` : inputName;
}

export function htmlInputName(path: string): string {
  const [first, ...segments] = path.split('.');

  return first + segments.map((segment) => `[${segment}]`).join('');
}

export function inputId(path: string): string {
  return `form-element-${path
    .split('.')
    .map((segment) => encodeURIComponent(segment))
    .join('--')}`;
}

export function valueAt(values: FormValues, path: string): unknown {
  return path.split('.').reduce<unknown>((value, segment) => {
    if (typeof value !== 'object' || value === null || Array.isArray(value)) {
      return undefined;
    }

    return (value as FormValues)[segment];
  }, values);
}

export function setValueAt(
  values: FormValues,
  path: string,
  value: unknown
): void {
  const segments = path.split('.');
  const finalSegment = segments.pop()!;
  let parent = values;

  for (const segment of segments) {
    const child = parent[segment];

    if (typeof child !== 'object' || child === null || Array.isArray(child)) {
      throw new Error(`Cannot bind Form Element value at ${path}.`);
    }

    parent = child as FormValues;
  }

  parent[finalSegment] = value;
}

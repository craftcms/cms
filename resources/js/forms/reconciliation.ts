import type {FormElementData} from './types';

export function reconciliationKey(
  element: FormElementData,
  index: number
): string {
  if (element.key) {
    return `key:${element.key}`;
  }

  const inputName =
    element.name ??
    (element.type === 'craft:field' ? element.children?.[0]?.name : undefined);

  return inputName ? `name:${inputName}` : `position:${index}`;
}

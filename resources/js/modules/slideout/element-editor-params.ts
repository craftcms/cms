import type {UiValues} from '@/modules/ui/types';

/** The ids an element editor can be opened for. */
export interface ElementEditorTarget {
  elementType?: string | null;
  elementId?: number | null;
  draftId?: number | false | null;
  revisionId?: number | false | null;
  fieldId?: number | false | null;
  ownerId?: number | false | null;
  siteId?: number | null;
  prevalidate?: boolean;
}

export interface ElementEditorParams extends UiValues {
  elementType?: string;
  elementId?: number;
  draftId?: number | null;
  revisionId?: number | null;
  fieldId?: number | null;
  ownerId?: number | null;
  siteId?: number;
  prevalidate?: 1;
}

/** A numeric `data-*` value, as jQuery's `.data()` read it. */
function numericData(element: HTMLElement | null, key: string): number | null {
  const value = element?.dataset[key];
  if (!value) {
    return null;
  }
  const number = Number(value);
  return Number.isFinite(number) ? number : null;
}

/**
 * The `elements/edit` params for an element editor: from the settings, falling
 * back to the `data-*` attributes of the element it was opened for. A jQuery
 * collection's first element is used too.
 */
export function elementEditorParams(
  target: HTMLElement | ArrayLike<HTMLElement> | null | undefined,
  settings: ElementEditorTarget
): ElementEditorParams {
  const element =
    target instanceof HTMLElement ? target : (target?.[0] ?? null);
  const params: ElementEditorParams = {};

  if (settings.elementType) {
    params.elementType = settings.elementType;
  }

  const elementId = settings.elementId || numericData(element, 'id');
  if (elementId) {
    params.elementId = elementId;
  }

  if (settings.draftId !== null && settings.draftId !== undefined) {
    params.draftId = settings.draftId || null; // could be false
  } else if (numericData(element, 'draftId')) {
    params.draftId = numericData(element, 'draftId');
  } else if (
    settings.revisionId !== null &&
    settings.revisionId !== undefined
  ) {
    params.revisionId = settings.revisionId || null; // could be false
  } else if (numericData(element, 'revisionId')) {
    params.revisionId = numericData(element, 'revisionId');
  }

  if (settings.fieldId !== null && settings.fieldId !== undefined) {
    params.fieldId = settings.fieldId || null; // could be false
  } else if (numericData(element, 'fieldId')) {
    params.fieldId = numericData(element, 'fieldId');
  }

  if (settings.ownerId !== null && settings.ownerId !== undefined) {
    params.ownerId = settings.ownerId || null; // could be false
  } else if (numericData(element, 'ownerId')) {
    params.ownerId = numericData(element, 'ownerId');
  }

  const siteId = settings.siteId || numericData(element, 'siteId');
  if (siteId) {
    params.siteId = siteId;
  }

  if (settings.prevalidate) {
    params.prevalidate = 1;
  }

  return params;
}

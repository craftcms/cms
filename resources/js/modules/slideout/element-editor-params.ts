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

/**
 * The `elements/edit` params for an element editor: from the settings, falling
 * back to the `data-*` attributes of the element it was opened for.
 */
export function elementEditorParams(
  $element: any,
  settings: ElementEditorTarget
): ElementEditorParams {
  const params: ElementEditorParams = {};

  if (settings.elementType) {
    params.elementType = settings.elementType;
  }

  if (settings.elementId) {
    params.elementId = settings.elementId;
  } else if ($element?.data('id')) {
    params.elementId = $element.data('id');
  }

  if (settings.draftId !== null && settings.draftId !== undefined) {
    params.draftId = settings.draftId || null; // could be false
  } else if ($element?.data('draft-id')) {
    params.draftId = $element.data('draft-id');
  } else if (
    settings.revisionId !== null &&
    settings.revisionId !== undefined
  ) {
    params.revisionId = settings.revisionId || null; // could be false
  } else if ($element?.data('revision-id')) {
    params.revisionId = $element.data('revision-id');
  }

  if (settings.fieldId !== null && settings.fieldId !== undefined) {
    params.fieldId = settings.fieldId || null; // could be false
  } else if ($element?.data('field-id')) {
    params.fieldId = $element.data('field-id');
  }

  if (settings.ownerId !== null && settings.ownerId !== undefined) {
    params.ownerId = settings.ownerId || null; // could be false
  } else if ($element?.data('owner-id')) {
    params.ownerId = $element.data('owner-id');
  }

  if (settings.siteId) {
    params.siteId = settings.siteId;
  } else if ($element?.data('site-id')) {
    params.siteId = $element.data('site-id');
  }

  if (settings.prevalidate) {
    params.prevalidate = 1;
  }

  return params;
}

import EditElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/EditElementController';
import {
  openSlideout,
  type OpenSlideoutOptions,
  type SlideoutInstance,
} from '@/common/slideouts';

/** An element, as much of it as its editor needs. */
export interface EditableElement {
  id: number;
  siteId?: number | string | null;
  cpEditUrl?: string | null;
}

/**
 * Opens an element's editor in a slideout: its own edit screen, or the generic
 * one for an element type that has none.
 */
export function openElementEditor(
  element: EditableElement,
  elementType: string,
  options: OpenSlideoutOptions = {}
): Promise<SlideoutInstance | null> {
  const href =
    element.cpEditUrl ??
    EditElementController['/{cpTrigger?}/{actionTrigger?}/elements/edit'].url(
      undefined,
      {
        query: {
          elementType,
          elementId: element.id,
          siteId: element.siteId ?? null,
        },
      }
    );

  return openSlideout(href, options);
}

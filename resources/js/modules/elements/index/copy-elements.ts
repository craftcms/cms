import {copyElements as copyToClipboard} from '@/modules/elements/clipboard';
import type {ElementActionSelection} from '../types/actions';

export function copyElements(
  elementType: string,
  elementIds: ReadonlyArray<string | number>,
  selectedElements: ReadonlyArray<ElementActionSelection>
): void {
  copyToClipboard(
    elementIds.map((id) => {
      const element = selectedElements.find(
        (candidate) => String(candidate.id) === String(id)
      );
      // Card data attributes hold the element's ids
      const data = element?.cardAttributes?.data as
        | Record<string, number | null | undefined>
        | undefined;

      return {
        type: element?.type ?? elementType,
        id,
        siteId: element?.siteId ?? data?.['site-id'] ?? Craft.siteId ?? null,
        ownerId: element?.ownerId ?? data?.['owner-id'] ?? undefined,
        fieldId: element?.fieldId ?? data?.['field-id'] ?? undefined,
        draftId: element?.draftId ?? data?.['draft-id'] ?? undefined,
        revisionId: element?.revisionId ?? data?.['revision-id'] ?? undefined,
        ...(element?.data?.entryTypeId || element?.entryTypeId
          ? {
              data: {
                entryTypeId: element.data?.entryTypeId ?? element.entryTypeId!,
              },
            }
          : {}),
      };
    })
  );
}

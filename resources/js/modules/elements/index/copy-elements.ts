import type {ElementActionSelection} from '../types/actions';

export function copyElements(
  elementType: string,
  elementIds: ReadonlyArray<string | number>,
  selectedElements: ReadonlyArray<ElementActionSelection>
): void {
  window.Craft?.cp?.copyElements?.(
    elementIds.map((id) => {
      const element = selectedElements.find(
        (candidate) => String(candidate.id) === String(id)
      );
      const data = element?.cardAttributes?.data;

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

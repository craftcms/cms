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
      return {
        type: element?.type ?? elementType,
        id,
        siteId: element?.siteId ?? Craft.siteId ?? null,
      };
    })
  );
}

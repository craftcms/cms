import {ConfigService} from '@craftcms/ui/services/Config';

// [display name, plural display name, lowercase name, lowercase plural name]
type ElementTypeNames = Record<string, [string, string, string, string]>;

/** An element type's lowercase name, as used mid-sentence. */
export function elementTypeName(
  elementType: string,
  plural = false
): string | undefined {
  const names = ConfigService.getInstance().get(
    'elementTypeNames',
    {}
  ) as ElementTypeNames;

  return names[elementType]?.[plural ? 3 : 2];
}

export type PermissionItem = CraftCms.Cms.User.Data.Permission;

export function hasNested(
  item: PermissionItem
): item is PermissionItem & {nested: Record<string, PermissionItem>} {
  return (
    !!item.nested &&
    typeof item.nested === 'object' &&
    !Array.isArray(item.nested) &&
    Object.keys(item.nested).length > 0
  );
}

export function getNestedKeys(item: PermissionItem | undefined): Array<string> {
  if (!item || !hasNested(item)) {
    return [];
  }

  return Object.values(item.nested).flatMap((child: PermissionItem) => [
    child.key,
    ...getNestedKeys(child),
  ]);
}

export interface PermissionItem {
  key: string;
  nested: Record<string, PermissionItem>;
  label: string;
  info?: string;
  warning?: string;
}

export function hasNested(item: PermissionItem) {
  return (
    item.nested &&
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
    child.key.toLowerCase(),
    ...getNestedKeys(child),
  ]);
}

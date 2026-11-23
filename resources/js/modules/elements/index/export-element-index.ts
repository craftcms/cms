import {runAction} from '@craftcms/ui/actions.mjs';
import exportIndex from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ExportElementIndexController';
import type {ElementIndexExportFormat} from './types/exporters';

/**
 * Downloads an export of the elements an index lists. `params` rebuild the
 * index's query on the server; the selected IDs and the limit narrow its
 * `criteria`.
 */
export async function exportElementIndex(
  params: Record<string, unknown>,
  format: ElementIndexExportFormat,
  type: string,
  selectedIds: ReadonlyArray<string | number> = [],
  limit?: number
): Promise<void> {
  const criteria =
    params.criteria instanceof Object
      ? (params.criteria as Record<string, unknown>)
      : {};

  await runAction({
    type: 'download',
    method: 'POST',
    url: exportIndex.url(),
    body: {
      ...params,
      criteria: {
        ...criteria,
        ...(selectedIds.length ? {id: [...selectedIds]} : {}),
        ...(limit ? {limit} : {}),
      },
      type,
      format,
    },
  });
}

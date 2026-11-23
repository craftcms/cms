import {useElementQuickEdit} from '@/modules/elements/composables/useElementQuickEdit';
import type {BulkActionEventDetail} from '@/modules/elements/types/actions';
import type {ElementIndexRow} from './useContentIndexData';

/**
 * Opens an index's elements: double-clicking a row or the bulk actions bar's
 * Edit button edits it in a slideout, and its View button opens it on the
 * front end. Both buttons act on a single selected row.
 */
export function useElementRowActions(options: {
  rows: () => ReadonlyArray<ElementIndexRow>;
  refresh: () => void;
}) {
  const quickEdit = useElementQuickEdit({refreshResults: options.refresh});

  function actionRow(detail: BulkActionEventDetail): ElementIndexRow | null {
    if (detail.elementIds.length !== 1) {
      return null;
    }

    const id = String(detail.elementIds[0]);

    return options.rows().find((row) => String(row.id) === id) ?? null;
  }

  function editElement(detail: BulkActionEventDetail): void {
    const url = actionRow(detail)?.cpEditUrl;

    if (url) {
      quickEdit.openEditor(url, detail.trigger);
    }
  }

  function viewElement(detail: BulkActionEventDetail): void {
    const url = actionRow(detail)?.viewUrl;

    if (url) {
      window.open(url, '_blank', 'noopener');
    }
  }

  return {quickEdit, editElement, viewElement};
}

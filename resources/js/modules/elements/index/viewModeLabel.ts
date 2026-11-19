import {t} from '@craftcms/ui';
import type {ViewMode} from '@/modules/elements/types/view-state';

/** A view mode's short name; its own title describes it as a sentence. */
export function viewModeLabel(viewMode: ViewMode): string {
  switch (viewMode.mode) {
    case 'table':
      return t('Table');
    case 'cards':
      return t('Cards');
    case 'structure':
      return t('Structure');
    case 'thumbs':
      return t('Thumbnails');
    default:
      return viewMode.title;
  }
}

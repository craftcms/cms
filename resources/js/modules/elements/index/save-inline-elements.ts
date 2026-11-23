import {actionClient, t, isHttpError} from '@craftcms/ui';
import SaveElementIndexElementsController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/SaveElementIndexElementsController';
import type {InlineEditingSaveResult} from './composables/useInlineEditing';
import {showError} from '@/modules/messages/messages';
import {paramPairs} from '@/common/craft/params';

export async function saveInlineElements(
  body: URLSearchParams,
  params: Record<string, unknown>
): Promise<InlineEditingSaveResult | false> {
  // Nested params get bracketed keys; a list's items share one key, so
  // its existing values are replaced before they're appended.
  const pairs = paramPairs({...params, namespace: 'inline'}).filter(
    ([, value]) => value !== null && value !== undefined
  );

  for (const [key] of pairs) {
    body.delete(key);
  }

  for (const [key, value] of pairs) {
    body.append(key, String(value));
  }

  try {
    const {data} = await actionClient.post<InlineEditingSaveResult>(
      SaveElementIndexElementsController.url(),
      body
    );

    return data;
  } catch (cause) {
    const message = isHttpError<{message?: string}>(cause)
      ? cause.response?.data?.message
      : cause instanceof Error
        ? cause.message
        : null;
    showError(message || t('Couldn’t save.'));

    return false;
  }
}

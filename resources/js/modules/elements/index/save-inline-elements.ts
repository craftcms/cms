import {actionClient, t, isHttpError} from '@craftcms/ui';
import SaveElementIndexElementsController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/SaveElementIndexElementsController';
import type {InlineEditingSaveResult} from './composables/useInlineEditing';

export async function saveInlineElements(
  body: URLSearchParams,
  params: Record<string, unknown>
): Promise<InlineEditingSaveResult | false> {
  for (const [key, value] of Object.entries({...params, namespace: 'inline'})) {
    if (value !== null && value !== undefined) {
      body.set(key, String(value));
    }
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
    window.Craft?.cp?.displayError?.(message || t('Couldn’t save.'));

    return false;
  }
}

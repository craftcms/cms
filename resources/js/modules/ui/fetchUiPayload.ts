import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import type {UiPayload} from './types';

/**
 * Posts for a settings UI, failing when the response carries none. The rest
 * of the response, such as head and body HTML, comes back alongside it.
 */
export async function fetchUiPayload<
  Extra extends Record<string, unknown> = Record<string, never>,
>(
  url: string,
  body: Record<string, unknown>,
  missing = 'The server did not return a UI payload.'
): Promise<{ui: UiPayload} & Extra> {
  const {data} = await actionClient.post<{ui?: UiPayload} & Extra>(url, body);

  if (!data.ui) {
    throw new Error(missing);
  }

  return data as {ui: UiPayload} & Extra;
}

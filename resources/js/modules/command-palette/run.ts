import {actionClient, t, type BaseAction} from '@craftcms/ui';
import {MESSAGE_EVENT} from '@/modules/messages';
import {pageKey} from './recents';
import type {CommandPaletteItem, CommandPaletteViewResult} from './types';

export type HttpAction = Extract<BaseAction, {type: 'http'}>;

export interface HttpActionResult {
  redirect?: string;
}

/** Shows the messages an action's response carries, as `RespondsWithFlash` sends them. */
function showMessages(data: Record<string, unknown>): void {
  const messages = Array.isArray(data.messages) ? data.messages : [];

  for (const message of messages) {
    window.dispatchEvent(new CustomEvent(MESSAGE_EVENT, {detail: message}));
  }

  if (!messages.length && typeof data.message === 'string') {
    Craft.cp?.displayNotice?.(data.message);
  }
}

/**
 * Sends an `http` action's request, through `actionClient` so it carries the
 * CSRF token and control panel headers, and shows the messages it responds
 * with. Rejects, after showing the error, if the request fails.
 *
 * Its `confirm` message is ignored: the palette asks for confirmation itself,
 * inside the palette, before calling this.
 */
export async function runHttpAction(
  action: HttpAction
): Promise<HttpActionResult> {
  try {
    const {data} = await actionClient.request<Record<string, unknown>>({
      url: action.url,
      method: action.method ?? 'POST',
      data: action.body,
    });
    const body = data ?? {};
    showMessages(body);

    return {
      redirect: typeof body.redirect === 'string' ? body.redirect : undefined,
    };
  } catch (error: any) {
    Craft.cp?.displayError?.(
      error?.response?.data?.message ?? t('A server error occurred.')
    );

    throw error;
  }
}

/**
 * Where to go once an item's action has succeeded: wherever the response
 * said, or else away from the page being viewed, if the action removed it.
 * `replace` is set when it did, so the removed page leaves the history too.
 */
export function redirectAfter(
  item: CommandPaletteItem,
  redirect: string | undefined
): Pick<CommandPaletteViewResult, 'redirect' | 'replace'> {
  const removedThisPage = Boolean(
    item.removedPage &&
    pageKey(item.removedPage) === pageKey(window.location.href)
  );

  return {
    redirect:
      redirect ??
      (removedThisPage ? (item.removedPageRedirect ?? undefined) : undefined),
    replace: removedThisPage,
  };
}

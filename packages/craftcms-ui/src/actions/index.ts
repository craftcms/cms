import type {VariantKey} from '@src/constants/variants';
import {actionClient} from '@src/utilities/api/actionClient';

export type BaseAction =
  | {type: 'clipboard'; value: string}
  | {
      type: 'http';
      method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
      url: string;
      body?: Record<string, unknown>;
      confirm?: string;
    }
  | {
      type: 'event';
      name: string;
      detail?: Record<string, unknown>;
      confirm?: string;
    }
  | {
      type: 'download';
      url: string;
      filename?: string;
      /**
       * When set to a non-GET verb, the file is fetched as a blob through
       * `actionClient` (resolving the action URL + CP headers) rather than a
       * plain anchor navigation — needed for POST actions like recovery-code
       * downloads that sit behind an elevated session.
       */
      method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
      body?: Record<string, unknown>;
      confirm?: string;
    };

export type FeedbackData = {
  message?: string;
  // @TODO
  // display?: 'inline' | 'toast';
};

export type ActionFeedback = {
  loading?: FeedbackData;
  success?: FeedbackData;
  error?: FeedbackData;
};

export interface BaseActionItem {
  type?: 'button' | 'link' | 'hr';
  icon?: string;
  label: string;
}

export interface ActionItemLink {
  type: 'link';
  href: string;
  disabled?: boolean;
}

export interface ActionItemHr {
  type: 'hr';
}

export interface ActionItemButton extends BaseActionItem {
  type?: 'button';
  action: BaseAction;
  feedback?: ActionFeedback; // same feedback shape as before
  disabled?: boolean;
  variant?: VariantKey | string;
}

export type ActionItem = ActionItemButton | ActionItemHr | ActionItemLink;

export interface RunActionOptions {
  /**
   * The element that invoked the action (e.g. the clicked button). For
   * `event` actions it's merged into the dispatched event's `detail` as
   * `trigger`, so listeners can manage focus or read contextual `data-*`
   * attributes from the invoker's ancestors.
   */
  trigger?: Element;
  /**
   * The DOM event that invoked the action. For `event` actions it's merged
   * into the dispatched event's `detail` as `sourceEvent`, so listeners can
   * inspect modifier keys (e.g. ctrl-click opening a new window).
   */
  sourceEvent?: Event;
}

/**
 * Normalizes an action property value to a `BaseAction`. Vue's in-DOM
 * template compiler (e.g. server HTML rendered through `DynamicHtmlRenderer`)
 * assigns attribute values directly to an upgraded element's property as a
 * raw JSON string, bypassing Lit's attribute converter — so components accept
 * both and normalize before running.
 */
export function normalizeAction(
  action: BaseAction | string | null
): BaseAction | null {
  if (typeof action !== 'string') {
    return action;
  }

  try {
    return JSON.parse(action);
  } catch {
    return null;
  }
}

export async function runAction(
  action: BaseAction,
  options: RunActionOptions = {}
): Promise<void> {
  switch (action.type) {
    case 'clipboard':
      await navigator.clipboard.writeText(action.value);
      break;

    case 'http': {
      if (action.confirm) {
        if (!confirm(action.confirm)) {
          return;
        }
      }

      const response = await fetch(action.url, {
        method: action.method || 'POST',
        headers: {
          'Content-Type': 'application/json',
          // Ask for a JSON response so the server returns a redirect URL in the
          // body rather than a 302 (which `fetch` would silently follow without
          // navigating the browser).
          Accept: 'application/json',
        },
        body: action.body ? JSON.stringify(action.body) : undefined,
      });

      const data = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(data.message ?? 'Request failed');
      }

      if (typeof data.redirect === 'string' && data.redirect) {
        navigateTo(data.redirect);
      }

      break;
    }

    case 'event':
      if (action.confirm) {
        if (!confirm(action.confirm)) {
          return;
        }
      }

      window.dispatchEvent(
        new CustomEvent(action.name, {
          detail: {
            ...(action.detail ?? {}),
            ...(options.trigger ? {trigger: options.trigger} : {}),
            ...(options.sourceEvent ? {sourceEvent: options.sourceEvent} : {}),
          },
        })
      );
      break;

    case 'download': {
      if (action.confirm && !confirm(action.confirm)) {
        return;
      }

      // A non-GET download can't be a simple anchor navigation: fetch the file
      // as a blob (through actionClient, so the action URL + CP headers resolve)
      // and hand the browser an object URL to save.
      if (action.method && action.method !== 'GET') {
        const response = await actionClient.request({
          url: action.url,
          method: action.method,
          data: action.body,
          responseType: 'blob',
        });

        const disposition = String(
          response.headers['content-disposition'] ?? ''
        );
        const match = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(disposition);
        const filename =
          action.filename ??
          (match?.[1] ? decodeURIComponent(match[1]) : 'download');

        const objectUrl = URL.createObjectURL(response.data as Blob);
        const link = document.createElement('a');
        link.href = objectUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(objectUrl);
        break;
      }

      const a = document.createElement('a');
      a.href = action.url;
      a.download = action.filename ?? '';
      a.click();
      break;
    }

    default: {
      const unhandled: never = action;
      throw new Error(`Unknown action type: ${(unhandled as BaseAction).type}`);
    }
  }
}

/**
 * Navigate to `url` after an action. Framework-agnostic: dispatches a cancelable
 * `action:redirect` event so a host (e.g. an Inertia/SPA layer) can
 * `preventDefault()` and route its own way. If nothing handles it, falls back to
 * a full-page `window.location` navigation — so this works with no host wiring.
 */
function navigateTo(url: string): void {
  const proceed = window.dispatchEvent(
    new CustomEvent('action:redirect', {cancelable: true, detail: {url}})
  );

  if (proceed) {
    window.location.assign(url);
  }
}

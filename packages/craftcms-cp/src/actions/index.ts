import type {VariantKey} from '@src/types';

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
  | {type: 'navigate'; url: string; target?: '_self' | '_blank'}
  | {type: 'download'; url: string; filename?: string};

export type FeedbackData = {
  message?: string;
  display?: 'inline' | 'toast';
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

export async function runAction(action: BaseAction): Promise<void> {
  switch (action.type) {
    case 'clipboard':
      await navigator.clipboard.writeText(action.value);
      break;

    case 'http':
      if (action.confirm) {
        if (!confirm(action.confirm)) {
          return;
        }
      }

      const response = await fetch(action.url, {
        method: action.method || 'POST',
        headers: {'Content-Type': 'application/json'},
        body: action.body ? JSON.stringify(action.body) : undefined,
      });

      if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.message ?? 'Request failed');
      }

      break;

    case 'event':
      if (action.confirm) {
        if (!confirm(action.confirm)) {
          return;
        }
      }

      window.dispatchEvent(
        new CustomEvent(action.name, {detail: action.detail ?? {}})
      );
      break;

    case 'download':
      const a = document.createElement('a');
      a.href = action.url;
      a.download = action.filename ?? '';
      a.click();
      break;

    default:
      console.warn(`TODO: ${action.type} action`, action);
      throw new Error(`Unknown action type: ${action.type}`);
      break;
  }
}

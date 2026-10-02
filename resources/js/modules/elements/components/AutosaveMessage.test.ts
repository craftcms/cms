import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import type {AutosaveStatus} from '@/modules/elements/composables/useElementAutosave';
import AutosaveMessage from './AutosaveMessage.vue';

vi.mock('@craftcms/ui', () => ({
  t: (message: string, params: Record<string, string> = {}) =>
    message.replace(/\{(\w+)\}/g, (_, key) => params[key] ?? ''),
}));

let app: App | undefined;
let container: HTMLElement | undefined;

function mount(props: {
  status: AutosaveStatus;
  savedAt?: string | null;
  error?: string | null;
  httpStatus?: number | null;
  onRefresh?: () => void;
}): HTMLElement {
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({render: () => h(AutosaveMessage, props)});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  return container;
}

afterEach(() => {
  app?.unmount();
  container?.remove();
  app = container = undefined;
});

describe('AutosaveMessage', () => {
  it('renders nothing while idle', () => {
    expect(mount({status: 'idle'}).textContent?.trim()).toBe('');
  });

  it('reports a save with its time', () => {
    const element = mount({status: 'saved', savedAt: '10:18 AM'});

    expect(element.textContent).toContain('Saved 10:18 AM');
    expect(
      element.querySelector('craft-callout')!.getAttribute('variant')
    ).toBe('success');
  });

  it('shows the failure and its status code', () => {
    const element = mount({status: 'failed', httpStatus: 500});

    expect(element.textContent).toContain('Couldn’t save draft.');
    expect(element.querySelector('code')!.textContent).toBe('500');
    expect(element.querySelector('craft-button')).toBeNull();
  });

  it('offers a refresh when the session has expired', () => {
    const onRefresh = vi.fn();
    const element = mount({status: 'failed', httpStatus: 400, onRefresh});

    element.querySelector<HTMLElement>('craft-button')!.click();

    expect(onRefresh).toHaveBeenCalledOnce();
  });
});

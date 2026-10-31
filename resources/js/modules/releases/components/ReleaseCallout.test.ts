import {createApp, h} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {AttachedRelease} from '@/modules/elements/composables/useElementEditor';
import ReleaseCallout from './ReleaseCallout.vue';

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

function mount(
  release: Partial<AttachedRelease>,
  {
    locked = false,
    onDismiss = () => {},
    onStartEditing = () => {},
  }: {
    locked?: boolean;
    onDismiss?: () => void;
    onStartEditing?: () => void;
  } = {}
): void {
  app = createApp({
    render: () =>
      h(ReleaseCallout, {
        release: {
          id: 4,
          name: 'Launch',
          status: 'draft',
          targetDate: null,
          canManage: true,
          draftId: 7,
          siteId: 1,
          blocking: false,
          ...release,
        } as AttachedRelease,
        locked,
        canStartEditing: true,
        onDismiss,
        onStartEditing,
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
}

it.each([
  ['draft', 'This draft belongs to “Launch & <b>more</b>”.'],
  [
    'scheduled',
    'This draft belongs to the scheduled release “Launch & <b>more</b>”.',
  ],
] as const)(
  'links the name of the %s release the draft belongs to',
  (status, text) => {
    mount({status, name: 'Launch & <b>more</b>'});

    const link = container.querySelector('a')!;
    expect(link.getAttribute('href')).toBe('/admin/releases/4');
    expect(link.textContent).toBe('Launch & <b>more</b>');
    expect(container.querySelector('b')).toBeNull();
    expect(container.textContent).toContain(text);
  }
);

it.each([
  [
    'draft',
    false,
    'info',
    'This draft belongs to “Launch”. Saved changes will be included when the release publishes.',
  ],
  [
    'draft',
    true,
    'warning',
    'This draft is blocking “Launch”, which can’t be published until the draft is ready.',
  ],
  [
    'scheduled',
    true,
    'warning',
    'This draft is blocking the scheduled release “Launch”, which will fail to publish until the draft is ready.',
  ],
] as const)(
  'describes a %s release (blocking: %s) as %s',
  (status, blocking, variant, text) => {
    mount({status, blocking});

    const callout = container.querySelector<HTMLElement & {variant?: string}>(
      'craft-callout'
    )!;
    expect(callout.variant).toBe(variant);
    expect(callout.textContent).toContain(text);
  }
);

it('offers a dismiss button instead of Start editing while unlocked', () => {
  const onDismiss = vi.fn();
  mount({}, {onDismiss});

  const buttons = container.querySelectorAll('craft-button');
  expect(buttons).toHaveLength(1);
  expect(container.textContent).not.toContain('Start editing');
  expect(buttons[0]!.getAttribute('aria-label')).toBe('Dismiss');

  (buttons[0] as HTMLElement).click();
  expect(onDismiss).toHaveBeenCalledOnce();
});

it('locks a scheduled release draft behind Start editing, without a dismiss button', () => {
  const onStartEditing = vi.fn();
  mount({status: 'scheduled'}, {locked: true, onStartEditing});

  const callout = container.querySelector<HTMLElement & {variant?: string}>(
    'craft-callout'
  )!;
  expect(callout.variant).toBe('info');
  expect(callout.textContent).toContain(
    'This draft is scheduled to publish with “Launch”. If edits leave it unready, the release will fail to publish.'
  );

  const buttons = container.querySelectorAll<HTMLElement>('craft-button');
  expect(buttons).toHaveLength(1);
  expect(buttons[0]!.textContent?.trim()).toBe('Start editing');

  buttons[0]!.click();
  expect(onStartEditing).toHaveBeenCalledOnce();
});

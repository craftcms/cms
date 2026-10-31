import {createApp, h} from 'vue';
import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
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

function mount(release: Partial<AttachedRelease>): void {
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
          ...release,
        } as AttachedRelease,
        canEdit: true,
        editing: true,
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

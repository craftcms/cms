import {router} from '@inertiajs/vue3';
import {createApp, defineComponent, type ComputedRef} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {openSlideout} from '@/common/slideouts';
import type {ActionItem} from '@/common/types';
import {
  createElementActionMenu,
  useElementActionMenu,
  type ElementActionMenuItem,
} from './useElementActionMenu';

vi.mock('@/common/slideouts', () => ({openSlideout: vi.fn()}));

describe('useElementActionMenu', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;

  beforeEach(() => {
    window.Craft = Object.assign(Object.create(null), {
      csrfTokenName: 'CRAFT_CSRF_TOKEN',
      csrfTokenValue: 'token-value',
      elevatedSessionManager: {
        // Stands in for the password prompt: run the guarded work straight away.
        requireElevatedSession: vi.fn((onSuccess: () => void) => onSuccess()),
      },
      cp: {displayError: vi.fn()},
    });
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
    delete window.Craft;
  });

  function mount(
    items: Array<ElementActionMenuItem>
  ): ComputedRef<Array<ActionItem>> {
    let menu!: ComputedRef<Array<ActionItem>>;

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(
      defineComponent({
        setup() {
          menu = useElementActionMenu(() => items);

          return () => null;
        },
      })
    );
    app.mount(container);

    return menu;
  }

  /** Clicks the menu's only item. Every case here builds exactly one. */
  function activate(menu: ComputedRef<Array<ActionItem>>): void {
    const item = menu.value[0];
    if (!item || !('onClick' in item)) {
      throw new Error('Expected one button action.');
    }
    item.onClick?.(new Event('click'));
  }

  it('opens a slideout behavior in a Vue slideout', () => {
    activate(
      mount([
        {
          label: 'Section settings',
          behavior: {type: 'slideout', url: '/admin/settings/sections/1'},
        },
      ])
    );

    expect(openSlideout).toHaveBeenCalledWith('/admin/settings/sections/1');
  });

  it("opens the sidebar's entry type rather than the saved one", () => {
    window.Craft!.getCpUrl = vi.fn((path: string) => `/admin/${path}`);
    const toActionItems = createElementActionMenu({
      currentEntryTypeId: () => 7,
    });
    const [item] = toActionItems([
      {
        label: 'Entry type settings',
        behavior: {
          type: 'slideout',
          url: '/admin/settings/entry-types/3',
          entryTypeFromField: true,
        },
      },
    ]);

    if (!item || !('onClick' in item)) {
      throw new Error('Expected a button action.');
    }
    item.onClick?.(new MouseEvent('click'));

    expect(openSlideout).toHaveBeenCalledWith('/admin/settings/entry-types/7');
  });

  it('posts a submit behavior with its params and redirect', () => {
    const post = vi.spyOn(router, 'post').mockImplementation(() => undefined);

    const menu = mount([
      {
        label: 'Activate account',
        behavior: {
          type: 'submit',
          actionUrl: '/actions/users/activate-user',
          params: {userId: 5},
          redirect: 'encrypted',
        },
      },
    ]);

    activate(menu);

    expect(post).toHaveBeenCalledWith('/actions/users/activate-user', {
      userId: 5,
      redirect: 'encrypted',
    });
  });

  it('stops a submit behavior when its confirmation is dismissed', () => {
    const post = vi.spyOn(router, 'post').mockImplementation(() => undefined);
    // happy-dom leaves `window.confirm` undefined, so there's nothing to spy on.
    vi.stubGlobal(
      'confirm',
      vi.fn(() => false)
    );

    const menu = mount([
      {
        label: 'Deactivate',
        behavior: {
          type: 'submit',
          actionUrl: '/actions/users/deactivate-user',
          confirm: 'Are you sure?',
        },
      },
    ]);

    activate(menu);

    expect(post).not.toHaveBeenCalled();
  });

  it('re-authenticates before a submit behavior that asks for it', () => {
    const post = vi.spyOn(router, 'post').mockImplementation(() => undefined);

    const menu = mount([
      {
        label: 'Sign in as user',
        behavior: {
          type: 'submit',
          actionUrl: '/actions/users/impersonate',
          params: {userId: 5},
          requireElevatedSession: true,
        },
      },
    ]);

    activate(menu);

    expect(
      window.Craft.elevatedSessionManager.requireElevatedSession
    ).toHaveBeenCalled();
    expect(post).toHaveBeenCalledWith('/actions/users/impersonate', {
      userId: 5,
    });
  });

  it('submits a download as a real form, so the browser handles the file', () => {
    const submit = vi
      .spyOn(HTMLFormElement.prototype, 'submit')
      .mockImplementation(function (this: HTMLFormElement) {
        // The form removes itself right after submitting, so the fields are
        // read here rather than from the document afterwards.
        fields = Object.fromEntries(
          [...this.querySelectorAll('input')].map((input) => [
            input.name,
            input.value,
          ])
        );
        action = this.action;
      });

    let fields: Record<string, string> = {};
    let action = '';

    const menu = mount([
      {
        label: 'Download',
        behavior: {
          type: 'download',
          actionUrl: 'https://example.test/actions/assets/download-asset',
          params: {assetId: 7},
        },
      },
    ]);

    activate(menu);

    expect(submit).toHaveBeenCalled();
    expect(action).toBe('https://example.test/actions/assets/download-asset');
    expect(fields).toEqual({
      CRAFT_CSRF_TOKEN: 'token-value',
      assetId: '7',
    });
    expect(document.querySelector('form')).toBeNull();
  });
});

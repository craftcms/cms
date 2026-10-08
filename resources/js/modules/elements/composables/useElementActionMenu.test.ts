import {router} from '@inertiajs/vue3';
import {createApp, defineComponent, type ComputedRef} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {openSlideout} from '@/common/slideouts';
import type {ActionItem} from '@/common/types';
import {
  openImageEditorDialog,
  type ImageEditorSettings,
} from '@/modules/image-editor/open-image-editor-dialog';
import {openUiModal} from '@/modules/ui/open-ui-modal';
import {
  createElementActionMenu,
  useElementActionMenu,
  type ElementActionMenuItem,
} from './useElementActionMenu';

vi.mock('@/common/slideouts', () => ({openSlideout: vi.fn()}));
vi.mock('@/modules/image-editor/open-image-editor-dialog', () => ({
  openImageEditorDialog: vi.fn(),
}));
vi.mock('@/modules/ui/open-ui-modal', () => ({openUiModal: vi.fn()}));

const {actionPost, deletionManagers} = vi.hoisted(() => ({
  actionPost: vi.fn(),
  deletionManagers: [] as Array<{settings: {onSuccess: () => void}}>,
}));

vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal<Record<string, unknown>>()),
  actionClient: {post: actionPost},
}));

vi.mock('@/modules/element-deletion-manager', () => ({
  ElementDeletionManager: vi.fn(function (
    this: unknown,
    _type: string,
    _ids: number[],
    settings: {onSuccess: () => void}
  ) {
    deletionManagers.push({settings});
  }),
}));

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

  it('opens a formModal behavior and reloads the page once it’s submitted', () => {
    const reload = vi
      .spyOn(router, 'reload')
      .mockImplementation(() => undefined);

    activate(
      mount([
        {
          label: 'Receive',
          behavior: {
            type: 'formModal',
            modalUrl: 'things/receive-modal',
            actionUrl: 'things/receive',
            params: {thingId: 4},
          },
        },
      ])
    );

    expect(openUiModal).toHaveBeenCalledWith({
      modalUrl: 'things/receive-modal',
      actionUrl: 'things/receive',
      params: {thingId: 4},
      onSubmitted: expect.any(Function),
    });
    expect(reload).not.toHaveBeenCalled();

    vi.mocked(openUiModal).mock.lastCall![0].onSubmitted!({});

    expect(reload).toHaveBeenCalled();
  });

  describe('editImage', () => {
    const settings: ImageEditorSettings = {
      assetId: 7,
      filename: 'photo.jpg',
      focalPoint: null,
      imageWidth: 800,
      imageHeight: 600,
      imageEditorRatios: {Square: 1},
      allowDegreeFractions: false,
      orientation: 'ltr',
    };

    function openEditor(): (result: {newAssetId?: number}) => void {
      activate(
        mount([
          {
            label: 'Open in Image Editor',
            behavior: {type: 'editImage', assetId: 7, settings},
          },
        ])
      );

      expect(openImageEditorDialog).toHaveBeenCalledWith(
        settings,
        expect.any(Function)
      );

      return vi.mocked(openImageEditorDialog).mock.lastCall![1]!;
    }

    it('reloads the page once the image is saved in place', () => {
      const reload = vi
        .spyOn(router, 'reload')
        .mockImplementation(() => undefined);

      openEditor()({});

      expect(reload).toHaveBeenCalled();
    });

    it('leaves the page alone when the image is saved as a new asset', () => {
      const reload = vi
        .spyOn(router, 'reload')
        .mockImplementation(() => undefined);

      openEditor()({newAssetId: 8});

      expect(reload).not.toHaveBeenCalled();
    });
  });

  describe('inside a slideout', () => {
    function slideoutController() {
      return {
        instance: {id: 'slideout-1', containerId: 'container-1'},
        close: vi.fn(),
        reload: vi.fn().mockResolvedValue(undefined),
        saved: vi.fn().mockReturnValue(true),
      };
    }

    function activateIn(
      slideout: ReturnType<typeof slideoutController>,
      item: ElementActionMenuItem
    ): void {
      const [action] = createElementActionMenu({slideout: slideout as never})([
        item,
      ]);
      if (!action || !('onClick' in action)) {
        throw new Error('Expected a button action.');
      }
      action.onClick?.(new MouseEvent('click'));
    }

    beforeEach(() => {
      actionPost.mockReset();
      deletionManagers.length = 0;
      window.Craft!.cp = {
        displayError: vi.fn(),
        displayNotice: vi.fn(),
      } as never;
    });

    it('submits without visiting, then reloads the panel', async () => {
      const visit = vi
        .spyOn(router, 'post')
        .mockImplementation(() => undefined);
      actionPost.mockResolvedValue({data: {message: 'Entry is valid.'}});
      const slideout = slideoutController();

      activateIn(slideout, {
        label: 'Validate entry',
        behavior: {
          type: 'submit',
          actionUrl: '/actions/elements/validate',
          params: {elementId: 5},
          redirect: 'encrypted',
        },
      });

      await vi.waitFor(() => expect(slideout.reload).toHaveBeenCalledOnce());
      expect(visit).not.toHaveBeenCalled();
      expect(actionPost).toHaveBeenCalledWith('/actions/elements/validate', {
        elementId: 5,
      });
      expect(window.Craft!.cp!.displayNotice).toHaveBeenCalledWith(
        'Entry is valid.'
      );
      expect(slideout.saved).toHaveBeenCalledWith({
        draft: true,
        data: {message: 'Entry is valid.'},
      });
      expect(slideout.close).not.toHaveBeenCalled();
    });

    it('closes the panel after a destructive submission', async () => {
      actionPost.mockResolvedValue({data: {}});
      const slideout = slideoutController();

      activateIn(slideout, {
        label: 'Delete draft',
        destructive: true,
        behavior: {
          type: 'submit',
          actionUrl: '/actions/elements/delete-draft',
          params: {draftId: 3},
        },
      });

      await vi.waitFor(() =>
        expect(slideout.close).toHaveBeenCalledWith({force: true})
      );
      expect(slideout.saved).toHaveBeenCalledWith({data: {}});
      expect(slideout.reload).not.toHaveBeenCalled();
    });

    it('reports a failed submission without touching the panel', async () => {
      actionPost.mockRejectedValue({
        response: {data: {message: 'Couldn’t validate entry.'}},
      });
      const slideout = slideoutController();

      activateIn(slideout, {
        label: 'Validate entry',
        behavior: {type: 'submit', actionUrl: '/actions/elements/validate'},
      });

      await vi.waitFor(() =>
        expect(window.Craft!.cp!.displayError).toHaveBeenCalledWith(
          'Couldn’t validate entry.'
        )
      );
      expect(slideout.saved).not.toHaveBeenCalled();
      expect(slideout.reload).not.toHaveBeenCalled();
    });

    it('closes the panel rather than navigating after a deletion', () => {
      const visit = vi
        .spyOn(router, 'visit')
        .mockImplementation(() => undefined);
      const slideout = slideoutController();

      activateIn(slideout, {
        label: 'Delete entry',
        destructive: true,
        behavior: {
          type: 'delete',
          elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
          elementId: 5,
          siteId: 1,
          confirm: 'Are you sure?',
          redirect: '/admin/entries',
        },
      });
      deletionManagers[0]!.settings.onSuccess();

      expect(slideout.saved).toHaveBeenCalledOnce();
      expect(slideout.close).toHaveBeenCalledWith({force: true});
      expect(visit).not.toHaveBeenCalled();
    });

    it('reloads the panel once an image is saved in place', () => {
      const reload = vi
        .spyOn(router, 'reload')
        .mockImplementation(() => undefined);
      const slideout = slideoutController();

      activateIn(slideout, {
        label: 'Open in Image Editor',
        behavior: {
          type: 'editImage',
          assetId: 7,
          settings: {} as ImageEditorSettings,
        },
      });
      vi.mocked(openImageEditorDialog).mock.lastCall![1]!({});

      expect(slideout.reload).toHaveBeenCalledOnce();
      expect(reload).not.toHaveBeenCalled();
    });
  });
});

import {createApp, h, nextTick, provide, reactive, shallowRef} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {useAnnouncer} from '@/common/composables/useAnnouncer';
import type {Selectable} from '@/common/composables/useSelectable';
import type {ActionItemButton} from '@/common/types';
import {
  NestedOwnerEditorKey,
  type NestedOwnerContext,
} from '@/modules/elements/nested-owner';
import type {FormControlPayload} from '../types';
import type {NestedEntriesProps, NestedEntry} from './nested-entries';
import NestedEntriesControl from './NestedEntriesControl.vue';
import {
  DELETE_ACTION,
  nestedElementAction,
  standardNestedActions,
} from './nested-index.fixture';

const request = vi.hoisted(() => ({post: vi.fn()}));
const actions = vi.hoisted(() => ({run: vi.fn(async () => {})}));
const slideout = vi.hoisted(() => vi.fn().mockResolvedValue(null));
vi.mock('@/common/slideouts', () => ({
  canUseVueSlideout: () => window.Craft?.openSlideout instanceof Function,
  openSlideout: slideout,
}));
const copiedElements = vi.hoisted(() => ({
  value: [] as Array<{type: string; data?: Record<string, unknown>}>,
}));
vi.mock('@/modules/matrix/copied-elements', () => ({
  useCopiedElements: () => shallowRef(copiedElements.value),
}));
vi.mock('@craftcms/ui', async (original) => ({
  ...(await original<Record<string, unknown>>()),
  actionClient: request,
}));
vi.mock('@craftcms/ui/actions.mjs', () => ({runAction: actions.run}));
vi.mock('@/modules/elements/components/ElementCards.vue', () => ({
  default: {
    props: ['data', 'selection', 'itemBehavior', 'selectable', 'readOnly'],
    emits: ['reorder'],
    setup(
      props: {
        data: Array<{
          id: number;
          cardHeaderHtml: string;
          editUrl?: string;
          cardAttributes?: {class?: string};
        }>;
        selection: Selectable<number>;
        selectable: boolean;
        readOnly: boolean;
        itemBehavior?: {
          attrs: (card: {id: number}) => Record<string, unknown>;
        };
      },
      {slots}: {slots: Record<string, (data: unknown) => unknown>}
    ) {
      return () =>
        h(
          'ul',
          props.data.map((card) =>
            h(
              'li',
              {
                key: card.id,
                class: card.cardAttributes?.class,
                ...props.itemBehavior?.attrs(card),
              },
              [
                props.selectable && !props.readOnly
                  ? h('button', {
                      type: 'button',
                      'aria-label': `Select ${card.id}`,
                      onClick: () => props.selection.toggle(card.id),
                    })
                  : null,
                card.editUrl
                  ? h('a', {href: card.editUrl}, `Edit ${card.id}`)
                  : null,
                h('span', {innerHTML: card.cardHeaderHtml}),
                slots.actions?.({element: card}) as never,
              ]
            )
          )
        );
    },
  },
}));
describe('NestedEntriesControl', () => {
  let root: HTMLElement;
  let app: ReturnType<typeof createApp>;

  afterEach(() => {
    app?.unmount();
    root?.remove();
    request.post.mockReset();
    actions.run.mockClear();
    slideout.mockClear();
    copiedElements.value = [];
    vi.useRealTimers();
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
  });

  function menuAction(
    elementId: number,
    action: string,
    label: string
  ): ActionItemButton {
    const item = standardNestedActions.find((candidate) =>
      candidate.key.endsWith(
        action === 'copy'
          ? '\\Copy'
          : action === 'duplicate'
            ? '\\Duplicate'
            : '\\Delete'
      )
    )!;

    return nestedElementAction(elementId, item, label) as ActionItemButton;
  }

  function nestedEntry(
    entry: Pick<NestedEntry, 'id'> & Partial<NestedEntry>
  ): NestedEntry {
    return {
      siteId: null,
      entryTypeId: null,
      ownerId: null,
      capabilities: {
        copyable: true,
        duplicatable: true,
        deletable: true,
      },
      ownerIsCanonical: true,
      isUnpublishedDraft: false,
      ownerIsUnpublishedDraft: false,
      primaryOwnerId: null,
      isCanonical: true,
      editUrl: null,
      cpEditUrl: null,
      actionMenuItems: [],
      cardAttributes: {},
      cardHeaderHtml: '',
      cardActionsHtml: '',
      cardContentHtml: '',
      cardFooterHtml: '',
      cardThumbHtml: '',
      thumbAlignment: 'end',
      ...entry,
    };
  }

  function mount(
    options: {
      prepare?: () => Promise<NestedOwnerContext | null>;
      refresh?: () => Promise<void>;
      editable?: boolean;
      vueSlideout?: boolean;
      cards?: NestedEntry[];
      manager?: Partial<NonNullable<NestedEntriesProps['manager']>>;
    } = {}
  ) {
    const legacyElementEditor = {
      settings: {
        draftId: 6,
        saveParams: null as Record<string, unknown> | null,
      },
      saveDraft: vi.fn(async () => {}),
    };
    const legacySlideout = {
      elementEditor: legacyElementEditor,
      on: vi.fn(),
    };
    const createElementEditor = vi.fn(
      (
        _elementType: string,
        _settings: Record<string, unknown> & {
          onBeforeSubmit: () => Promise<void>;
        }
      ) => legacySlideout
    );
    vi.stubGlobal('Craft', {
      openSlideout: options.vueSlideout === false ? undefined : vi.fn(),
      createElementEditor,
      cp: {displayNotice: vi.fn()},
      elementTypeNames: {Entry: ['Entry', 'Entries', 'entry', 'entries']},
      Preview: {refresh: vi.fn()},
      broadcaster: new EventTarget(),
    });
    root = document.createElement('div');
    document.body.append(root);
    const control = reactive<FormControlPayload<NestedEntriesProps>>({
      type: 'CraftCms\\Cms\\Form\\Controls\\NestedEntries',
      component: 'craft:nested-entries',
      mode: 'editable',
      deltaGroup: ['fields', 'cards'],
      path: ['fields', 'cards'],
      props: {
        viewMode: 'cards' as const,
        manager: {
          ownerElementType: 'Entry',
          ownerId: 31,
          ownerSiteId: 1,
          attribute: 'field:cards',
          fieldId: 7,
          elementType: 'Entry',
          canCreate: false,
          canPaste: false,
          sortable: true,
          createAttributes: [{label: 'Entry', attributes: {}}],
          pasteableEntryTypeIds: [],
          ...options.manager,
        },
        cards: options.cards ?? [
          nestedEntry({
            id: 14,
            siteId: 1,
            cardHeaderHtml: '<b>Card</b>',
            actionMenuItems: [menuAction(14, 'delete', 'Delete entry')],
          }),
        ],
      },
    });

    const prepare =
      options.prepare ??
      vi.fn(async () => ({
        ownerId: 73,
        ownerIsDerivative: false,
        ownerIsInDerivativeTree: true,
        ownerIsUnpublishedDraft: false,
        requiresDerivative: true,
      }));
    const refresh = options.refresh ?? vi.fn(async () => {});
    app = createApp({
      setup() {
        provide(NestedOwnerEditorKey, {prepare, refresh});

        return () =>
          h(NestedEntriesControl as any, {
            editable: options.editable ?? true,
            value: null,
            control,
          });
      },
    });
    app.mount(root);

    return {
      control,
      prepare,
      refresh,
      createElementEditor,
      legacyElementEditor,
      legacySlideout,
    };
  }

  function action(label: string): HTMLElement | undefined {
    return [...root.querySelectorAll<HTMLElement>('craft-action-item')].find(
      (item) => item.textContent?.trim() === label
    );
  }

  it('does not mutate when owner preparation fails', async () => {
    const prepare = vi.fn().mockResolvedValue(null);
    window.confirm = vi.fn().mockReturnValue(true);
    mount({prepare});

    action('Delete entry')!.click();
    await vi.waitFor(() =>
      expect(root.textContent).toContain('No nested entries were changed')
    );

    expect(prepare).toHaveBeenCalledOnce();
    expect(request.post).not.toHaveBeenCalled();
  });

  it('does not mutate when draft preparation returns a published owner tree', async () => {
    const prepare = vi.fn().mockResolvedValue({
      ownerId: 31,
      ownerIsDerivative: false,
      ownerIsInDerivativeTree: false,
      ownerIsUnpublishedDraft: false,
      requiresDerivative: true,
    });
    window.confirm = vi.fn().mockReturnValue(true);
    mount({prepare});

    action('Delete entry')!.click();
    await vi.waitFor(() =>
      expect(root.textContent).toContain('Could not prepare the owner draft')
    );

    expect(request.post).not.toHaveBeenCalled();
  });

  it('uses the prepared owner and refreshes its form once after a mutation', async () => {
    request.post.mockResolvedValue({data: {}});
    window.confirm = vi.fn().mockReturnValue(true);
    const refresh = vi.fn(async () => {});
    const {prepare} = mount({refresh});

    window.dispatchEvent(
      new CustomEvent('craft:nested-element-action', {
        detail: {
          action: 'element-action',
          elementId: 14,
          item: standardNestedActions.find(
            (candidate) => candidate.key === DELETE_ACTION
          ),
          trigger: document.body,
        },
      })
    );
    await nextTick();

    expect(prepare).not.toHaveBeenCalled();

    action('Delete entry')!.click();
    await vi.waitFor(() => expect(refresh).toHaveBeenCalledOnce());

    expect(prepare).toHaveBeenCalledOnce();
    expect(actions.run).toHaveBeenCalledWith(
      expect.objectContaining({
        url: expect.stringContaining('element-indexes/perform-action'),
        body: expect.objectContaining({
          elementAction: DELETE_ACTION,
          ownerId: 73,
          context: 'embeddedIndex',
          source: '__IMP__',
          elementIds: [14],
        }),
      }),
      expect.anything()
    );
    expect(
      request.post.mock.calls.some(([url]) =>
        String(url).includes('element-indexes/get-elements')
      )
    ).toBe(false);
  });

  it('preserves server actions without rebuilding actions from capability flags', async () => {
    const opened = vi.fn();
    window.addEventListener('plugin:open', opened, {once: true});
    mount({
      cards: [
        nestedEntry({
          id: 14,
          actionMenuItems: [
            {
              type: 'button',
              label: 'Open in plugin',
              icon: 'star',
              action: {
                type: 'event',
                name: 'plugin:open',
                detail: {elementId: 14},
              },
            },
          ],
        }),
      ],
    });

    expect(action('Delete entry')).toBeUndefined();
    expect(action('Duplicate')).toBeUndefined();
    const item = action('Open in plugin')!;
    expect(item).toHaveProperty('icon', 'star');
    item.click();
    await vi.waitFor(() => expect(opened).toHaveBeenCalledOnce());

    expect((opened.mock.calls[0]![0] as CustomEvent).detail.elementId).toBe(14);
    expect(request.post).not.toHaveBeenCalled();
  });

  it.each(['buttons', 'menu'])(
    'creates the chosen type from %s and retains its opener',
    async (presentation) => {
      request.post.mockResolvedValue({
        data: {
          cpEditUrl: '/admin/edit/92',
          element: {id: 92, siteId: 1, draftId: 44},
        },
      });
      const {prepare, refresh} = mount({
        manager: {
          canCreate: true,
          createAttributes: [
            {label: 'Text', icon: 'align-left', attributes: {typeId: 9}},
            {
              label: 'Quote',
              icon: 'quote-left',
              group: presentation === 'menu' ? 'Content' : undefined,
              attributes: {typeId: 17},
            },
          ],
        },
      });
      await nextTick();

      const target =
        presentation === 'menu'
          ? action('Add Quote')!
          : [...root.querySelectorAll<HTMLElement>('[data-create-entry]')].find(
              (button) => button.textContent?.trim() === 'Add Quote'
            )!;
      const opener =
        presentation === 'menu'
          ? root.querySelector('[data-create-entry]')
          : target;

      expect((target as HTMLElement & {icon: string}).icon).toBe('quote-left');
      target.click();
      await vi.waitFor(() => expect(refresh).toHaveBeenCalledOnce());

      expect(prepare).toHaveBeenCalledOnce();
      expect(request.post).toHaveBeenCalledWith(
        expect.stringContaining('elements/create'),
        {elementType: 'Entry', ownerId: 73, fieldId: 7, siteId: 1, typeId: 17}
      );
      const editUrl = new URL(
        slideout.mock.calls[0]![0],
        window.location.origin
      );
      expect(editUrl.pathname).toBe('/admin/actions/elements/edit');
      expect(Object.fromEntries(editUrl.searchParams)).toEqual({
        elementId: '92',
        siteId: '1',
        fieldId: '7',
        ownerId: '73',
        draftId: '44',
        fresh: '1',
      });
      expect(slideout).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({opener})
      );
    }
  );

  it('opens newly created entries in the legacy editor when the Vue host is unavailable', async () => {
    request.post.mockResolvedValue({
      data: {element: {id: 92, siteId: 1, draftId: 44}},
    });
    const {createElementEditor} = mount({
      vueSlideout: false,
      manager: {
        canCreate: true,
        createAttributes: [{label: 'Text', attributes: {typeId: 9}}],
      },
    });
    await nextTick();

    root.querySelector<HTMLElement>('[data-create-entry]')!.click();
    await vi.waitFor(() => expect(createElementEditor).toHaveBeenCalledOnce());

    expect(slideout).not.toHaveBeenCalled();
    expect(createElementEditor).toHaveBeenCalledWith('Entry', {
      elementId: 92,
      siteId: 1,
      fieldId: 7,
      ownerId: 73,
      draftId: 44,
      params: {fresh: 1},
      onBeforeSubmit: expect.any(Function),
    });
  });

  it('restores focus by position when a saved child has a new identity', async () => {
    const refresh = vi.fn(async () => {
      control.props.cards = [
        nestedEntry({
          id: 28,
          siteId: 1,
          ownerId: 31,
          editUrl: '/edit/28',
          cardAttributes: {data: {editable: true}},
        }),
      ];
    });
    const {control} = mount({
      refresh,
      cards: [
        nestedEntry({
          id: 18,
          siteId: 1,
          ownerId: 31,
          editUrl:
            '/admin/actions/elements/edit?elementId=18&siteId=1&fieldId=7&ownerId=31&draftId=6&prevalidate=1',
          cardAttributes: {data: {editable: true}},
        }),
      ],
    });
    await nextTick();
    const button = root.querySelector<HTMLElement>('[data-edit-entry]')!;
    button.click();
    await vi.waitFor(() => expect(slideout).toHaveBeenCalledOnce());
    const editUrl = new URL(slideout.mock.lastCall![0], window.location.origin);
    expect(Object.fromEntries(editUrl.searchParams)).toEqual({
      elementId: '18',
      siteId: '1',
      fieldId: '7',
      ownerId: '31',
      draftId: '6',
      prevalidate: '1',
    });
    const options = slideout.mock.lastCall![1];

    options.opener.focus();
    await vi.waitFor(() =>
      expect(document.activeElement?.getAttribute('href')).toContain(
        'elementId=18'
      )
    );
    options.onSaved();
    options.opener.focus();
    await vi.waitFor(() => {
      expect(refresh).toHaveBeenCalledOnce();
      expect(document.activeElement?.getAttribute('href')).toBe('/edit/28');
    });
  });

  it('uses the refreshed server edit URL after preparing an owner draft', async () => {
    const serverEditUrl =
      '/admin/actions/elements/edit?elementId=18&siteId=1&fieldId=7&ownerId=31';
    const {control} = mount({
      cards: [
        nestedEntry({
          id: 18,
          siteId: 1,
          ownerId: 31,
          editUrl: serverEditUrl,
          cardAttributes: {data: {editable: true}},
        }),
      ],
    });
    await nextTick();

    root.querySelector<HTMLAnchorElement>('a[href]')!.click();
    await vi.waitFor(() => expect(slideout).toHaveBeenCalledOnce());
    expect(await slideout.mock.lastCall![1].prepareNestedOwner()).toBe(73);

    control.props.cards = [
      nestedEntry({
        id: 18,
        siteId: 1,
        ownerId: 31,
        editUrl: serverEditUrl,
        cardAttributes: {data: {editable: true}},
      }),
    ];
    await nextTick();
    root.querySelector<HTMLAnchorElement>('a[href]')!.click();
    await vi.waitFor(() => expect(slideout).toHaveBeenCalledTimes(2));

    expect(
      new URL(
        slideout.mock.lastCall![0],
        window.location.origin
      ).searchParams.get('ownerId')
    ).toBe('31');
  });

  it('prepares derivative saves in the legacy editor when the Vue host is unavailable', async () => {
    const {createElementEditor, legacyElementEditor} = mount({
      vueSlideout: false,
      cards: [
        nestedEntry({
          id: 18,
          siteId: 1,
          ownerId: 31,
          editUrl:
            '/admin/actions/elements/edit?elementId=18&siteId=1&fieldId=7&ownerId=31&draftId=6',
          cardAttributes: {data: {editable: true}},
        }),
      ],
    });
    await nextTick();

    root.querySelector<HTMLAnchorElement>('a[href]')!.click();
    await vi.waitFor(() => expect(createElementEditor).toHaveBeenCalledOnce());

    expect(slideout).not.toHaveBeenCalled();
    const settings = createElementEditor.mock.lastCall![1];
    await settings.onBeforeSubmit();
    expect(legacyElementEditor.settings.saveParams).toEqual({
      action: 'elements/save-nested-element-for-derivative',
      newOwnerId: 73,
    });
  });

  it('opens the entry’s own edit page in a new tab on a modified click', async () => {
    const open = vi.spyOn(window, 'open').mockReturnValue(null);
    mount({
      cards: [
        nestedEntry({
          id: 18,
          editUrl: '/admin/actions/elements/edit?elementId=18',
          cpEditUrl: '/admin/entries/pages/18',
          cardAttributes: {data: {editable: true}},
        }),
      ],
    });
    await nextTick();

    root
      .querySelector('[data-nested-id="18"] span')!
      .dispatchEvent(
        new MouseEvent('dblclick', {bubbles: true, ctrlKey: true})
      );

    expect(open).toHaveBeenCalledWith(
      '/admin/entries/pages/18',
      '_blank',
      'noopener'
    );
    expect(slideout).not.toHaveBeenCalled();
  });

  it('refreshes the owner once a nested editor’s draft saves settle', async () => {
    const {refresh} = mount({
      cards: [
        nestedEntry({
          id: 41,
          editUrl: '/admin/actions/elements/edit?elementId=41',
          cardAttributes: {data: {editable: true}},
        }),
      ],
    });
    await nextTick();
    root
      .querySelector('[data-nested-id="41"] span')!
      .dispatchEvent(new MouseEvent('dblclick', {bubbles: true}));
    await vi.waitFor(() =>
      expect(
        slideout.mock.calls.find(([url]) => url.includes('elementId=41'))
      ).toBeDefined()
    );
    const {onSaved} = slideout.mock.calls.find(([url]) =>
      url.includes('elementId=41')
    )![1];

    vi.useFakeTimers();
    onSaved({draft: true});
    onSaved({draft: true});
    await vi.advanceTimersByTimeAsync(999);
    expect(refresh).not.toHaveBeenCalled();

    await vi.advanceTimersByTimeAsync(1);
    expect(refresh).toHaveBeenCalledOnce();

    onSaved({draft: true});
    onSaved();
    await vi.advanceTimersByTimeAsync(1000);
    expect(refresh).toHaveBeenCalledTimes(2);
  });

  it('announces deletion and passes the standard confirmation to the action runner', async () => {
    const {refresh} = mount();

    action('Delete entry')!.click();
    expect(useAnnouncer().announcement.value).toBe('Loading');
    await vi.waitFor(() => expect(refresh).toHaveBeenCalledOnce());

    expect(actions.run).toHaveBeenCalledWith(
      expect.objectContaining({
        confirm: 'Are you sure you want to delete the selected entries?',
      }),
      expect.anything()
    );
    await vi.waitFor(() =>
      expect(useAnnouncer().announcement.value).toBe('Loading complete')
    );
  });

  it('offers paste only for copied entries with an allowed entry type', async () => {
    copiedElements.value = [
      {type: 'Entry', data: {entryTypeId: 17}},
      {type: 'Entry', data: {entryTypeId: 23}},
    ];
    mount({
      manager: {
        canPaste: true,
        canCreate: true,
        pasteableEntryTypeIds: [17, 23],
      },
    });
    await nextTick();

    expect(
      [...root.querySelectorAll('craft-button')].some(
        (button) => button.textContent?.trim() === 'Paste entries'
      )
    ).toBe(true);
  });

  it('hides paste when a copied entry type is not allowed', async () => {
    copiedElements.value = [{type: 'Entry', data: {entryTypeId: 41}}];
    mount({
      manager: {
        canPaste: true,
        canCreate: true,
        pasteableEntryTypeIds: [17, 23],
      },
    });
    await nextTick();

    expect(root.textContent).not.toContain('Paste entry');
  });

  it('does not expose selection or mutation actions in read-only mode', () => {
    mount({
      editable: false,
      cards: [
        nestedEntry({
          id: 14,
          editUrl: '/edit/14',
          cardAttributes: {
            data: {editable: true},
          },
          actionMenuItems: [
            menuAction(14, 'delete', 'Delete entry'),
            menuAction(14, 'duplicate', 'Duplicate'),
          ],
        }),
      ],
    });

    expect(root.querySelector('[aria-label="Select 14"]')).toBeNull();
    expect(action('Delete entry')).toBeUndefined();
    expect(action('Duplicate')).toBeUndefined();
    expect(root.querySelector('[data-edit-entry]')).not.toBeNull();
  });

  it('marks invalid cards without requesting index data', async () => {
    mount();
    const control = root.querySelector('.nested-entries')!;
    control.dispatchEvent(
      new CustomEvent('craft:nested-validation', {
        bubbles: true,
        detail: {ids: [14]},
      })
    );
    await nextTick();

    expect(root.querySelector('[data-nested-id="14"]')?.classList).toContain(
      'error'
    );
    expect(request.post).not.toHaveBeenCalled();
  });
});

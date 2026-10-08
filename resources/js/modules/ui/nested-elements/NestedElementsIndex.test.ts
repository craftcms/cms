import {createApp, h, nextTick, provide} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import type {ActionItemButton} from '@/common/types';
import {
  NestedOwnerEditorKey,
  type NestedOwnerContext,
} from '@/modules/elements/nested-owner';
import type {BulkActionItem} from '@/modules/elements/types/actions';
import type {NestedElementsProps, NestedElement} from './nested-elements';
import NestedElementsIndex from './NestedElementsIndex.vue';
import {NestedElementsControlKey} from './nested-elements-context';
import {
  button,
  DELETE_ACTION,
  goToPage,
  inlineTitleInput,
  nestedElement,
  nestedElementAction,
  nestedIndexPayload,
  pageInput,
  pagerButton,
  postsTo,
  selectRow,
  stubElementInternals,
  standardNestedActions,
  waitForPage,
} from './nested-index.fixture';

const request = vi.hoisted(() => ({post: vi.fn()}));
const actions = vi.hoisted(() => ({run: vi.fn()}));
vi.mock('@craftcms/ui', async (original) => ({
  ...(await original<Record<string, unknown>>()),
  actionClient: request,
}));
vi.mock('@craftcms/ui/actions.mjs', () => ({runAction: actions.run}));

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {readOnly: false}}),
}));

describe('NestedElementsIndex', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let root: HTMLElement | undefined;
  let restoreElementInternals = () => {};

  afterEach(() => {
    app?.unmount();
    root?.remove();
    request.post.mockReset();
    actions.run.mockReset();
    localStorage.clear();
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
    restoreElementInternals();
  });

  function element(
    id: number,
    data: Record<string, unknown> = {},
    overrides: Partial<NestedElement> = {}
  ): NestedElement {
    const {
      copyable = true,
      duplicatable = true,
      deletable = true,
      ...cardData
    } = data;

    return nestedElement(id, {
      capabilities: {
        copyable: Boolean(copyable),
        duplicatable: Boolean(duplicatable),
        deletable: Boolean(deletable),
      },
      cardAttributes: {
        data: {
          label: `Entry ${id}`,
          editable: true,
          ...cardData,
        },
      },
      inlineEditable: cardData.editable !== false,
      ...overrides,
    });
  }

  function nestedAction(
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

  function mount({
    editable = true,
    mode = 'table',
    pages = [
      [element(11), element(12)],
      [element(13), element(14)],
    ],
    reorderable = true,
    actions,
  }: {
    editable?: boolean;
    mode?: 'table' | 'cards';
    pages?: NestedElement[][];
    reorderable?: boolean;
    actions?: BulkActionItem[] | null;
  } = {}) {
    restoreElementInternals = stubElementInternals();
    const server = {reorderable};
    const saveResponses: unknown[] = [];
    let heldLoad: Promise<void> | null = null;
    const payload = (page: number, viewMode: 'table' | 'cards') =>
      nestedIndexPayload(pages[page - 1] ?? [], {
        page,
        lastPage: pages.length,
        total: pages.flat().length,
        mode: viewMode,
        reorderable: server.reorderable,
        actions,
      });
    const craft = {
      cp: {
        copyElements: vi.fn(),
        displayError: vi.fn(),
        displayNotice: vi.fn(),
        getCopiedElements: () => [],
        onCopyElements: vi.fn(),
      },
      createElementEditor: vi.fn(() => ({
        elementEditor: {settings: {}, saveDraft: vi.fn()},
        on: vi.fn(),
      })),
      elementTypeNames: {Entry: ['Entry', 'Entries', 'entry', 'entries']},
      findDeltaData: (_before: string, after: string, names: string[]) =>
        `${after}&modifiedDeltaNames[]=${encodeURIComponent(names[0]!)}`,
      pageTrigger: 'p',
      systemUid: 'test',
    };
    vi.stubGlobal('Craft', craft);
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('<svg></svg>'))
    );

    request.post.mockImplementation(async (url: string, body: unknown) => {
      if (url.includes('element-indexes/get-elements')) {
        const params = body as {p?: number; viewMode?: 'table' | 'cards'};
        await heldLoad;

        return {data: payload(Number(params.p ?? 1), params.viewMode ?? mode)};
      }

      if (url.includes('element-indexes/save-elements')) {
        return saveResponses.shift() ?? {data: {message: 'Changes saved.'}};
      }

      if (url.includes('nested-elements/reorder')) {
        return {data: {message: 'Entries reordered.'}};
      }

      throw new Error(`Unexpected request: ${url}`);
    });

    const owner = {
      prepare: vi.fn<() => Promise<NestedOwnerContext>>(async () => ({
        ownerId: 91,
        ownerIsDerivative: true,
        ownerIsInDerivativeTree: true,
        ownerIsUnpublishedDraft: false,
        requiresDerivative: true,
      })),
      refresh: vi.fn(async () => {}),
    };
    const control: NestedElementsProps = {
      viewMode: 'index',
      manager: {
        elementType: 'Entry',
        ownerElementType: 'Entry',
        ownerId: 73,
        ownerSiteId: 1,
        fieldId: 7,
        attribute: 'field:entries',
        canCreate: true,
        canPaste: false,
        sortable: true,
        maxElements: 10,
        createButtonLabel: 'New entry',
        createAttributes: [{label: 'Entry', attributes: {typeId: 9}}],
        pasteableData: null,
      },
      cards: [],
      index: {
        indexSettings: {
          storageKey: 'field:entries',
          showHeaderColumn: true,
          static: false,
        },
        initial: payload(1, mode),
      },
    } as NestedElementsProps;

    root = document.createElement('div');
    document.body.append(root);
    app = createApp({
      setup() {
        provide(NestedElementsControlKey, {
          path: ['fields', 'entries'],
          markModified: vi.fn(),
        });
        provide(NestedOwnerEditorKey, owner);

        return () => h(NestedElementsIndex, {control, editable});
      },
    });
    app.mount(root);

    return {
      root,
      craft,
      owner,
      server,
      saveResponses,
      holdLoads: () => {
        let release!: () => void;
        heldLoad = new Promise((resolve) => {
          release = () => {
            heldLoad = null;
            resolve();
          };
        });

        return release;
      },
    };
  }

  it('selects visible table rows by their IDs and clears selection on page change', async () => {
    vi.stubGlobal(
      'confirm',
      vi.fn(() => true)
    );
    const {root} = mount();
    selectRow(root, 11);
    await nextTick();
    button(root, 'Move').click();

    await waitForPage(root, 2);
    await waitForIdle(root);
    expect(findActionItem(root, 'Delete')).toBeUndefined();
    selectRow(root, 13, 'Enter');
    await nextTick();
    actionItem(root, 'Delete').click();

    await vi.waitFor(() =>
      expect(actions.run).toHaveBeenCalledWith(
        expect.objectContaining({
          body: expect.objectContaining({
            elementAction: DELETE_ACTION,
            elementIds: [13],
            ownerId: 91,
          }),
        }),
        expect.anything()
      )
    );
  });

  it('pages in read-only mode and locks the shared pager during requests', async () => {
    const {root, holdLoads} = mount({editable: false});
    const previous = () => pagerButton(root, 'Previous page');
    const next = () => pagerButton(root, 'Next page');

    expect(previous().disabled).toBe(true);
    expect(next().disabled).toBe(false);
    await goToPage(root, 'Next page', 2);
    expect(indexRequests().at(-1)?.p).toBe(2);
    await vi.waitFor(() => expect(next().disabled).toBe(true));
    expect(previous().disabled).toBe(false);

    await goToPage(root, 'Previous page', 1);
    const input = pageInput(root).querySelector('input')!;
    input.value = '2';
    input.dispatchEvent(new InputEvent('input', {bubbles: true}));
    await vi.waitFor(() => expect(indexRequests().at(-1)?.p).toBe(2));
    await vi.waitFor(() => expect(previous().disabled).toBe(false));

    const release = holdLoads();
    button(root, 'Refresh').click();
    await vi.waitFor(() => expect(pageInput(root).disabled).toBe(true));
    expect(previous().disabled).toBe(true);
    expect(next().disabled).toBe(true);
    expect(button(root, 'Export').disabled).toBe(true);
    release();
    await vi.waitFor(() => expect(pageInput(root).disabled).toBe(false));

    let finishExport!: () => void;
    actions.run.mockImplementationOnce(
      () => new Promise<void>((resolve) => (finishExport = resolve))
    );
    exportButton(root, 'XLSX').click();
    await vi.waitFor(() => expect(pageInput(root).disabled).toBe(true));
    expect(previous().disabled).toBe(true);
    expect(button(root, 'Export').disabled).toBe(true);
    finishExport();
    await vi.waitFor(() => expect(pageInput(root).disabled).toBe(false));
  });

  it('stops Enter in embedded pager and export inputs from reaching the owner', async () => {
    const {root} = mount();
    const ownerKeydown = vi.fn();
    root.addEventListener('keydown', ownerKeydown);
    const pagerInput = await vi.waitFor(() => {
      const input = pageInput(root).querySelector('input');
      expect(input).not.toBeNull();

      return input!;
    });
    const exportInput = root.querySelector<HTMLInputElement>(
      'input[type="number"]'
    )!;

    for (const input of [pagerInput, exportInput]) {
      const event = new KeyboardEvent('keydown', {
        key: 'Enter',
        bubbles: true,
        cancelable: true,
      });

      expect(input.dispatchEvent(event)).toBe(false);
      expect(event.defaultPrevented).toBe(true);
    }

    expect(ownerKeydown).not.toHaveBeenCalled();
  });

  it('uses card selection for bulk actions and clears it before switching to table', async () => {
    vi.stubGlobal(
      'confirm',
      vi.fn(() => true)
    );
    const {root} = mount({mode: 'cards', pages: [[element(31), element(47)]]});
    await waitForIdle(root);
    expect(
      [...root.querySelectorAll('craft-button')].filter(
        (item) => item.textContent?.trim() === 'New entry'
      )
    ).toHaveLength(1);

    selectRow(root, 47);
    await nextTick();
    expect(
      [...root.querySelectorAll('[role="status"]')].some((status) =>
        status.textContent?.includes('1 item selected')
      )
    ).toBe(true);
    button(root, 'Clear selection').click();
    await nextTick();
    expect(findActionItem(root, 'Delete')).toBeUndefined();

    selectRow(root, 47);
    await nextTick();
    actionItem(root, 'Delete').click();
    await vi.waitFor(() =>
      expect(actions.run).toHaveBeenCalledWith(
        expect.objectContaining({
          body: expect.objectContaining({elementIds: [47]}),
        }),
        expect.anything()
      )
    );
    await waitForIdle(root);

    selectRow(root, 31);
    await nextTick();
    expect(findActionItem(root, 'Delete')).toBeDefined();
    root
      .querySelector('craft-button-group[name="viewState[mode]"]')!
      .dispatchEvent(new CustomEvent('change', {detail: {value: 'table'}}));
    await vi.waitFor(() =>
      expect(root.querySelector('tr[data-nested-id="31"]')).not.toBeNull()
    );
    expect(findActionItem(root, 'Delete')).toBeUndefined();
  });

  it('shows loading feedback during a read-only reload and disables selection', async () => {
    const {root, holdLoads} = mount({editable: false});
    const release = holdLoads();
    button(root, 'Refresh').click();
    await vi.waitFor(() =>
      expect(root.querySelector('section')!.getAttribute('aria-busy')).toBe(
        'true'
      )
    );
    expect(root.querySelectorAll('tr.cp-table-row')).toHaveLength(0);
    expect(root.querySelector('craft-spinner')).not.toBeNull();

    release();
    await waitForIdle(root);
    expect(root.querySelectorAll('tr.cp-table-row')).toHaveLength(2);
    expect(button(root, 'New entry')).toBeUndefined();
    const checkboxes = [
      ...root.querySelectorAll<HTMLElement & {disabled: boolean}>(
        'table craft-checkbox'
      ),
    ];
    expect(checkboxes.length).toBeGreaterThan(0);
    expect(checkboxes.every((checkbox) => checkbox.disabled)).toBe(true);
  });

  it('uses the response reorderability flag to gate reordering', async () => {
    const {root, server} = mount({reorderable: false});

    expect(root.querySelector('craft-reorder-button')).toBeNull();

    server.reorderable = true;
    button(root, 'Refresh').click();

    await vi.waitFor(() =>
      expect(root.querySelectorAll('craft-reorder-button')).toHaveLength(2)
    );
  });

  it.each(['table', 'cards'] as const)(
    'keeps %s controls mounted but disabled while a reorder is saving',
    async (mode) => {
      const {root} = mount({mode});
      const controls = (selector: string) => [
        ...root.querySelectorAll<HTMLElement & {disabled: boolean}>(selector),
      ];
      const reorderButtons = () => controls('craft-reorder-button');
      const selectionAndSorting = () => [
        ...controls('.element-index__body craft-checkbox'),
        ...controls('th button'),
      ];
      await vi.waitFor(() => expect(reorderButtons()).toHaveLength(2));
      await waitForIdle(root);
      let finishReorder!: () => void;
      request.post.mockImplementationOnce(async () => {
        await new Promise<void>((resolve) => (finishReorder = resolve));

        return {data: {message: 'Entries reordered.'}};
      });

      reorderButtons()[0]!.dispatchEvent(
        new CustomEvent('craft-reorder', {
          detail: {direction: 'down'},
          bubbles: true,
        })
      );
      await vi.waitFor(() => expect(finishReorder).toBeTypeOf('function'));
      await nextTick();

      expect(reorderButtons()).toHaveLength(2);
      expect(selectionAndSorting().length).toBeGreaterThan(0);
      expect(
        [...reorderButtons(), ...selectionAndSorting()].every(
          (control) => control.disabled
        )
      ).toBe(true);

      finishReorder();
      await waitForIdle(root);
      expect(reorderButtons()).toHaveLength(2);
      expect(
        [...reorderButtons(), ...selectionAndSorting()].some(
          (control) => control.disabled
        )
      ).toBe(false);
    }
  );

  it('renders card labels and attributes without unauthorized or legacy actions', async () => {
    const {root} = mount({
      mode: 'cards',
      pages: [
        [
          nestedElement(11, {
            capabilities: {
              copyable: false,
              duplicatable: true,
              deletable: true,
            },
            cardHeaderHtml: '<strong>Alpha</strong>',
            cardActionsHtml: '<button>Unsafe legacy action</button>',
            cardAttributes: {
              style: {'min-height': '80px'},
              data: {label: 'Alpha'},
            },
          }),
        ],
      ],
    });
    await waitForIdle(root);
    const card = root.querySelector<HTMLElement>('craft-card')!;
    expect(card.style.minHeight).toBe('80px');
    expect(card.textContent).toContain('Alpha');
    expect(card.querySelector('craft-button')).toBeNull();
    expect(root.textContent).not.toContain('Duplicate');
    expect(root.textContent).not.toContain('Copy');
    expect(root.textContent).not.toContain('Delete');
    expect(root.textContent).not.toContain('Unsafe legacy action');
  });

  it('saves inline edits in the prepared owner scope', async () => {
    const {root} = mount({
      pages: [[element(11, {}, {inlineInputHtml: inlineTitleInput(11)})]],
    });
    button(root, 'Edit inline').click();
    const input = await vi.waitFor(() => {
      const element = root.querySelector<HTMLInputElement>(
        '[data-inline-id="11"] input'
      );
      expect(element).not.toBeNull();

      return element!;
    });
    expect(indexRequests().at(-1)).toEqual(
      expect.objectContaining({editable: true})
    );
    expect(document.activeElement).toBe(input);
    input.value = 'Edited';
    button(root, 'Save').click();
    const submitted = await vi.waitFor(() => {
      const [request] = saveRequests();
      expect(request).toBeDefined();

      return request!;
    });

    expect(submitted!.get('ownerId')).toBe('91');
    expect(submitted!.get('attribute')).toBe('field:entries');
    expect(submitted!.get('inline[element-11][title]')).toBe('Edited');
  });

  it('exports with the server-selected exporter and format', () => {
    const {root} = mount();
    exportButton(root, 'XLSX').click();

    expect(actions.run).toHaveBeenCalledWith({
      type: 'download',
      method: 'POST',
      url: expect.stringContaining('export'),
      body: expect.objectContaining({
        type: 'Expanded',
        format: 'xlsx',
        criteria: {search: '', status: null},
      }),
    });
  });

  it('scopes server bulk downloads to the nested owner and preserves plugin parameters', async () => {
    const {root} = mount({
      actions: [
        {
          key: 'plugin\\Download',
          label: 'Download selected',
          action: {
            type: 'download',
            method: 'GET',
            url: '/actions/plugin/download?token=plugin-token',
            body: {format: 'zip'},
            confirm: 'Download selected entries?',
          },
        },
      ],
    });
    selectRow(root, 11);
    await nextTick();

    actionItem(root, 'Download selected').click();
    await vi.waitFor(() => expect(actions.run).toHaveBeenCalledOnce());

    expect(actions.run.mock.calls[0]![0]).toMatchObject({
      type: 'download',
      method: 'GET',
      url: '/actions/plugin/download?token=plugin-token',
      confirm: 'Download selected entries?',
      body: {
        format: 'zip',
        ownerElementType: 'Entry',
        ownerId: 73,
        ownerSiteId: 1,
        attribute: 'field:entries',
        elementType: 'Entry',
        context: 'embeddedIndex',
        source: '__IMP__',
        elementIds: [11],
      },
    });
  });

  it('runs server bulk actions in the prepared owner scope and refreshes the index', async () => {
    const {root} = mount({
      actions: [
        {
          key: 'plugin\\Archive',
          label: 'Archive selected',
          action: {
            type: 'http',
            method: 'POST',
            url: '/actions/plugin/archive',
            body: {archive: true},
          },
        },
      ],
    });
    selectRow(root, 11);
    await nextTick();
    const readsBeforeAction = indexRequests().length;

    actionItem(root, 'Archive selected').click();
    await vi.waitFor(() => expect(actions.run).toHaveBeenCalledOnce());

    expect(actions.run.mock.calls[0]![0]).toMatchObject({
      type: 'http',
      method: 'POST',
      url: '/actions/plugin/archive',
      body: {
        archive: true,
        ownerElementType: 'Entry',
        ownerId: 91,
        ownerSiteId: 1,
        attribute: 'field:entries',
        elementType: 'Entry',
        context: 'embeddedIndex',
        source: '__IMP__',
        elementIds: [11],
      },
    });
    await vi.waitFor(() =>
      expect(indexRequests()).toHaveLength(readsBeforeAction + 1)
    );
  });

  it('preserves server table actions and intercepts only the primary edit link', async () => {
    const pluginAction = vi.fn();
    window.addEventListener('plugin:inspect', pluginAction, {once: true});
    const editUrl = '/edit/11?elementId=11';
    const {root, craft} = mount({
      pages: [
        [
          element(
            11,
            {label: 'Alpha'},
            {
              title: `<a href="${editUrl}">Alpha</a><a href="/other">Other</a>`,
              actionMenuItems: [
                {
                  label: 'Inspect in plugin',
                  action: {
                    type: 'event',
                    name: 'plugin:inspect',
                    detail: {elementId: 11},
                  },
                },
                nestedAction(11, 'copy', 'Copy from server'),
                nestedAction(11, 'duplicate', 'Duplicate from server'),
                nestedAction(11, 'delete', 'Delete from server'),
              ],
            }
          ),
        ],
      ],
    });
    const firstRow = root.querySelector<HTMLElement>('tr.cp-table-row')!;
    expect(
      firstRow.querySelector('craft-button')?.getAttribute('aria-label')
    ).toBe('Actions for Alpha');
    expect(firstRow.textContent).toContain('Inspect in plugin');
    expect(firstRow.textContent).toContain('Copy from server');
    expect(firstRow.textContent).toContain('Duplicate from server');
    expect(firstRow.textContent).toContain('Delete from server');

    actionItem(firstRow, 'Inspect in plugin').click();
    actionItem(firstRow, 'Copy from server').click();
    await vi.waitFor(() => expect(pluginAction).toHaveBeenCalledOnce());
    expect(craft.cp.copyElements).toHaveBeenCalledWith([
      expect.objectContaining({id: 11}),
    ]);

    const editLink = firstRow.querySelector<HTMLAnchorElement>(
      `a[href="${editUrl}"]`
    )!;
    const mouseup = vi.fn();
    editLink.addEventListener('mouseup', mouseup);
    expect(
      editLink.dispatchEvent(
        new MouseEvent('mouseup', {bubbles: true, cancelable: true})
      )
    ).toBe(false);
    expect(mouseup).not.toHaveBeenCalled();
    expect(
      editLink.dispatchEvent(
        new MouseEvent('click', {bubbles: true, cancelable: true})
      )
    ).toBe(false);
    expect(craft.createElementEditor).toHaveBeenCalledWith(
      'Entry',
      expect.objectContaining({elementId: '11'})
    );

    expect(
      editLink.dispatchEvent(
        new MouseEvent('mouseup', {
          bubbles: true,
          cancelable: true,
          ctrlKey: true,
        })
      )
    ).toBe(true);
    expect(mouseup).toHaveBeenCalledOnce();
    expect(
      editLink.dispatchEvent(
        new MouseEvent('click', {
          bubbles: true,
          cancelable: true,
          ctrlKey: true,
        })
      )
    ).toBe(true);
    expect(craft.createElementEditor).toHaveBeenCalledTimes(1);

    const otherLink =
      firstRow.querySelector<HTMLAnchorElement>('a[href="/other"]')!;
    expect(
      otherLink.dispatchEvent(
        new MouseEvent('click', {bubbles: true, cancelable: true})
      )
    ).toBe(true);
    expect(craft.createElementEditor).toHaveBeenCalledTimes(1);
  });

  it('leaves table links native when the owner is read-only', () => {
    const {root, craft} = mount({editable: false, pages: [[element(11)]]});
    const link = root.querySelector<HTMLAnchorElement>(
      'a[href="/edit/11?elementId=11"]'
    )!;

    expect(
      link.dispatchEvent(
        new MouseEvent('mouseup', {bubbles: true, cancelable: true})
      )
    ).toBe(true);
    expect(
      link.dispatchEvent(
        new MouseEvent('click', {bubbles: true, cancelable: true})
      )
    ).toBe(true);
    expect(craft.createElementEditor).not.toHaveBeenCalled();
  });
});

function actionItem(root: HTMLElement, label: string): HTMLElement {
  return findActionItem(root, label)!;
}

function findActionItem(
  root: HTMLElement,
  label: string
): HTMLElement | undefined {
  return [...root.querySelectorAll<HTMLElement>('craft-action-item')].find(
    (item) => item.textContent?.trim() === label
  );
}

function exportButton(root: HTMLElement, format: string): HTMLElement {
  return [...root.querySelectorAll<HTMLElement>('craft-button')].find(
    (candidate) =>
      candidate.textContent?.includes('Expanded') &&
      candidate.textContent.includes(format)
  )!;
}

async function waitForIdle(root: HTMLElement): Promise<void> {
  await vi.waitFor(() =>
    expect(root.querySelector('section')!.getAttribute('aria-busy')).toBe(
      'false'
    )
  );
}

function indexRequests(): Array<Record<string, unknown>> {
  return postsTo(request.post, 'element-indexes/get-elements');
}

function saveRequests(): URLSearchParams[] {
  return postsTo<URLSearchParams>(
    request.post,
    'element-indexes/save-elements'
  );
}

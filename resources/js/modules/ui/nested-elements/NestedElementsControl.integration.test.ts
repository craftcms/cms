import {createApp, h, nextTick, provide, reactive} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  NestedOwnerEditorKey,
  type NestedOwnerContext,
} from '@/modules/elements/nested-owner';
import type {UiControlPayload} from '../types';
import NestedElementsControl from './NestedElementsControl.vue';
import type {NestedElementsProps} from './nested-elements';
import {
  button,
  DUPLICATE_ACTION,
  goToPage,
  inlineTitleInput,
  nestedElement,
  nestedIndexPayload,
  postsTo,
  selectRow,
  selectRows,
  stubElementInternals,
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

describe('NestedElementsControl', () => {
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

  function mount({maxElements = 10} = {}) {
    restoreElementInternals = stubElementInternals();

    const movedToSecondPage = new Set<number>();
    let inlineSaveFailures = 1;
    let submitElement:
      | ((event: {response: {data: {draft: boolean}}}) => void)
      | undefined;
    let reorderFailures = 0;
    const pasteElements = vi.fn(async () => [{id: 92}]);
    const ownerRefresh = vi.fn(async () => {});
    const prepare = vi.fn<() => Promise<NestedOwnerContext>>(async () => ({
      ownerId: 73,
      ownerIsDerivative: false,
      ownerIsInDerivativeTree: true,
      ownerIsUnpublishedDraft: false,
      requiresDerivative: true,
    }));
    const broadcaster = Object.assign(new EventTarget(), {
      postMessage: vi.fn(),
    });

    vi.stubGlobal('Craft', {
      broadcaster,
      pageId: 'owner-page',
      pageTrigger: 'p',
      systemUid: 'test',
      elementTypeNames: {Entry: ['Entry', 'Entries', 'entry', 'entries']},
      findDeltaData: (_before: string, after: string, names: string[]) =>
        `${after}&modifiedDeltaNames[]=${encodeURIComponent(names[0]!)}`,
      createElementEditor: vi.fn(() => ({
        elementEditor: {
          settings: {draftId: 44, saveParams: null},
          saveDraft: vi.fn(async () => {}),
        },
        on: (
          event: string,
          callback: (event: {response: {data: {draft: boolean}}}) => void
        ) => {
          if (event === 'submit') {
            submitElement = callback;
          }
        },
      })),
      cp: {
        copyElements: vi.fn(),
        displayError: vi.fn(),
        displayNotice: vi.fn(),
        getCopiedElements: () => [
          {type: 'Entry', id: 81, data: {entryTypeId: 9}},
        ],
        onCopyElements: vi.fn(),
        pasteElements,
      },
      Preview: {refresh: vi.fn()},
    });
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('<svg></svg>'))
    );

    request.post.mockImplementation(
      async (url: string, body: Record<string, unknown> | URLSearchParams) => {
        if (url.includes('element-indexes/get-elements')) {
          return indexResponse(
            body as Record<string, unknown>,
            movedToSecondPage
          );
        }

        if (url.includes('nested-elements/reorder')) {
          if (reorderFailures > 0) {
            reorderFailures--;
            throw new Error('Reorder failed');
          }

          const params = body as {elementIds: number[]; offset: number};
          if (params.offset === 2) {
            params.elementIds.forEach((id) => movedToSecondPage.add(id));
          }

          return {data: {message: 'Entries reordered.'}};
        }

        if (url.includes('element-indexes/perform-action')) {
          return {data: {message: 'Action performed.'}};
        }

        if (url.includes('elements/create')) {
          return {data: {element: {id: 92, siteId: 1, draftId: 44}}};
        }

        if (url.includes('element-indexes/save-elements')) {
          if (inlineSaveFailures > 0) {
            inlineSaveFailures--;
            throw new Error('Connection lost');
          }

          return {data: {message: 'Changes saved.'}};
        }

        throw new Error(`Unexpected request: ${url}`);
      }
    );
    actions.run.mockImplementation(async (action) => {
      await request.post(action.url, action.body);
    });

    const control = reactive<UiControlPayload<NestedElementsProps>>({
      type: 'CraftCms\\Cms\\Ui\\Controls\\NestedElements',
      component: 'craft:nested-elements',
      mode: 'editable',
      deltaGroup: ['fields', 'entries'],
      path: ['fields', 'entries'],
      props: {
        viewMode: 'index',
        manager: {
          ownerElementType: 'Entry',
          ownerId: 31,
          ownerSiteId: 1,
          attribute: 'field:entries',
          fieldId: 7,
          elementType: 'Entry',
          canCreate: true,
          canPaste: true,
          sortable: true,
          maxElements,
          createButtonLabel: 'New entry',
          createAttributes: [{label: 'Entry', attributes: {typeId: 9}}],
          pasteableData: {attribute: 'entryTypeId', values: [9]},
        },
        cards: [],
        index: {
          indexSettings: {
            storageKey: 'field:entries',
            showHeaderColumn: true,
            static: false,
          },
          initial: indexResponse({}, movedToSecondPage).data,
        },
      },
    });

    root = document.createElement('div');
    document.body.append(root);
    app = createApp({
      setup() {
        provide(NestedOwnerEditorKey, {prepare, refresh: ownerRefresh});

        return () =>
          h(NestedElementsControl as any, {
            control,
            editable: true,
            value: null,
          });
      },
    });
    app.mount(root);

    return {
      root,
      ownerRefresh,
      pasteElements,
      submitElement: () => submitElement,
      failNextReorder: () => reorderFailures++,
    };
  }

  it('uses the page-2 offset and restores focus after moving a selection to another page', async () => {
    const {root, failNextReorder} = mount();
    await waitForPage(root, 1);
    await goToPage(root, 'Next page', 2);

    const firstRow = root.querySelector<HTMLElement>('tr.cp-table-row')!;
    window.dispatchEvent(
      new CustomEvent('craft:nested-element-action', {
        detail: {
          action: 'move-backward',
          elementId: 13,
          trigger: firstRow,
        },
      })
    );
    await vi.waitFor(() => {
      expect(reorderRequests()).toContainEqual(
        expect.objectContaining({elementIds: [13], offset: 3})
      );
    });

    await goToPage(root, 'Previous page', 1);
    selectRow(root, 11);
    await nextTick();
    const move = button(root, 'Move');
    move.focus();
    move.click();
    await waitForPage(root, 2);
    await vi.waitFor(() => {
      const movedRow = root.querySelector<HTMLElement>('[data-nested-id="11"]');
      expect(movedRow).not.toBeNull();
      expect(movedRow!.contains(document.activeElement)).toBe(true);
    });
    expect(reorderRequests()).toContainEqual(
      expect.objectContaining({elementIds: [11], offset: 2})
    );

    await goToPage(root, 'Previous page', 1);
    selectRow(root, 12);
    await nextTick();
    const readsBeforeFailedMove = indexRequests().length;
    const destinationReadsBeforeFailedMove = indexRequests().filter(
      (request) => Number(request.p ?? 1) === 2
    ).length;
    failNextReorder();
    button(root, 'Move').click();

    await vi.waitFor(() =>
      expect(indexRequests()).toHaveLength(readsBeforeFailedMove + 1)
    );
    expect(
      indexRequests().filter((request) => Number(request.p ?? 1) === 2)
    ).toHaveLength(destinationReadsBeforeFailedMove);
    // Five page loads and three moves: ~0.4s locally, but ~5s on a shared CI
    // runner, right at the default limit.
  }, 15_000);

  it('resets paging after the first create save and a toolbar paste', async () => {
    const {root, ownerRefresh, pasteElements, submitElement} = mount();
    await waitForPage(root, 1);
    await goToPage(root, 'Next page', 2);
    const readsBeforeCreate = indexRequests().length;

    button(root, 'New entry').click();
    await vi.waitFor(() => expect(submitElement()).toBeTypeOf('function'));
    expect(indexRequests()).toHaveLength(readsBeforeCreate);

    submitElement()!({response: {data: {draft: false}}});
    await waitForPage(root, 1);
    expect(ownerRefresh).toHaveBeenCalledOnce();

    await goToPage(root, 'Next page', 2);
    await search(root, 'needle');
    await vi.waitFor(() => {
      expect(indexRequests().at(-1)).toEqual(
        expect.objectContaining({search: 'needle'})
      );
    });
    await waitForPage(root, 1);
    await goToPage(root, 'Next page', 2);
    button(root, 'Paste entry').click();
    await vi.waitFor(() => expect(pasteElements).toHaveBeenCalledOnce());
    await waitForPage(root, 1);
    expect(searchInput(root).modelValue).toBe('');
    expect(indexRequests().at(-1)?.search).toBeUndefined();
    expect(ownerRefresh).toHaveBeenCalledTimes(2);
  });

  it('reloads the index after a nested child saves', async () => {
    const {root, ownerRefresh, submitElement} = mount();
    await waitForPage(root, 1);
    const readsBeforeSave = indexRequests().length;

    root
      .querySelector<HTMLElement>('[data-nested-id="11"]')!
      .dispatchEvent(new MouseEvent('dblclick', {bubbles: true}));
    await vi.waitFor(() => expect(submitElement()).toBeTypeOf('function'));

    submitElement()!({response: {data: {draft: false}}});

    await vi.waitFor(() =>
      expect(indexRequests()).toHaveLength(readsBeforeSave + 1)
    );
    expect(ownerRefresh).toHaveBeenCalledOnce();
  });

  it.each([
    {label: 'at capacity', maxElements: 4, duplicates: false},
    {
      label: 'without room for the whole selection',
      maxElements: 5,
      duplicates: false,
    },
    {
      label: 'with room for the whole selection',
      maxElements: 6,
      duplicates: true,
    },
  ])(
    'counts every owned entry when duplicating a selection $label',
    async ({maxElements, duplicates}) => {
      const {root} = mount({maxElements});
      await waitForPage(root, 1);
      await selectRows(root, [11, 12]);

      const duplicate = actionItem(root, 'Duplicate');
      expect(duplicate.disabled).toBe(!duplicates);
      duplicate.click();

      if (duplicates) {
        await vi.waitFor(() =>
          expect(duplicateRequests()).toEqual([
            expect.objectContaining({
              elementAction: DUPLICATE_ACTION,
              elementIds: [11, 12],
              ownerId: 73,
            }),
          ])
        );
      } else {
        await nextTick();
        expect(duplicateRequests()).toEqual([]);
      }
    }
  );

  it('retains and retries failed inline values without an intermediate refresh', async () => {
    const {root, ownerRefresh} = mount();
    await waitForPage(root, 1);
    button(root, 'Edit inline').click();
    const input = await vi.waitFor(() => {
      const element = root.querySelector<HTMLInputElement>(
        '[data-inline-id="11"] input'
      );
      expect(element).not.toBeNull();

      return element!;
    });
    input.value = 'Unsaved integration value';
    const readsBeforeSave = indexRequests().length;
    const ownerRefreshesBeforeSave = ownerRefresh.mock.calls.length;

    button(root, 'Save').click();
    await vi.waitFor(() => expect(inlineSaveRequests()).toHaveLength(1));
    await vi.waitFor(() => expect(button(root, 'Save').disabled).toBe(false));

    expect(inlineSaveRequests()[0]?.get('inline[element-11][title]')).toBe(
      'Unsaved integration value'
    );
    expect(root.querySelector('[data-inline-id="11"] input')).toBe(input);
    expect(input.value).toBe('Unsaved integration value');
    expect(indexRequests()).toHaveLength(readsBeforeSave);
    expect(ownerRefresh).toHaveBeenCalledTimes(ownerRefreshesBeforeSave);

    button(root, 'Save').click();
    await vi.waitFor(() => expect(inlineSaveRequests()).toHaveLength(2));
    expect(inlineSaveRequests()[1]?.get('inline[element-11][title]')).toBe(
      'Unsaved integration value'
    );
    await vi.waitFor(() => {
      expect(root.querySelector('[data-inline-id="11"] input')).toBeNull();
    });
    expect(indexRequests()).toHaveLength(readsBeforeSave + 1);
    expect(ownerRefresh).toHaveBeenCalledTimes(ownerRefreshesBeforeSave + 1);
  });
});

function indexResponse(
  params: Record<string, unknown>,
  movedToSecondPage: Set<number>
) {
  const page = Number(params.p ?? 1);
  const firstPageIds = [11, 12].filter((id) => !movedToSecondPage.has(id));
  const secondPageIds = [...movedToSecondPage, 13, 14];
  const ids = page === 1 ? firstPageIds : secondPageIds;

  return {
    data: nestedIndexPayload(
      ids.map((id) =>
        nestedElement(
          id,
          params.editable === true
            ? {inlineInputHtml: inlineTitleInput(id)}
            : {}
        )
      ),
      {page, lastPage: 2, total: firstPageIds.length + secondPageIds.length}
    ),
  };
}

function searchInput(root: HTMLElement) {
  return root.querySelector<
    HTMLElement & {modelValue: string; updateComplete?: Promise<unknown>}
  >('craft-input[name="search"]')!;
}

async function search(root: HTMLElement, value: string): Promise<void> {
  const input = searchInput(root);
  await input.updateComplete;
  input.modelValue = value;
  input.dispatchEvent(
    new CustomEvent('model-value-changed', {
      bubbles: true,
      detail: {isTriggeredByUser: true},
    })
  );
  await nextTick();

  const event = new KeyboardEvent('keydown', {
    key: 'Enter',
    bubbles: true,
    cancelable: true,
  });
  input.querySelector('input')!.dispatchEvent(event);
  expect(event.defaultPrevented).toBe(true);
}

function indexRequests(): Array<Record<string, unknown>> {
  return postsTo(request.post, 'element-indexes/get-elements');
}

function duplicateRequests(): Array<Record<string, unknown>> {
  return postsTo(request.post, 'element-indexes/perform-action').filter(
    (body) => body.elementAction === DUPLICATE_ACTION
  );
}

function actionItem(root: HTMLElement, label: string) {
  return [
    ...root.querySelectorAll<HTMLElement & {disabled: boolean}>(
      'craft-action-item'
    ),
  ].find((item) => item.textContent?.trim() === label)!;
}

function reorderRequests(): Array<Record<string, unknown>> {
  return postsTo(request.post, 'nested-elements/reorder');
}

function inlineSaveRequests(): URLSearchParams[] {
  return postsTo<URLSearchParams>(
    request.post,
    'element-indexes/save-elements'
  );
}

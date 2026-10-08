import {createApp, h, nextTick, shallowRef} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {savedNestedOwner} from '@/modules/elements/nested-owner';
import type {ActionItemButton} from '@/common/types';
import type {NestedElement, NestedElementsProps} from './nested-elements';
import NestedElements from './NestedElements.vue';
import {
  DELETE_ACTION,
  nestedElementAction,
  standardNestedActions,
} from './nested-index.fixture';

const request = vi.hoisted(() => ({post: vi.fn()}));
const actions = vi.hoisted(() => ({run: vi.fn(async () => {})}));
const slideout = vi.hoisted(() => vi.fn().mockResolvedValue(null));
vi.mock('@/common/slideouts', () => ({
  canUseVueSlideout: () => true,
  openSlideout: slideout,
}));
vi.mock('@/modules/matrix/copied-elements', () => ({
  useCopiedElements: () => shallowRef([]),
}));
vi.mock('@craftcms/ui', async (original) => ({
  ...(await original<Record<string, unknown>>()),
  actionClient: request,
}));
vi.mock('@craftcms/ui/actions.mjs', () => ({runAction: actions.run}));
vi.mock('@/modules/elements/components/ElementCards.vue', () => ({
  default: {
    props: ['data'],
    setup(
      props: {data: Array<{id: number}>},
      {slots}: {slots: Record<string, (data: unknown) => unknown>}
    ) {
      return () =>
        h(
          'ul',
          props.data.map((card) =>
            h('li', {key: card.id, 'data-nested-id': card.id}, [
              slots.actions?.({element: card}) as never,
            ])
          )
        );
    },
  },
}));

describe('NestedElements', () => {
  let root: HTMLElement;
  let app: ReturnType<typeof createApp>;

  afterEach(() => {
    app?.unmount();
    root?.remove();
    request.post.mockReset();
    actions.run.mockClear();
    slideout.mockClear();
    vi.unstubAllGlobals();
  });

  function address(id: number): NestedElement {
    return {
      id,
      siteId: 1,
      entryTypeId: null,
      ownerId: 31,
      capabilities: {copyable: true, duplicatable: true, deletable: true},
      ownerIsCanonical: true,
      isUnpublishedDraft: false,
      ownerIsUnpublishedDraft: false,
      primaryOwnerId: 31,
      isCanonical: true,
      editUrl: null,
      cpEditUrl: null,
      actionMenuItems: [
        nestedElementAction(
          id,
          standardNestedActions.find((item) => item.key === DELETE_ACTION)!,
          'Delete address'
        ) as ActionItemButton,
      ],
      cardAttributes: {},
      cardHeaderHtml: '',
      cardActionsHtml: '',
      cardContentHtml: '',
      cardFooterHtml: '',
      cardThumbHtml: '',
      thumbAlignment: 'end',
    };
  }

  function mount(refresh: () => Promise<void>, canCreate = false) {
    vi.stubGlobal('Craft', {
      cp: {displayNotice: vi.fn()},
      elementTypeNames: {
        Address: ['Address', 'Addresses', 'address', 'addresses'],
      },
      Preview: {refresh: vi.fn()},
      broadcaster: new EventTarget(),
    });
    root = document.createElement('div');
    document.body.append(root);

    const nested: NestedElementsProps = {
      viewMode: 'cards-grid',
      manager: {
        ownerElementType: 'User',
        ownerId: 31,
        ownerSiteId: 1,
        attribute: 'addresses',
        fieldId: null as unknown as number,
        elementType: 'Address',
        canCreate,
        canPaste: false,
        sortable: false,
        createButtonLabel: 'New address',
        createAttributes: [{label: 'New address', attributes: {}}],
        pasteableData: null,
      },
      cards: [address(14)],
    };

    app = createApp({
      setup: () => () =>
        h(NestedElements as any, {
          path: ['addresses'],
          nested,
          editable: true,
          owner: savedNestedOwner(31, refresh),
        }),
    });
    app.mount(root);
  }

  function action(label: string): HTMLElement | undefined {
    return [...root.querySelectorAll<HTMLElement>('craft-action-item')].find(
      (item) => item.textContent?.trim() === label
    );
  }

  it('runs nested actions against a saved owner outside an element editor', async () => {
    window.confirm = vi.fn().mockReturnValue(true);
    const refresh = vi.fn(async () => {});
    mount(refresh);
    await nextTick();

    action('Delete address')!.click();
    await vi.waitFor(() => expect(refresh).toHaveBeenCalledOnce());

    expect(actions.run).toHaveBeenCalledWith(
      expect.objectContaining({
        url: expect.stringContaining('element-indexes/perform-action'),
        body: expect.objectContaining({
          elementAction: DELETE_ACTION,
          ownerId: 31,
          attribute: 'addresses',
          elementIds: [14],
        }),
      }),
      expect.anything()
    );
  });

  it('creates nested elements for a saved owner and edits them in a slideout', async () => {
    request.post.mockResolvedValue({
      data: {element: {id: 92, siteId: 1, draftId: 44}},
    });
    const refresh = vi.fn(async () => {});
    mount(refresh, true);
    await nextTick();

    [...root.querySelectorAll<HTMLElement>('[data-create-element]')]
      .find((button) => button.textContent?.trim() === 'New address')!
      .click();
    await vi.waitFor(() => expect(slideout).toHaveBeenCalledOnce());

    expect(request.post).toHaveBeenCalledWith(
      expect.stringContaining('elements/create'),
      expect.objectContaining({elementType: 'Address', ownerId: 31})
    );
    const editUrl = new URL(slideout.mock.calls[0]![0], window.location.origin);
    expect(Object.fromEntries(editUrl.searchParams)).toMatchObject({
      elementId: '92',
      ownerId: '31',
      fresh: '1',
    });
  });
});

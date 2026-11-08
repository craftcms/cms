import $ from 'jquery';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type * as Clipboard from './clipboard';

const messages = vi.hoisted(() => ({
  showError: vi.fn(),
  showSuccess: vi.fn(),
}));
vi.mock('@/modules/messages/messages', () => messages);

const broadcasts = vi.hoisted(() => ({
  sent: [] as unknown[],
  handlers: new Map<string, () => void>(),
}));
vi.mock('@/common/craft/broadcast', () => ({
  broadcast: (message: unknown) => broadcasts.sent.push(message),
  onBroadcast: (event: string, handler: () => void) => {
    broadcasts.handlers.set(event, handler);
    return () => {};
  },
}));

const STORAGE_KEY = 'Craft-test.copiedElements';
const entry = {type: 'Entry', id: 5, siteId: 1};

/** Stands in for the card, which fills the elements in and then fires `show`. */
const {Card} = vi.hoisted(() => {
  class Card {
    static last: Card | null = null;
    loaded = false;
    closed = false;
    shown = false;
    clipboardFormats: {text: string; html: string | null} | null = null;
    loadedFormats: Promise<{text: string; html: string | null} | null>;
    #resolveFormats!: (formats: {text: string; html: string | null}) => void;
    #onShow: Array<() => void> = [];

    constructor(
      public elementInfo: Clipboard.CopiedElementInfo[],
      public onCancel: () => void
    ) {
      Card.last = this;
      this.loadedFormats = new Promise((resolve) => {
        this.#resolveFormats = resolve;
      });
    }

    show() {
      this.shown = true;
    }

    on(_event: 'show', handler: () => void) {
      this.#onShow.push(handler);
    }

    load(
      elements = this.elementInfo,
      formats: {text: string; html: string | null} = {text: 'Entry', html: null}
    ) {
      this.elementInfo = elements;
      this.clipboardFormats = formats;
      this.#resolveFormats(formats);
      this.loaded = true;
      this.#onShow.forEach((handler) => handler());
    }

    close() {
      this.closed = true;
    }
  }

  return {Card};
});
vi.mock('./copied-elements-notification', () => ({
  CopiedElementsNotification: Card,
}));

/** `Craft.CP` before the clipboard takes its methods over. */
function legacyCp() {
  class CP {
    copyElementCallbacks: unknown[] = [];

    onCopyElements(callback: unknown) {
      this.copyElementCallbacks.push(callback);
    }
  }

  return CP;
}

let clipboard: typeof Clipboard;
let actionClient: typeof import('@craftcms/ui/utilities/api/actionClient').actionClient;
let cp: any;

beforeEach(async () => {
  vi.resetModules();
  localStorage.clear();
  broadcasts.sent.length = 0;
  broadcasts.handlers.clear();
  Card.last = null;
  // Fresh copies, so the clipboard's state doesn't carry between tests
  const {ConfigService} = await import('@craftcms/ui/services/Config');
  ({actionClient} = await import('@craftcms/ui/utilities/api/actionClient'));
  ConfigService.getInstance().initialize({
    userId: 1,
    elementTypeNames: {Entry: ['Entry', 'Entries', 'entry', 'entries']},
  });
  const CP = legacyCp();
  cp = new CP();
  vi.stubGlobal('Craft', {systemUid: 'test', CP, cp});
  clipboard = await import('./clipboard');
});

afterEach(() => {
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
  Object.values(messages).forEach((fn) => fn.mockReset());
});

describe('the element clipboard', () => {
  it('stores copied elements, shows the card and tells other tabs', () => {
    clipboard.installClipboard();
    clipboard.copyElements([entry]);

    expect(JSON.parse(localStorage.getItem(STORAGE_KEY)!)).toEqual([entry]);
    expect(Card.last?.shown).toBe(true);
    expect(broadcasts.sent).toEqual([{event: 'copyElements'}]);
  });

  it('puts the elements’ titles on the system clipboard as it’s copied, once they’re loaded', async () => {
    const items: Record<string, Promise<Blob>>[] = [];
    vi.stubGlobal(
      'ClipboardItem',
      class {
        constructor(record: Record<string, Promise<Blob>>) {
          items.push(record);
        }
      }
    );
    const write = vi.fn(async () => {});
    vi.stubGlobal('navigator', {clipboard: {write}});
    clipboard.installClipboard();
    clipboard.copyElements([entry]);

    expect(write).toHaveBeenCalledOnce();
    expect(clipboard.isCopiedElementsText('Apples\nPears')).toBe(false);

    Card.last!.load([entry], {
      text: 'Apples\nPears',
      html: '<a href="https://example.com/apples">Apples</a><br>Pears',
    });

    await expect(
      items[0]!['text/plain']!.then((blob) => blob.text())
    ).resolves.toBe('Apples\nPears');
    await expect(
      items[0]!['text/html']!.then((blob) => blob.text())
    ).resolves.toBe('<a href="https://example.com/apples">Apples</a><br>Pears');
    expect(clipboard.isCopiedElementsText('Apples\r\nPears\r\n')).toBe(true);
    expect(clipboard.isCopiedElementsText('Apples')).toBe(false);
  });

  it('only writes text that’s ready while the click is handled, in browsers without ClipboardItem', async () => {
    vi.stubGlobal('ClipboardItem', undefined);
    const writeText = vi.fn(async () => {});
    vi.stubGlobal('navigator', {clipboard: {writeText}});
    clipboard.installClipboard();

    // Elements' titles load after the click, so they'd be refused
    clipboard.copyElements([entry]);
    Card.last!.load([entry], {text: 'Apples', html: null});
    await Promise.resolve();
    expect(writeText).not.toHaveBeenCalled();

    (await import('@/modules/ui/copiedFieldValue')).copyFieldValue({
      value: 'Hi',
      component: 'craft:text',
      label: 'Title',
      text: 'Hi',
    });
    expect(writeText).toHaveBeenCalledExactlyOnceWith('Hi');
  });

  it('keeps one thing on the clipboard: elements or a field value', async () => {
    const values = await import('@/modules/ui/copiedFieldValue');
    const value = {
      value: 'Hi',
      component: 'craft:text',
      label: 'Title',
      text: 'Hi',
    };
    clipboard.installClipboard();
    values.installFieldValueClipboard();

    values.copyFieldValue(value);
    clipboard.copyElements([entry]);

    expect(values.useCopiedFieldValue().value).toBeNull();
    expect(localStorage.getItem('Craft-test.copiedFieldValue')).toBeNull();

    const card = Card.last!;
    values.copyFieldValue(value);

    expect(localStorage.getItem(STORAGE_KEY)).toBeNull();
    expect(card.closed).toBe(true);
    expect(values.useCopiedFieldValue().value).toEqual(value);
  });

  it('tells listeners about the elements once the card has filled them in', () => {
    clipboard.installClipboard();
    const listener = vi.fn();
    clipboard.onCopiedElementsChange(listener);
    clipboard.copyElements([entry, {...entry, id: 6}]);

    expect(listener).not.toHaveBeenCalled();
    expect(clipboard.getCopiedElements()).toEqual([]);

    // One of them no longer exists
    const filled = [{...entry, data: {entryTypeId: 3}}];
    Card.last!.load(filled);

    expect(listener).toHaveBeenCalledWith(filled, 'Paste entry');
    expect(clipboard.getCopiedElements()).toEqual(filled);
  });

  it('clears the clipboard and the card', () => {
    clipboard.installClipboard();
    const listener = vi.fn();
    clipboard.onCopiedElementsChange(listener);
    clipboard.copyElements([entry]);
    const card = Card.last!;
    clipboard.clearCopiedElements();

    expect(localStorage.getItem(STORAGE_KEY)).toBeNull();
    expect(card.closed).toBe(true);
    expect(listener).toHaveBeenLastCalledWith([], null);
  });

  it('shows what was copied earlier, and what other tabs copy', () => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify([entry]));
    clipboard.installClipboard();

    expect(Card.last?.elementInfo).toEqual([entry]);

    const other = {...entry, id: 9};
    localStorage.setItem(STORAGE_KEY, JSON.stringify([other]));
    broadcasts.handlers.get('copyElements')!();

    expect(Card.last?.elementInfo).toEqual([other]);
  });

  it('duplicates the copied elements when pasting, then clears them', async () => {
    clipboard.installClipboard();
    clipboard.copyElements([entry]);
    const post = vi.spyOn(actionClient, 'post').mockResolvedValue({
      data: {message: 'Entry pasted.', newElements: [{id: 50}]},
    } as never);

    const pasted = await clipboard.pasteElements({ownerId: 7});

    expect(post).toHaveBeenCalledWith('elements/bulk-duplicate', {
      elements: [entry],
      newAttributes: {ownerId: 7},
    });
    expect(pasted).toEqual([{id: 50}]);
    expect(messages.showSuccess).toHaveBeenCalledWith('Entry pasted.');
    expect(localStorage.getItem(STORAGE_KEY)).toBeNull();
  });

  it('keeps the clipboard and shows the error when a paste fails', async () => {
    clipboard.installClipboard();
    clipboard.copyElements([entry]);
    vi.spyOn(actionClient, 'post').mockRejectedValue(new Error('offline'));

    await expect(clipboard.pasteElements()).resolves.toEqual([]);
    expect(messages.showError).toHaveBeenCalledWith(null);
    expect(localStorage.getItem(STORAGE_KEY)).not.toBeNull();
  });
});

describe('the field value clipboard', () => {
  it('only shows a copied value to the user who copied it', async () => {
    const value = {
      value: 'Hi',
      component: 'craft:text',
      label: 'Title',
      text: 'Hi',
    };
    (await import('@/modules/ui/copiedFieldValue')).copyFieldValue(value);

    /** Loads the page again, with `userId` signed in. */
    const signIn = async (userId: number) => {
      vi.resetModules();
      const {ConfigService} = await import('@craftcms/ui/services/Config');
      ConfigService.getInstance().initialize({userId});
      const values = await import('@/modules/ui/copiedFieldValue');
      values.installFieldValueClipboard();

      return values.useCopiedFieldValue().value;
    };

    expect(await signIn(1)).toEqual(value);
    // Someone else signs in, in the same browser
    expect(await signIn(2)).toBeNull();
    expect(localStorage.getItem('Craft-test.copiedFieldValue')).toBeNull();
  });
});

describe('Craft.cp', () => {
  it('copies legacy element markup through the clipboard', () => {
    vi.stubGlobal('jQuery', $);
    clipboard.installClipboard();
    const $element = $(
      '<div data-type="Entry" data-id="5" data-site-id="1" data-draft-id="2"></div>'
    );

    cp.copyElements($element);

    expect(JSON.parse(localStorage.getItem(STORAGE_KEY)!)).toEqual([
      {type: 'Entry', id: 5, siteId: 1, draftId: 2},
    ]);
  });

  it('keeps callbacks registered before the clipboard took over', () => {
    const callback = vi.fn();
    cp.onCopyElements(callback);
    clipboard.installClipboard();
    clipboard.copyElements([entry]);
    Card.last!.load();

    expect(callback).toHaveBeenCalledWith([entry], 'Paste entry');
  });
});

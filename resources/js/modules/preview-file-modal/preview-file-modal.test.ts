import $ from 'jquery';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {PreviewFileModal} from './preview-file-modal';

const sendActionRequest = vi.fn();
const cp = {announce: vi.fn(), displayError: vi.fn(), displayNotice: vi.fn()};

function openDialogs(): HTMLElement[] {
  return [...document.body.querySelectorAll<HTMLElement>('craft-dialog')];
}

function footerItems(dialog: HTMLElement): HTMLElement[] {
  return [...dialog.querySelectorAll<HTMLElement>(':scope > [slot="footer"]')];
}

async function settle(): Promise<void> {
  for (let i = 0; i < 4; i++) {
    await Promise.resolve();
  }
}

/** Makes preview content report more height than fits, as a long file's does. */
function stubOverflow(): void {
  vi.spyOn(HTMLElement.prototype, 'scrollHeight', 'get').mockImplementation(
    function (this: HTMLElement) {
      return this.classList.contains('previewmodal-content') ? 1000 : 0;
    }
  );
  vi.spyOn(HTMLElement.prototype, 'clientHeight', 'get').mockReturnValue(100);
}

/** A stand-in for the legacy `Garnish.Select` holding asset elements. */
function elementSelect(ids: number[]) {
  const $items = $(
    ids.map((id) => `<div class="element" data-id="${id}"></div>`).join('')
  );
  return {
    $items,
    getItemIndex: ($item: any) => $items.index($item),
    getPreviousItem: (index: number) => $items.eq(index - 1),
    getNextItem: (index: number) => $items.eq(index + 1),
    deselectAll: vi.fn(),
    selectItem: vi.fn(),
    focusItem: vi.fn(),
  };
}

describe('PreviewFileModal', () => {
  beforeEach(() => {
    vi.stubGlobal('$', $);
    vi.stubGlobal('Craft', {
      t: (_category: string, message: string) => message,
      cp,
      sendActionRequest,
      getActionUrl: (action: string) => `/actions/${action}`,
      appendHeadHtml: vi.fn(),
      appendBodyHtml: vi.fn(),
    });
    sendActionRequest.mockImplementation(
      async (_method: string, _action: string, {data}: any) => ({
        data: {
          requestId: data.requestId,
          previewHtml: `<p class="preview">${data.assetId}</p>`,
        },
      })
    );
  });

  afterEach(() => {
    PreviewFileModal.openInstance?.quickHide();
    document.body.replaceChildren();
    vi.unstubAllGlobals();
    vi.clearAllMocks();
    vi.restoreAllMocks();
  });

  it('shows the preview in a dialog, and returns focus once it closes', async () => {
    const opener = document.createElement('button');
    document.body.append(opener);
    opener.focus();

    const modal = new PreviewFileModal(5, {
      startingWidth: 400,
      startingHeight: 300,
    });
    await settle();

    const [dialog] = openDialogs();
    expect(dialog!.querySelector('.preview')!.textContent).toBe('5');
    expect(dialog!.style.getPropertyValue('--c-dialog-inline-size')).toBe(
      '400px'
    );
    expect(dialog!.style.getPropertyValue('--c-dialog-block-size')).toBe(
      '300px'
    );

    modal.hide();
    dialog!.dispatchEvent(new Event('craft-after-hide'));

    expect(openDialogs()).toHaveLength(0);
    expect(PreviewFileModal.openInstance).toBeNull();
    expect(document.activeElement).toBe(opener);
  });

  it('highlights a text preview’s code, keeping markup in it as text', async () => {
    sendActionRequest.mockResolvedValueOnce({
      data: {
        requestId: 1,
        previewHtml:
          '<pre><code data-language="json">{"a": "&lt;b&gt;"}</code></pre>',
      },
    });
    new PreviewFileModal(5);
    await settle();

    const code = openDialogs()[0]!.querySelector('code')!;
    expect(code.querySelector('b')).toBeNull();
    expect(code.textContent).toBe('{"a": "<b>"}');
    expect(code.querySelector('.th-property')!.textContent).toBe('"a"');
    expect(code.querySelector('.th-string')!.textContent).toBe('"<b>"');
  });

  it('offers to download, copy, and close a text preview', async () => {
    const writeText = vi
      .spyOn(navigator.clipboard, 'writeText')
      .mockResolvedValue(undefined);
    sendActionRequest.mockResolvedValueOnce({
      data: {
        requestId: 1,
        previewHtml:
          '<pre><code data-language="text">a &lt;b&gt; c</code></pre>',
      },
    });
    const modal = new PreviewFileModal(5);
    await settle();

    const [dialog] = openDialogs();
    const buttons = footerItems(dialog!);
    expect(buttons.map((button) => button.textContent)).toEqual([
      'Download',
      'Copy',
      'Close',
    ]);

    buttons[1]!.click();
    await settle();
    expect(writeText).toHaveBeenCalledWith('a <b> c');
    expect(cp.displayNotice).toHaveBeenCalledWith('Copied to clipboard.');

    buttons[2]!.click();
    expect(modal.visible).toBe(false);
  });

  it('reports when a text preview couldn’t be copied', async () => {
    vi.spyOn(navigator.clipboard, 'writeText').mockRejectedValue(
      new Error('Denied')
    );
    sendActionRequest.mockResolvedValueOnce({
      data: {
        requestId: 1,
        previewHtml: '<pre><code data-language="text">abc</code></pre>',
      },
    });
    new PreviewFileModal(5);
    await settle();

    footerItems(openDialogs()[0]!)[1]!.click();
    await settle();

    expect(cp.displayError).toHaveBeenCalledWith(
      'Couldn’t copy to the clipboard.'
    );
  });

  it('moves a preview’s own footer controls into the footer, until the next asset loads', async () => {
    const select = elementSelect([1, 2, 3]);
    sendActionRequest
      .mockResolvedValueOnce({
        data: {
          requestId: 1,
          previewHtml: '<button slot="footer">Focal Point</button><img alt="">',
        },
      })
      .mockResolvedValueOnce({
        data: {
          requestId: 2,
          previewHtml: '<pre><code data-language="text">abc</code></pre>',
        },
      });
    PreviewFileModal.showForAsset(select.$items.eq(0), select);
    await settle();

    const [dialog] = openDialogs();
    const labels = () => footerItems(dialog!).map((item) => item.textContent);
    expect(labels()).toEqual(['Focal Point', 'Download', 'Close']);

    const right = () =>
      dialog!.dispatchEvent(
        new KeyboardEvent('keydown', {key: 'ArrowRight', cancelable: true})
      );
    right();
    await settle();
    expect(labels()).toEqual(['Download', 'Copy', 'Close']);

    right();
    await settle();
    expect(dialog!.querySelector('.preview')!.textContent).toBe('3');
    expect(labels()).toEqual(['Download', 'Close']);
  });

  it('downloads the asset being previewed', async () => {
    const submit = vi
      .spyOn(HTMLFormElement.prototype, 'submit')
      .mockImplementation(function (this: HTMLFormElement) {
        expect(this.action).toContain('/actions/assets/download-asset');
        expect(
          this.querySelector<HTMLInputElement>('[name="assetId"]')!.value
        ).toBe('2');
      });
    const select = elementSelect([1, 2]);
    PreviewFileModal.showForAsset(select.$items.eq(0), select);
    await settle();

    const [dialog] = openDialogs();
    dialog!.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'ArrowRight', cancelable: true})
    );
    await settle();
    footerItems(dialog!)[0]!.click();

    expect(submit).toHaveBeenCalledOnce();
  });

  it('moves between the assets of its element select in the same dialog', async () => {
    const select = elementSelect([1, 2, 3]);
    PreviewFileModal.showForAsset(select.$items.eq(1), select);
    await settle();

    const [dialog] = openDialogs();
    const right = new KeyboardEvent('keydown', {
      key: 'ArrowRight',
      cancelable: true,
    });
    dialog!.dispatchEvent(right);
    await settle();

    expect(right.defaultPrevented).toBe(true);
    expect(openDialogs()).toEqual([dialog]);
    expect(dialog!.querySelector('.preview')!.textContent).toBe('3');
    expect(select.selectItem).toHaveBeenCalledWith(
      select.$items.eq(2),
      false,
      false
    );

    dialog!.dispatchEvent(new Event('craft-after-hide'));
    expect(select.focusItem).toHaveBeenCalledWith(select.$items.eq(2));
  });

  it('focuses the loaded preview, and makes it a tab stop when it overflows', async () => {
    new PreviewFileModal(5);
    await settle();

    const content = openDialogs()[0]!.querySelector<HTMLElement>(
      '.previewmodal-content'
    )!;
    expect(document.activeElement).toBe(content);
    expect(content.tabIndex).toBe(-1);

    PreviewFileModal.openInstance!.quickHide();
    stubOverflow();
    new PreviewFileModal(5);
    await settle();

    expect(document.activeElement).toBe(
      openDialogs()[0]!.querySelector('.previewmodal-content')
    );
    expect(document.activeElement!.getAttribute('tabindex')).toBe('0');
  });

  it('scrolls an overflowing preview with the up and down arrows, rather than moving between assets', async () => {
    stubOverflow();
    const select = elementSelect([1, 2, 3]);
    PreviewFileModal.showForAsset(select.$items.eq(1), select);
    await settle();

    const [dialog] = openDialogs();
    const down = new KeyboardEvent('keydown', {
      key: 'ArrowDown',
      cancelable: true,
    });
    dialog!.dispatchEvent(down);
    await settle();

    expect(down.defaultPrevented).toBe(false);
    expect(dialog!.querySelector('.preview')!.textContent).toBe('2');

    const right = new KeyboardEvent('keydown', {
      key: 'ArrowRight',
      cancelable: true,
    });
    dialog!.dispatchEvent(right);
    await settle();

    expect(right.defaultPrevented).toBe(true);
    expect(dialog!.querySelector('.preview')!.textContent).toBe('3');
  });

  it('leaves arrow keys alone without an element select', async () => {
    new PreviewFileModal(5);
    await settle();

    const right = new KeyboardEvent('keydown', {
      key: 'ArrowRight',
      cancelable: true,
    });
    openDialogs()[0]!.dispatchEvent(right);

    expect(right.defaultPrevented).toBe(false);
  });
});

import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {BaseElementSelectInput} from './base-element-select-input';

vi.mock('@/common/craft/icon-svg', () => ({
  iconSvg: async () => document.createElement('svg'),
}));

function chip(id: number): string {
  return `<li><div class="element chip small" data-id="${id}" data-site-id="1">
    <input type="hidden" name="related[]" value="${id}">
    <div class="chip-content"><span class="label">Entry ${id}</span><div class="chip-actions"></div></div>
  </div></li>`;
}

function boot(settings = {}): BaseElementSelectInput {
  document.body.innerHTML = `
    <div id="related" class="elementselect">
      <ul class="elements">${chip(1)}${chip(2)}</ul>
      <div class="flex"><button type="button" class="btn add">Add</button></div>
    </div>`;

  return new BaseElementSelectInput({
    id: 'related',
    name: 'related',
    elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    ...settings,
  });
}

const container = () => document.getElementById('related')!;
const addButton = () => container().querySelector('.btn.add')!;
const postedIds = () =>
  [
    ...container().querySelectorAll<HTMLInputElement>(
      'input[name="related[]"]'
    ),
  ].map((input) => input.value);

beforeEach(() => {
  expect(window.jQuery).toBeUndefined();
});

afterEach(() => {
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

it('reads its chips and hides Add once the limit is reached', () => {
  const input = boot({limit: 2});

  expect(input.getSelectedElementIds()).toEqual([1, 2]);
  expect(addButton().classList.contains('hidden')).toBe(true);
});

it('removes a chip from the value and frees its slot', async () => {
  const input = boot({limit: 2});
  const change = vi.fn();
  container().addEventListener('change', change);

  input.removeElement(input.getElementById(1)!);

  expect(input.getSelectedElementIds()).toEqual([2]);
  expect(postedIds()).toEqual(['2']);
  expect(addButton().classList.contains('hidden')).toBe(false);
  expect(change).toHaveBeenCalled();
});

it('adds the modal selection as chips posted with the field', async () => {
  vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {
      elements: {
        3: [
          '<div class="element chip small" data-id="3" data-site-id="1"><div class="chip-content"><div class="chip-actions"></div></div></div>',
        ],
      },
      headHtml: '',
      bodyHtml: '',
    },
  } as never);
  const input = boot();

  await input.onModalSelect([{id: 3, siteId: 1}]);

  expect(input.getSelectedElementIds()).toEqual([1, 2, 3]);
  expect(postedIds()).toEqual(['1', '2', '3']);
});

it('swaps the chip staged for replacement with the selection', async () => {
  vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {
      elements: {
        5: [
          '<div class="element chip small" data-id="5" data-site-id="1"><div class="chip-content"><div class="chip-actions"></div></div></div>',
        ],
      },
      headHtml: '',
      bodyHtml: '',
    },
  } as never);
  const input = boot();
  vi.spyOn(input, 'createModal').mockResolvedValue({
    show: vi.fn(),
    hide: vi.fn(),
    setDisabledElementIds: vi.fn(),
  });

  input.showReplaceModal(input.getElementById(1)!);
  await input.onModalSelect([{id: 5, siteId: 1}]);

  expect(input.getSelectedElementIds()).toEqual([2, 5]);
});

it('moves a chip when its reorder button asks to', () => {
  const input = boot();
  const button = input
    .getElementById(2)!
    .querySelector('craft-reorder-button')!;

  button.dispatchEvent(
    new CustomEvent('craft-reorder', {detail: {direction: 'up'}, bubbles: true})
  );

  expect(input.getSelectedElementIds()).toEqual([2, 1]);
  expect(postedIds()).toEqual(['2', '1']);
});

it("opens its chips' Edit items with its own settings, ahead of the global listener", () => {
  const openSlideout = vi.fn();
  vi.stubGlobal('Craft', {...window.Craft, openSlideout});
  const globalListener = vi.fn();
  window.addEventListener('craft:edit-element', globalListener);
  const input = boot({prevalidate: true});

  const editFrom = (trigger: Element) =>
    window.dispatchEvent(
      new CustomEvent('craft:edit-element', {detail: {trigger}})
    );

  editFrom(input.getElementById(2)!.querySelector('.chip-actions')!);
  expect(openSlideout).toHaveBeenCalledOnce();
  expect(openSlideout.mock.calls[0]![0]).toMatch(/elementId=2.*prevalidate=1/);
  expect(globalListener).not.toHaveBeenCalled();

  editFrom(addButton());
  expect(globalListener).toHaveBeenCalledOnce();

  input.destroy();
  editFrom(document.querySelector('.chip-actions')!);
  expect(openSlideout).toHaveBeenCalledOnce();
  expect(globalListener).toHaveBeenCalledTimes(2);

  window.removeEventListener('craft:edit-element', globalListener);
  vi.unstubAllGlobals();
});

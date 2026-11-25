import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import '@/modules/messages/components/cp-messages';
import {forgetCopiedMinimized} from '@/modules/messages/copied-notification';
import {Notification} from '@/modules/messages/notification';
import {CopiedElementsNotification} from './copied-elements-notification';

vi.mock('@/common/craft/icon-svg', () => ({iconSvg: async () => null}));

const chip = (id: number, label: string, url = '') =>
  `<div class="chip" data-id="${id}" data-site-id="1" data-label="${label}"${url ? ` data-url="${url}"` : ''}>${label}</div>`;

const post = vi.spyOn(actionClient, 'post');

beforeEach(() => {
  vi.stubGlobal('Craft', {notificationDuration: 0});
  document.body.innerHTML = '<cp-messages></cp-messages>';
  post.mockResolvedValue({
    data: {
      elements: {
        7: [chip(7, 'Apples', 'https://example.com/apples')],
        8: [chip(8, '1984')],
      },
    },
  } as never);
});

afterEach(() => {
  localStorage.clear();
  post.mockReset();
  vi.unstubAllGlobals();
  document.body.innerHTML = '';
});

it('shows the copied elements that still exist as chips side by side, and puts their titles on the clipboard', async () => {
  const card = new CopiedElementsNotification(
    [7, 8, 9].map((id) => ({type: 'Entry', id, siteId: 1})),
    () => {},
    {animate: false}
  );
  await card.show();

  expect(card.elementInfo.map(({id}) => id)).toEqual([7, 8]);
  const chips = [
    ...document.querySelectorAll<HTMLElement>(
      '.copied-notification-preview .copied-chips > .chip'
    ),
  ];
  expect(chips.map((chip) => chip.textContent)).toEqual(['Apples', '1984']);
  expect(
    chips.every(
      (chip) =>
        chip.getAttribute('size') === 'xsmall' && chip.hasAttribute('rounded')
    )
  ).toBe(true);
  expect(
    document.querySelector<HTMLElement>('.copied-notification-preview')!.inert
  ).toBe(true);
  expect(
    document.querySelector('.copied-notification-value .sr-only')!.textContent
  ).toBe('Apples\n1984');
  await expect(card.loadedFormats).resolves.toEqual({
    text: 'Apples\n1984',
    html: '<a href="https://example.com/apples">Apples</a><br>1984',
  });
});

it('cancels the copy when none of the elements exist anymore', async () => {
  post.mockResolvedValue({
    data: {elements: []},
  } as never);
  const cancel = vi.fn();
  const card = new CopiedElementsNotification(
    [{type: 'Entry', id: 9, siteId: 1}],
    cancel,
    {animate: false}
  );
  await card.show();

  expect(cancel).toHaveBeenCalledOnce();
  await expect(card.loadedFormats).resolves.toBeNull();
});

it('keeps the copy, without titles for the system clipboard, when the chips can’t be loaded', async () => {
  post.mockRejectedValue(new Error('Offline'));
  const cancel = vi.fn();
  const elements = [{type: 'Entry', id: 7, siteId: 1}];
  const card = new CopiedElementsNotification(elements, cancel, {
    animate: false,
  });
  await card.show();

  expect(cancel).not.toHaveBeenCalled();
  expect(card.loaded).toBe(true);
  expect(card.elementInfo).toEqual(elements);
  expect(document.querySelector('.notification-message')?.textContent).toMatch(
    /copied\.$/
  );
  await expect(card.loadedFormats).resolves.toBeNull();
});

it('minimizes below newer notifications, and stays minimized on later pages until something new is copied', async () => {
  const elements = [7, 8].map((id) => ({type: 'Entry', id, siteId: 1}));
  const showCard = async () => {
    const card = new CopiedElementsNotification(elements, () => {}, {
      animate: false,
    });
    await card.show();
    return card;
  };
  const messages = document.querySelector('cp-messages')!;
  const dock = messages.querySelector('.messages-dock')!;

  const card = await showCard();
  card
    .element!.querySelector<HTMLElement>('.copied-notification-minimize')!
    .click();
  const saved = new Notification('success', 'Saved.', {animate: false});
  await saved.show();

  expect(card.element!.parentElement).toBe(dock);
  expect(
    messages.querySelector('.messages-stack')!.contains(saved.element)
  ).toBe(true);
  expect(messages.style.getPropertyValue('--cp-messages-count')).toBe('1');
  expect(
    card.element!.querySelector('.copied-notification-count')!.textContent
  ).toBe('2');

  card.element!.remove();
  const nextPage = await showCard();
  expect(nextPage.element!.parentElement).toBe(dock);

  nextPage
    .element!.querySelector<HTMLElement>('.copied-notification-expand')!
    .click();
  expect(nextPage.element!.closest('[role="status"]')).not.toBeNull();
  expect(Number(nextPage.element!.dataset.shownAt)).toBeGreaterThanOrEqual(
    Number(saved.element!.dataset.shownAt)
  );

  nextPage
    .element!.querySelector<HTMLElement>('.copied-notification-minimize')!
    .click();
  nextPage.element!.remove();
  forgetCopiedMinimized();
  const newCopy = await showCard();
  expect(newCopy.element!.closest('[role="status"]')).not.toBeNull();
});

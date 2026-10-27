import {describe, expect, it, vi} from 'vite-plus/test';

import {
  type UploadDuplicate,
  UploadDuplicatesNotificationElement,
} from './upload-duplicates-notification.ce';

const duplicate = (id: number, title: string): UploadDuplicate => ({
  id,
  title,
  url: `/admin/assets/edit/${id}`,
  chipHtml: `<craft-chip data-id="${id}"><a class="label-link" href="/admin/assets/edit/${id}"><span>${title}</span></a></craft-chip>`,
});

async function notification(count = 2): Promise<{
  element: UploadDuplicatesNotificationElement;
  chips: HTMLElement[];
  links: HTMLAnchorElement[];
}> {
  const element = new UploadDuplicatesNotificationElement();
  element.duplicates = Array.from({length: count}, (_, index) =>
    duplicate(5 + index, 'Harbor at dusk')
  );
  document.body.replaceChildren(element);
  await element.updateComplete;

  return {
    element,
    chips: [...element.querySelectorAll<HTMLElement>('.cp-chip-stack__chip')],
    links: [...element.querySelectorAll('a')],
  };
}

function click(link: HTMLAnchorElement, init: MouseEventInit = {}): MouseEvent {
  const event = new MouseEvent('click', {
    bubbles: true,
    cancelable: true,
    ...init,
  });
  link.dispatchEvent(event);
  return event;
}

describe('craft-upload-duplicates-notification', () => {
  it('shows the closest image as a chip linked to its edit page', async () => {
    const {chips, links} = await notification(1);

    expect(chips).toHaveLength(1);
    expect(chips[0]!.querySelector('craft-chip')!.dataset.id).toBe('5');
    expect(links[0]!.getAttribute('href')).toBe('/admin/assets/edit/5');
    expect(chips[0]!.inert).toBe(false);
    expect(chips[0]!.hasAttribute('aria-hidden')).toBe(false);
  });

  it('stacks other images faded behind it, out of reach', async () => {
    const {chips} = await notification(3);

    expect(chips.map((chip) => chip.inert)).toEqual([false, true, true]);
    expect(chips.map((chip) => chip.getAttribute('aria-hidden'))).toEqual([
      null,
      'true',
      'true',
    ]);
    expect(
      chips.map((chip) => [
        chip.style.insetInlineStart,
        chip.style.getPropertyValue('--overlay-opacity'),
      ])
    ).toEqual([
      ['', ''],
      ['4px', '0.4'],
      ['8px', '0.55'],
    ]);
    expect(Number(chips[0]!.style.zIndex)).toBeGreaterThan(
      Number(chips[1]!.style.zIndex)
    );
  });

  it('stacks at most four images behind the closest one', async () => {
    const {chips} = await notification(8);

    expect(chips).toHaveLength(5);
  });

  it('leaves links to be followed unless the navigation is handled', async () => {
    const {element, links} = await notification(1);

    expect(click(links[0]!).defaultPrevented).toBe(false);

    const navigate = vi.fn((event: Event) => event.preventDefault());
    element.addEventListener('craft-upload-navigate', navigate);

    expect(click(links[0]!).defaultPrevented).toBe(true);
    expect((navigate.mock.calls[0]![0] as CustomEvent<string>).detail).toBe(
      '/admin/assets/edit/5'
    );
  });

  it('leaves modified clicks to the browser', async () => {
    const {element, links} = await notification(1);
    const navigate = vi.fn((event: Event) => event.preventDefault());
    element.addEventListener('craft-upload-navigate', navigate);

    expect(click(links[0]!, {metaKey: true}).defaultPrevented).toBe(false);
    expect(navigate).not.toHaveBeenCalled();
  });
});

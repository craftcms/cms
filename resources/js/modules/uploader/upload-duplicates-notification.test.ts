import {describe, expect, it, vi} from 'vite-plus/test';

import {UploadDuplicatesNotificationElement} from './upload-duplicates-notification.ce';

async function notification(): Promise<{
  element: UploadDuplicatesNotificationElement;
  links: HTMLAnchorElement[];
}> {
  const element = new UploadDuplicatesNotificationElement();
  element.duplicates = [
    {id: 5, title: 'Harbor at dusk', url: '/admin/assets/edit/5'},
    {id: 6, title: 'Harbor at dusk', url: '/admin/assets/edit/6'},
  ];
  document.body.append(element);
  await element.updateComplete;

  return {element, links: [...element.querySelectorAll('a')]};
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
  it('lists each image as a link to its edit page', async () => {
    const {links} = await notification();

    expect(
      links.map((link) => [link.textContent, link.getAttribute('href')])
    ).toEqual([
      ['Harbor at dusk', '/admin/assets/edit/5'],
      ['Harbor at dusk', '/admin/assets/edit/6'],
    ]);
  });

  it('leaves links to be followed unless the navigation is handled', async () => {
    const {element, links} = await notification();

    expect(click(links[0]!).defaultPrevented).toBe(false);

    const navigate = vi.fn((event: Event) => event.preventDefault());
    element.addEventListener('craft-upload-navigate', navigate);

    expect(click(links[1]!).defaultPrevented).toBe(true);
    expect((navigate.mock.calls[0]![0] as CustomEvent<string>).detail).toBe(
      '/admin/assets/edit/6'
    );
  });

  it('leaves modified clicks to the browser', async () => {
    const {element, links} = await notification();
    const navigate = vi.fn((event: Event) => event.preventDefault());
    element.addEventListener('craft-upload-navigate', navigate);

    expect(click(links[0]!, {metaKey: true}).defaultPrevented).toBe(false);
    expect(navigate).not.toHaveBeenCalled();
  });
});

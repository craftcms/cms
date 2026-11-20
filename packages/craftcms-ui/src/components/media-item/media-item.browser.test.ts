import {describe, expect, it} from 'vite-plus/test';
import type CraftMediaItem from './media-item.js';
import './media-item.js';

async function mount(markup: string, width = ''): Promise<CraftMediaItem> {
  document.body.innerHTML = `<div class="frame" style="font: 16px/1.5 sans-serif; ${width}">${markup}</div>`;
  const item = document.querySelector('craft-media-item') as CraftMediaItem;
  await item.updateComplete;
  return item;
}

function part(item: CraftMediaItem, name: string): HTMLElement | null {
  return item.shadowRoot!.querySelector(`[part~="${name}"]`);
}

describe('craft-media-item', () => {
  it('draws the icon in place of a missing thumbnail, hidden from screen readers', async () => {
    const item = await mount(
      `<craft-media-item icon="newspaper"><span slot="heading">Annual report</span></craft-media-item>`
    );
    const icon = part(item, 'thumbnail')!.querySelector('craft-icon')!;
    await (icon as HTMLElement & {updateComplete: Promise<unknown>})
      .updateComplete;

    expect(icon.getAttribute('name')).toBe('newspaper');
    expect(icon.getAttribute('aria-hidden')).toBe('true');
    expect(icon.getAttribute('role')).toBeNull();
  });

  it('draws a slotted thumbnail instead of the icon', async () => {
    const item = await mount(
      `<craft-media-item icon="newspaper"><img slot="thumbnail" alt="" src="data:image/gif;base64,R0lGODlhAQABAAAAACw="><span slot="heading">Annual report</span></craft-media-item>`
    );
    const thumbnail = part(item, 'thumbnail')!;

    expect(thumbnail.querySelector('craft-icon')).toBeNull();
    expect(thumbnail.querySelector('slot')!.assignedElements()).toHaveLength(1);
  });

  it('crops a slotted thumbnail to a 40px square', async () => {
    const item = await mount(
      `<craft-media-item><img slot="thumbnail" alt="" width="120" height="60" src="data:image/gif;base64,R0lGODlhAQABAAAAACw="><span slot="heading">Annual report</span></craft-media-item>`
    );
    const box = part(item, 'thumbnail')!.getBoundingClientRect();
    const image = item.querySelector('img')!.getBoundingClientRect();

    expect([box.width, box.height]).toEqual([40, 40]);
    expect([image.width, image.height]).toEqual([40, 40]);
  });

  it('renders its regions in a fixed order, whatever the order of its children', async () => {
    const item = await mount(
      `<craft-media-item icon="newspaper"><span slot="suffix">PR</span>Nov 1, 2026<span slot="heading">Annual report</span></craft-media-item>`
    );
    const order = [
      ...item.shadowRoot!.querySelectorAll(
        '[part~="thumbnail"], [part~="heading"], [part~="body"], [part~="suffix"]'
      ),
    ].map((element) => element.getAttribute('part'));

    expect(order).toEqual(['thumbnail', 'heading', 'body', 'suffix']);
  });

  it('renders no region for an empty slot', async () => {
    const item = await mount(
      `<craft-media-item><span slot="heading">Annual report</span>
      </craft-media-item>`
    );

    expect(part(item, 'thumbnail')).toBeNull();
    expect(part(item, 'body')).toBeNull();
    expect(part(item, 'suffix')).toBeNull();
    expect(part(item, 'heading')).not.toBeNull();
  });

  it('shows a region once something is slotted into it', async () => {
    const item = await mount(
      `<craft-media-item><span slot="heading">Annual report</span></craft-media-item>`
    );
    const suffix = document.createElement('span');
    suffix.slot = 'suffix';
    suffix.textContent = 'PR';
    item.append(suffix);
    await new Promise((resolve) => setTimeout(resolve));
    await item.updateComplete;

    expect(part(item, 'suffix')).not.toBeNull();
  });

  it('adds no role of its own', async () => {
    const item = await mount(
      `<craft-media-item icon="newspaper"><span slot="heading">Annual report</span>Nov 1, 2026<span slot="suffix">PR</span></craft-media-item>`
    );
    const roles = [item, ...item.shadowRoot!.querySelectorAll('[part]')].map(
      (element) => element.getAttribute('role')
    );

    expect(roles.every((role) => role === null)).toBe(true);
  });

  it('wraps a long heading rather than overflowing a narrow container', async () => {
    const item = await mount(
      `<craft-media-item icon="newspaper"><span slot="heading">Museum joins a European collections network for shared research</span>Nov 1, 2026</craft-media-item>`,
      'width: 200px'
    );
    const frame = document.querySelector('.frame')!;
    const heading = item.querySelector('[slot="heading"]')!;

    expect(item.getBoundingClientRect().right).toBeLessThanOrEqual(
      frame.getBoundingClientRect().right
    );
    expect(heading.getClientRects().length).toBeGreaterThan(1);
  });

  it('keeps the heading and body to one line each when truncated', async () => {
    const item = await mount(
      `<craft-media-item icon="newspaper" truncate><span slot="heading">Museum joins a European collections network for shared research</span><span>Nov 1, 2026 · News articles, events and exhibitions</span></craft-media-item>`,
      'width: 200px'
    );
    const heading = item.querySelector<HTMLElement>('[slot="heading"]')!;
    const body = item.querySelector<HTMLElement>('span:not([slot])')!;
    const lineHeight = parseFloat(getComputedStyle(heading).lineHeight);

    expect(heading.getBoundingClientRect().height).toBeLessThanOrEqual(
      lineHeight
    );
    expect(heading.scrollWidth).toBeGreaterThan(heading.clientWidth);
    expect(getComputedStyle(heading).textOverflow).toBe('ellipsis');
    expect(body.getBoundingClientRect().height).toBeLessThanOrEqual(
      parseFloat(getComputedStyle(body).lineHeight)
    );
    expect(item.getBoundingClientRect().right).toBeLessThanOrEqual(
      document.querySelector('.frame')!.getBoundingClientRect().right
    );
  });
});

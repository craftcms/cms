import {beforeEach, expect, it} from 'vite-plus/test';
import './timeline-item.js';
import type CraftTimelineItem from './timeline-item.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(heading: string): Promise<{
  marker: HTMLElement;
  heading: HTMLElement;
  meta: HTMLElement;
}> {
  document.body.innerHTML = `
    <craft-timeline-item last style="inline-size: 16rem">
      <span slot="marker">•</span>
      <span slot="heading">${heading}</span>
      <time slot="meta">9:42 AM</time>
    </craft-timeline-item>`;
  const item = document.querySelector<CraftTimelineItem>(
    'craft-timeline-item'
  )!;
  await item.updateComplete;

  return {
    marker: item.shadowRoot!.querySelector<HTMLElement>('[part="marker"]')!,
    heading: item.querySelector<HTMLElement>('[slot="heading"]')!,
    meta: item.querySelector<HTMLElement>('[slot="meta"]')!,
  };
}

function lineCenters(element: HTMLElement): number[] {
  const range = document.createRange();
  range.selectNodeContents(element);

  return [
    ...new Set(
      [...range.getClientRects()].map((rect) => (rect.top + rect.bottom) / 2)
    ),
  ].sort((a, b) => a - b);
}

function center(element: HTMLElement): number {
  const rect = element.getBoundingClientRect();
  return (rect.top + rect.bottom) / 2;
}

it.each([
  ['a one-line heading', 'Ada approved'],
  [
    'a heading that wraps',
    'Ada started editing the draft, withdrawing it from review',
  ],
])(
  'centers the first line of %s on the marker, and the metadata on that line',
  async (_case, text) => {
    const {marker, heading, meta} = await render(text);
    const [firstLine] = lineCenters(heading);

    expect(Math.abs(firstLine! - center(marker))).toBeLessThanOrEqual(0.5);
    expect(Math.abs(center(meta) - firstLine!)).toBeLessThanOrEqual(1.5);
  }
);

it('wraps the long heading onto more than one line', async () => {
  const {heading} = await render(
    'Ada started editing the draft, withdrawing it from review'
  );

  expect(lineCenters(heading).length).toBeGreaterThan(1);
});

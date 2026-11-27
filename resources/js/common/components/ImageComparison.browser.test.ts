import '../../../css/cp.css';
import '@craftcms/ui/components/action-menu/action-menu';
import '@craftcms/ui/components/badge/badge';
import '@craftcms/ui/components/button/button';
import {createApp, h, ref} from 'vue';
import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import ImageComparison from './ImageComparison.vue';
import type {
  ComparedDetail,
  ComparedImage,
  ImageComparisonView,
} from './imageComparison';
import ImageComparisonViews from './ImageComparisonViews.vue';

let teardown: () => void;
let thumb: string;

beforeEach(async () => {
  await page.viewport(1200, 800);
  const canvas = new OffscreenCanvas(30, 20);
  canvas.getContext('2d')!.fillRect(0, 0, 30, 20);
  const blob = await canvas.convertToBlob();
  thumb = await new Promise<string>((resolve) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result as string);
    reader.readAsDataURL(blob);
  });
});

afterEach(() => {
  teardown?.();
  document.body.replaceChildren();
});

const details: ComparedDetail[] = [
  {key: 'filename', label: 'Filename'},
  {key: 'size', label: 'File size'},
  {key: 'uploaded', label: 'Uploaded', compared: false},
];

function image(
  key: string,
  title: string,
  values: Record<string, string | null> = {},
  extra: Partial<ComparedImage> = {}
): ComparedImage {
  return {
    key,
    title,
    src: thumb,
    details: {filename: 'harbor.jpg', size: '72 kB', uploaded: null, ...values},
    ...extra,
  };
}

/** Mounts the comparison with its views' buttons beside it, as a host would. */
function mount(
  from: ComparedImage,
  to: ComparedImage[],
  props: {hideHeadings?: boolean} = {}
) {
  const view = ref<ImageComparisonView>('sideBySide');
  const comparison = ref<{slidable: boolean} | null>(null);
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp({
    render: () =>
      h('div', [
        comparison.value?.slidable
          ? h(ImageComparisonViews, {
              modelValue: view.value,
              'onUpdate:modelValue': (value: ImageComparisonView) =>
                (view.value = value),
            })
          : null,
        h(ImageComparison, {
          ref: comparison,
          from,
          to,
          fromLabel: 'Before',
          toLabel: 'After',
          details,
          view: view.value,
          'onUpdate:view': (value: ImageComparisonView) => (view.value = value),
          ...props,
        }),
      ]),
  });
  app.mount(host);
  teardown = () => app.unmount();
}

function status(): string | undefined {
  return document
    .querySelector('[data-image-comparison] [role="status"]')
    ?.textContent?.trim();
}

/** The details whose rows say they differ, by their row headers. */
function marked(): string[] {
  return [...document.querySelectorAll('th[scope="rowgroup"]')]
    .filter((header) => header.querySelector('craft-badge'))
    .map((header) => header.firstChild!.textContent!.trim());
}

it('marks and announces the details that differ, but not those it isn’t told to compare', async () => {
  mount(image('a', 'Harbor', {uploaded: '11/1/26'}), [
    image(
      'b',
      'Harbor copy',
      {filename: 'harbor-copy.jpg', uploaded: '11/2/26'},
      {url: '/assets/b'}
    ),
  ]);

  await expect.poll(marked).toEqual(['Filename']);
  await expect.poll(status).toBe('These details differ: Filename');
  await expect
    .element(page.getByRole('columnheader', {name: 'Before'}))
    .toBeVisible();

  const link = page.getByRole('link', {
    name: 'Harbor copy (opens in a new window)',
  });
  await expect.element(link).toHaveAttribute('href', '/assets/b');
  const header = page
    .getByRole('columnheader', {name: /After/})
    .element()
    .getBoundingClientRect();
  expect(
    header.right - (link.element() as HTMLElement).getBoundingClientRect().right
  ).toBeLessThan(40);
});

it('switches between several images from a menu in place of the heading', async () => {
  mount(image('a', 'Harbor'), [
    image('b', 'Harbor at dusk'),
    image('c', 'Harbor at night', {size: '80 kB'}),
  ]);
  await expect.poll(status).toBe('None of these details differ.');
  expect(page.getByRole('columnheader', {name: 'After'}).query()).toBeNull();

  await page
    .getByRole('button', {name: 'Harbor at dusk Choose an image'})
    .click();
  await page
    .getByRole('button', {name: 'Harbor at night', exact: true})
    .click();

  await expect.poll(status).toBe('These details differ: File size');
  expect(marked()).toEqual(['File size']);
  await expect
    .element(
      page.getByRole('button', {name: 'Harbor at night Choose an image'})
    )
    .toBeVisible();
});

it('slides a divider between the images, by keyboard or a single click', async () => {
  mount(image('a', 'Harbor'), [image('b', 'Harbor copy')]);
  await page.getByRole('button', {name: 'Slider'}).click();
  const slider = page.getByRole('slider', {
    name: 'Divider between Before and After',
  });
  await expect.element(slider).toBeVisible();
  const box = document
    .querySelector('.image-comparison__slider')!
    .getBoundingClientRect();
  const handleCenter = () => {
    const handle = document
      .querySelector('.image-comparison__slider-handle')!
      .getBoundingClientRect();
    return (handle.left + handle.right) / 2;
  };
  expect(Math.abs(handleCenter() - (box.left + box.width * 0.5))).toBeLessThan(
    1.5
  );

  await userEvent.click(slider, {
    position: {x: box.width * 0.75, y: box.height / 2},
  });
  await expect
    .poll(() => Number((slider.element() as HTMLInputElement).value))
    .toBeGreaterThan(70);
  const value = Number((slider.element() as HTMLInputElement).value);
  expect(value).toBeLessThan(80);
  expect(
    Math.abs(handleCenter() - (box.left + (box.width * value) / 100))
  ).toBeLessThan(1.5);

  await userEvent.keyboard('{ArrowLeft}{ArrowLeft}');
  await expect.element(slider).toHaveValue(String(value - 2));
});

it('offers no slider without a preview of each image', async () => {
  mount(image('a', 'Harbor'), [image('b', 'Harbor copy', {}, {src: null})]);
  await expect.element(page.getByText('Preview not available.')).toBeVisible();
  expect(page.getByRole('button', {name: 'Slider'}).query()).toBeNull();
});

it('caps how tall the images get, however wide it is', async () => {
  await page.viewport(2400, 1000);
  mount(image('a', 'Harbor'), [image('b', 'Harbor copy')]);
  await expect.poll(() => document.querySelectorAll('img').length).toBe(2);

  const previews = [
    ...document.querySelectorAll('.image-comparison__preview'),
  ].map((preview) => preview.getBoundingClientRect());
  // 30 × 20 images, in columns 1200px wide, would be 800px tall uncapped.
  expect(previews.map((preview) => Math.round(preview.height))).toEqual([
    384, 384,
  ]);
  expect(previews[0]!.width).toBeCloseTo(576, 0);

  await page.getByRole('button', {name: 'Slider'}).click();
  const slider = document
    .querySelector('.image-comparison__slider')!
    .getBoundingClientRect();
  expect(slider.height).toBeLessThanOrEqual(640);
  expect(slider.width / slider.height).toBeCloseTo(1.5, 1);
});

it('names the columns only for screen readers when the host already does', async () => {
  mount(image('a', 'Harbor'), [image('b', 'Harbor copy', {}, {url: '/b'})], {
    hideHeadings: true,
  });
  const header = page.getByRole('columnheader', {name: 'After'});
  await expect.element(header).toBeInTheDocument();
  expect(
    (header.element() as HTMLElement).getBoundingClientRect().height
  ).toBeLessThan(2);
  expect(page.getByRole('link').query()).toBeNull();
});
